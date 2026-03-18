<?php

namespace App\Modules\Worktime\Services;

use App\Models\CompanyDefaultTime;
use App\Models\Employee;
use App\Models\Holiday;
use App\Modules\Worktime\Models\ClockRecord;
use App\Modules\Worktime\Models\EmployeeEvent;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class WorktimeApurationService
{
    public function calculate(
        int $tenantId,
        int $companyId,
        string $startDate,
        string $endDate,
        array $employeeIds = [],
    ): array {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->endOfDay();

        $employees = Employee::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->when($employeeIds, fn ($query) => $query->whereIn('id', $employeeIds))
            ->orderBy('name')
            ->get(['id', 'name']);

        $defaultTimes = CompanyDefaultTime::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->get()
            ->keyBy(fn (CompanyDefaultTime $time) => $time->weekday->value);

        $holidays = Holiday::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn ($holiday) => Carbon::parse($holiday->date)->format('Y-m-d'));

        $clockRecords = ClockRecord::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->whereBetween('recorded_at', [$start, $end])
            ->when($employeeIds, fn ($query) => $query->whereIn('employee_id', $employeeIds))
            ->orderBy('recorded_at')
            ->get()
            ->groupBy(fn (ClockRecord $record) => $record->employee_id . '|' . $record->recorded_at->format('Y-m-d'));

        $employeeEvents = EmployeeEvent::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where(function ($query) use ($start, $end) {
                $query
                    ->whereBetween('starts_at', [$start, $end])
                    ->orWhereBetween('ends_at', [$start, $end])
                    ->orWhere(function ($subQuery) use ($start, $end) {
                        $subQuery
                            ->where('starts_at', '<=', $start)
                            ->where('ends_at', '>=', $end);
                    });
            })
            ->when($employeeIds, fn ($query) => $query->whereIn('employee_id', $employeeIds))
            ->get()
            ->groupBy(fn (EmployeeEvent $event) => $event->employee_id);

        $employeeResults = [];
        $flatDays = [];

        foreach ($employees as $employee) {
            $days = [];
            $summary = [
                'expected_minutes' => 0,
                'worked_minutes' => 0,
                'delay_minutes' => 0,
                'early_exit_minutes' => 0,
                'overtime_minutes' => 0,
                'absence_minutes' => 0,
                'inconsistent_days' => 0,
                'worked_days' => 0,
            ];

            foreach (CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay()) as $date) {
                $dateKey = $date->format('Y-m-d');
                $weekdayKey = strtolower($date->englishDayOfWeek);

                $records = $clockRecords->get($employee->id . '|' . $dateKey, collect())->values();
                $events = collect($employeeEvents->get($employee->id, collect()))
                    ->filter(fn (EmployeeEvent $event) => $this->eventTouchesDay($event, $date));

                $holiday = $holidays->get($dateKey);
                $defaultTime = $defaultTimes->get($weekdayKey);

                $schedule = $this->resolveExpectedSchedule(
                    date: $date->copy(),
                    defaultTime: $defaultTime,
                    holiday: $holiday,
                    events: $events,
                );

                $worked = $this->resolveWorkedMinutes($records);

                $delayMinutes = 0;
                $earlyExitMinutes = 0;
                $overtimeMinutes = 0;
                $absenceMinutes = 0;

                if ($schedule['expected_minutes'] > 0 && ! $worked['is_inconsistent'] && $records->isNotEmpty()) {
                    $firstRecord = $records->first()->recorded_at;
                    $lastRecord = $records->last()->recorded_at;

                    if ($schedule['expected_start']) {
                        $delayMinutes = max(0, $schedule['expected_start']->diffInMinutes($firstRecord, false));
                    }

                    if ($schedule['expected_end']) {
                        $earlyExitMinutes = max(0, $lastRecord->diffInMinutes($schedule['expected_end'], false));
                    }
                }

                if (! $worked['is_inconsistent']) {
                    if ($schedule['expected_minutes'] > 0) {
                        $overtimeMinutes = max(0, $worked['worked_minutes'] - $schedule['expected_minutes']);
                        $absenceMinutes = max(0, $schedule['expected_minutes'] - $worked['worked_minutes']);
                    } else {
                        $overtimeMinutes = $worked['worked_minutes'];
                    }
                }

                $dayStatus = 'ok';
                $dayNotes = collect($schedule['notes']);

                if ($worked['is_inconsistent']) {
                    $dayStatus = 'inconsistent';
                    $dayNotes->push('Quantidade ímpar de registros no dia.');
                } elseif ($schedule['expected_minutes'] === 0 && $worked['worked_minutes'] === 0) {
                    $dayStatus = 'neutral';
                } elseif ($schedule['expected_minutes'] > 0 && $worked['worked_minutes'] === 0) {
                    $dayStatus = 'absence';
                }

                $day = [
                    'date' => $dateKey,
                    'date_label' => $date->format('d/m/Y'),
                    'expected_minutes' => $schedule['expected_minutes'],
                    'worked_minutes' => $worked['worked_minutes'],
                    'delay_minutes' => $delayMinutes,
                    'early_exit_minutes' => $earlyExitMinutes,
                    'overtime_minutes' => $overtimeMinutes,
                    'absence_minutes' => $absenceMinutes,
                    'records_count' => $records->count(),
                    'status' => $dayStatus,
                    'notes' => $dayNotes->values()->all(),
                ];

                $days[] = $day;

                $summary['expected_minutes'] += $day['expected_minutes'];
                $summary['worked_minutes'] += $day['worked_minutes'];
                $summary['delay_minutes'] += $day['delay_minutes'];
                $summary['early_exit_minutes'] += $day['early_exit_minutes'];
                $summary['overtime_minutes'] += $day['overtime_minutes'];
                $summary['absence_minutes'] += $day['absence_minutes'];

                if ($day['status'] === 'inconsistent') {
                    $summary['inconsistent_days']++;
                }

                if ($day['worked_minutes'] > 0) {
                    $summary['worked_days']++;
                }

                $flatDays[] = [
                    'employee_name' => $employee->name,
                    'date' => $day['date_label'],
                    'expected_minutes' => $day['expected_minutes'],
                    'worked_minutes' => $day['worked_minutes'],
                    'delay_minutes' => $day['delay_minutes'],
                    'early_exit_minutes' => $day['early_exit_minutes'],
                    'overtime_minutes' => $day['overtime_minutes'],
                    'absence_minutes' => $day['absence_minutes'],
                    'records_count' => $day['records_count'],
                    'status' => $day['status'],
                    'notes' => implode(' | ', $day['notes']),
                ];
            }

            $employeeResults[] = [
                'id' => $employee->id,
                'name' => $employee->name,
                'summary' => $summary,
                'days' => $days,
            ];
        }

        return [
            'employees' => $employeeResults,
            'flat_days' => $flatDays,
            'totals' => [
                'employees_count' => count($employeeResults),
                'days_count' => count($flatDays),
                'expected_minutes' => collect($flatDays)->sum('expected_minutes'),
                'worked_minutes' => collect($flatDays)->sum('worked_minutes'),
                'delay_minutes' => collect($flatDays)->sum('delay_minutes'),
                'early_exit_minutes' => collect($flatDays)->sum('early_exit_minutes'),
                'overtime_minutes' => collect($flatDays)->sum('overtime_minutes'),
                'absence_minutes' => collect($flatDays)->sum('absence_minutes'),
                'inconsistent_days' => collect($flatDays)->where('status', 'inconsistent')->count(),
            ],
        ];
    }

    protected function resolveExpectedSchedule(
        Carbon $date,
        ?CompanyDefaultTime $defaultTime,
        $holiday,
        Collection $events,
    ): array {
        if (! $defaultTime || ! $defaultTime->start_time || ! $defaultTime->end_time) {
            return [
                'expected_start' => null,
                'expected_end' => null,
                'expected_minutes' => 0,
                'notes' => ['Sem jornada prevista.'],
            ];
        }

        if ($holiday) {
            return [
                'expected_start' => null,
                'expected_end' => null,
                'expected_minutes' => 0,
                'notes' => ['Feriado.'],
            ];
        }

        $expectedStart = Carbon::parse($date->format('Y-m-d') . ' ' . $defaultTime->start_time);
        $expectedEnd = Carbon::parse($date->format('Y-m-d') . ' ' . $defaultTime->end_time);
        $breakMinutes = $this->timeToMinutes($defaultTime->break_duration);

        $expectedMinutes = max(0, $expectedStart->diffInMinutes($expectedEnd) - $breakMinutes);
        $notes = [];

        foreach ($events as $event) {
            $eventStart = $event->starts_at->copy();
            $eventEnd = $event->ends_at->copy();

            if (
                $eventStart->lessThanOrEqualTo($date->copy()->startOfDay())
                && $eventEnd->greaterThanOrEqualTo($date->copy()->endOfDay())
            ) {
                return [
                    'expected_start' => null,
                    'expected_end' => null,
                    'expected_minutes' => 0,
                    'notes' => ['Evento diário: ' . ($event->event_type?->label() ?? 'Evento')],
                ];
            }

            $overlapStart = $eventStart->greaterThan($expectedStart) ? $eventStart : $expectedStart;
            $overlapEnd = $eventEnd->lessThan($expectedEnd) ? $eventEnd : $expectedEnd;

            if ($overlapStart->lessThan($overlapEnd)) {
                $expectedMinutes -= $overlapStart->diffInMinutes($overlapEnd);
                $notes[] = 'Evento parcial: ' . ($event->event_type?->label() ?? 'Evento');
            }
        }

        return [
            'expected_start' => $expectedStart,
            'expected_end' => $expectedEnd,
            'expected_minutes' => max(0, $expectedMinutes),
            'notes' => $notes,
        ];
    }

    protected function resolveWorkedMinutes(Collection $records): array
    {
        if ($records->count() === 0) {
            return [
                'worked_minutes' => 0,
                'is_inconsistent' => false,
            ];
        }

        if ($records->count() % 2 !== 0) {
            return [
                'worked_minutes' => 0,
                'is_inconsistent' => true,
            ];
        }

        $workedMinutes = 0;
        $chunks = $records->chunk(2);

        foreach ($chunks as $chunk) {
            $start = $chunk->get(0)?->recorded_at;
            $end = $chunk->get(1)?->recorded_at;

            if (! $start || ! $end || $end->lessThanOrEqualTo($start)) {
                return [
                    'worked_minutes' => 0,
                    'is_inconsistent' => true,
                ];
            }

            $workedMinutes += $start->diffInMinutes($end);
        }

        return [
            'worked_minutes' => $workedMinutes,
            'is_inconsistent' => false,
        ];
    }

    protected function eventTouchesDay(EmployeeEvent $event, Carbon $date): bool
    {
        return $event->starts_at->lessThanOrEqualTo($date->copy()->endOfDay())
            && $event->ends_at->greaterThanOrEqualTo($date->copy()->startOfDay());
    }

    protected function timeToMinutes(?string $time): int
    {
        if (! $time) {
            return 0;
        }

        [$hours, $minutes] = explode(':', $time);

        return ((int) $hours * 60) + (int) $minutes;
    }
}
