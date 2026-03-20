<?php

namespace App\Modules\Worktime\Services;

use App\Models\CompanyDefaultTime;
use App\Models\Employee;
use App\Models\Holiday;
use App\Modules\Worktime\Models\ClockRecord;
use App\Modules\Worktime\Models\EmployeeEvent;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class WorktimeApurationService
{
    protected int $delayToleranceMinutes = 5;
    protected int $earlyExitToleranceMinutes = 5;

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
            ->groupBy(fn (ClockRecord $record) => $record->employee_id . '|' . $this->normalizeDateTime($record->recorded_at)?->format('Y-m-d'));

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
                'warning_days' => 0,
            ];

            foreach (CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay()) as $date) {
                $dateKey = $date->format('Y-m-d');
                $weekdayKey = strtolower($date->englishDayOfWeek);

                $records = $clockRecords
                    ->get($employee->id . '|' . $dateKey, collect())
                    ->sortBy(fn (ClockRecord $record) => $this->normalizeDateTime($record->recorded_at)?->format('Y-m-d H:i:s'))
                    ->values();

                $recordTimes = $records
                    ->map(fn (ClockRecord $record) => $this->normalizeDateTime($record->recorded_at)?->format('H:i'))
                    ->filter()
                    ->values()
                    ->all();

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
                $notes = collect($schedule['notes']);

                if ($worked['is_inconsistent'] && $worked['inconsistency_reason']) {
                    $notes->push($worked['inconsistency_reason']);
                }

                if ($schedule['expected_minutes'] > 0 && ! $worked['is_inconsistent'] && $records->isNotEmpty()) {
                    $firstRecord = $this->normalizeDateTime($records->first()?->recorded_at);
                    $lastRecord = $this->normalizeDateTime($records->last()?->recorded_at);

                    if ($firstRecord && $schedule['expected_start']) {
                        $rawDelay = $schedule['expected_start']->diffInMinutes($firstRecord, false);
                        $delayMinutes = $rawDelay > $this->delayToleranceMinutes ? $rawDelay : 0;

                        if ($delayMinutes > 0) {
                            $notes->push('Atraso identificado.');
                        }
                    }

                    if ($lastRecord && $schedule['expected_end']) {
                        $rawEarlyExit = $lastRecord->diffInMinutes($schedule['expected_end'], false);
                        $earlyExitMinutes = $rawEarlyExit > $this->earlyExitToleranceMinutes ? $rawEarlyExit : 0;

                        if ($earlyExitMinutes > 0) {
                            $notes->push('Saída antecipada identificada.');
                        }
                    }
                }

                if (! $worked['is_inconsistent']) {
                    if ($schedule['expected_minutes'] > 0) {
                        $overtimeMinutes = max(0, $worked['worked_minutes'] - $schedule['expected_minutes']);
                        $absenceMinutes = max(0, $schedule['expected_minutes'] - $worked['worked_minutes']);

                        if ($overtimeMinutes > 0) {
                            $notes->push('Horas extras no dia.');
                        }

                        if ($absenceMinutes > 0 && $worked['worked_minutes'] === 0) {
                            $notes->push('Ausência no dia.');
                        } elseif ($absenceMinutes > 0) {
                            $notes->push('Déficit de jornada no dia.');
                        }
                    } else {
                        $overtimeMinutes = $worked['worked_minutes'];

                        if ($overtimeMinutes > 0) {
                            $notes->push('Trabalho realizado em dia sem jornada prevista.');
                        }
                    }
                }

                $dayStatus = $this->resolveDayStatus(
                    expectedMinutes: $schedule['expected_minutes'],
                    workedMinutes: $worked['worked_minutes'],
                    isInconsistent: $worked['is_inconsistent'],
                    delayMinutes: $delayMinutes,
                    earlyExitMinutes: $earlyExitMinutes,
                    overtimeMinutes: $overtimeMinutes,
                );

                $day = [
                    'date' => $dateKey,
                    'date_label' => $date->format('d/m/Y'),
                    'expected_minutes' => $schedule['expected_minutes'],
                    'expected_hours' => $this->formatMinutes($schedule['expected_minutes']),
                    'worked_minutes' => $worked['worked_minutes'],
                    'worked_hours' => $this->formatMinutes($worked['worked_minutes']),
                    'delay_minutes' => $delayMinutes,
                    'delay_hours' => $this->formatMinutes($delayMinutes),
                    'early_exit_minutes' => $earlyExitMinutes,
                    'early_exit_hours' => $this->formatMinutes($earlyExitMinutes),
                    'overtime_minutes' => $overtimeMinutes,
                    'overtime_hours' => $this->formatMinutes($overtimeMinutes),
                    'absence_minutes' => $absenceMinutes,
                    'absence_hours' => $this->formatMinutes($absenceMinutes),
                    'records_count' => $records->count(),
                    'record_times' => $recordTimes,
                    'status' => $dayStatus,
                    'status_label' => $this->resolveDayStatusLabel($dayStatus),
                    'notes' => $notes->unique()->values()->all(),
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

                if ($day['status'] === 'warning') {
                    $summary['warning_days']++;
                }

                if ($day['worked_minutes'] > 0) {
                    $summary['worked_days']++;
                }

                $flatDays[] = [
                    'employee_name' => $employee->name,
                    'date' => $day['date_label'],
                    'expected_minutes' => $day['expected_minutes'],
                    'expected_hours' => $day['expected_hours'],
                    'worked_minutes' => $day['worked_minutes'],
                    'worked_hours' => $day['worked_hours'],
                    'delay_minutes' => $day['delay_minutes'],
                    'delay_hours' => $day['delay_hours'],
                    'early_exit_minutes' => $day['early_exit_minutes'],
                    'early_exit_hours' => $day['early_exit_hours'],
                    'overtime_minutes' => $day['overtime_minutes'],
                    'overtime_hours' => $day['overtime_hours'],
                    'absence_minutes' => $day['absence_minutes'],
                    'absence_hours' => $day['absence_hours'],
                    'records_count' => $day['records_count'],
                    'record_times' => implode(' • ', $day['record_times']),
                    'status' => $day['status'],
                    'status_label' => $day['status_label'],
                    'notes' => implode(' | ', $day['notes']),
                ];
            }

            $employeeResults[] = [
                'id' => $employee->id,
                'name' => $employee->name,
                'summary' => [
                    'expected_minutes' => $summary['expected_minutes'],
                    'expected_hours' => $this->formatMinutes($summary['expected_minutes']),
                    'worked_minutes' => $summary['worked_minutes'],
                    'worked_hours' => $this->formatMinutes($summary['worked_minutes']),
                    'delay_minutes' => $summary['delay_minutes'],
                    'delay_hours' => $this->formatMinutes($summary['delay_minutes']),
                    'early_exit_minutes' => $summary['early_exit_minutes'],
                    'early_exit_hours' => $this->formatMinutes($summary['early_exit_minutes']),
                    'overtime_minutes' => $summary['overtime_minutes'],
                    'overtime_hours' => $this->formatMinutes($summary['overtime_minutes']),
                    'absence_minutes' => $summary['absence_minutes'],
                    'absence_hours' => $this->formatMinutes($summary['absence_minutes']),
                    'inconsistent_days' => $summary['inconsistent_days'],
                    'worked_days' => $summary['worked_days'],
                    'warning_days' => $summary['warning_days'],
                ],
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
                'expected_hours' => $this->formatMinutes((int) collect($flatDays)->sum('expected_minutes')),
                'worked_minutes' => collect($flatDays)->sum('worked_minutes'),
                'worked_hours' => $this->formatMinutes((int) collect($flatDays)->sum('worked_minutes')),
                'delay_minutes' => collect($flatDays)->sum('delay_minutes'),
                'delay_hours' => $this->formatMinutes((int) collect($flatDays)->sum('delay_minutes')),
                'early_exit_minutes' => collect($flatDays)->sum('early_exit_minutes'),
                'early_exit_hours' => $this->formatMinutes((int) collect($flatDays)->sum('early_exit_minutes')),
                'overtime_minutes' => collect($flatDays)->sum('overtime_minutes'),
                'overtime_hours' => $this->formatMinutes((int) collect($flatDays)->sum('overtime_minutes')),
                'absence_minutes' => collect($flatDays)->sum('absence_minutes'),
                'absence_hours' => $this->formatMinutes((int) collect($flatDays)->sum('absence_minutes')),
                'inconsistent_days' => collect($flatDays)->where('status', 'inconsistent')->count(),
                'warning_days' => collect($flatDays)->where('status', 'warning')->count(),
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
                'inconsistency_reason' => null,
            ];
        }

        if ($records->count() % 2 !== 0) {
            return [
                'worked_minutes' => 0,
                'is_inconsistent' => true,
                'inconsistency_reason' => 'Quantidade ímpar de registros no dia.',
            ];
        }

        $orderedRecords = $records
            ->sortBy(fn (ClockRecord $record) => $this->normalizeDateTime($record->recorded_at)?->format('Y-m-d H:i:s'))
            ->values();

        $workedMinutes = 0;

        for ($i = 0; $i < $orderedRecords->count(); $i += 2) {
            $start = $this->normalizeDateTime($orderedRecords->get($i)?->recorded_at);
            $end = $this->normalizeDateTime($orderedRecords->get($i + 1)?->recorded_at);

            if (! $start || ! $end) {
                return [
                    'worked_minutes' => 0,
                    'is_inconsistent' => true,
                    'inconsistency_reason' => 'Par de registros incompleto ou inválido.',
                ];
            }

            if ($end->lessThanOrEqualTo($start)) {
                return [
                    'worked_minutes' => 0,
                    'is_inconsistent' => true,
                    'inconsistency_reason' => 'Sequência inválida de horários no dia.',
                ];
            }

            $workedMinutes += $start->diffInMinutes($end);
        }

        return [
            'worked_minutes' => $workedMinutes,
            'is_inconsistent' => false,
            'inconsistency_reason' => null,
        ];
    }

    protected function resolveDayStatus(
        int $expectedMinutes,
        int $workedMinutes,
        bool $isInconsistent,
        int $delayMinutes,
        int $earlyExitMinutes,
        int $overtimeMinutes,
    ): string {
        if ($isInconsistent) {
            return 'inconsistent';
        }

        if ($expectedMinutes === 0 && $workedMinutes === 0) {
            return 'neutral';
        }

        if ($expectedMinutes > 0 && $workedMinutes === 0) {
            return 'absence';
        }

        if ($delayMinutes > 0 || $earlyExitMinutes > 0 || $overtimeMinutes > 0) {
            return 'warning';
        }

        return 'ok';
    }

    protected function resolveDayStatusLabel(string $status): string
    {
        return match ($status) {
            'ok' => 'OK',
            'warning' => 'Atenção',
            'absence' => 'Ausência',
            'neutral' => 'Sem jornada',
            'inconsistent' => 'Inconsistente',
            default => '—',
        };
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

    protected function formatMinutes(int $minutes): string
    {
        $negative = $minutes < 0;
        $minutes = abs($minutes);

        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        return ($negative ? '-' : '') . str_pad((string) $hours, 2, '0', STR_PAD_LEFT) . ':' . str_pad((string) $remainingMinutes, 2, '0', STR_PAD_LEFT);
    }

    protected function normalizeDateTime(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value->copy();
        }

        if ($value instanceof CarbonInterface) {
            return Carbon::instance($value);
        }

        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value);
        }

        if (is_string($value) && trim($value) !== '') {
            try {
                return Carbon::parse($value);
            } catch (\Throwable $e) {
                return null;
            }
        }

        return null;
    }
}
