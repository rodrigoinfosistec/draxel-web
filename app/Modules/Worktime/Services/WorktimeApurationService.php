<?php

namespace App\Modules\Worktime\Services;

use App\Models\CompanyDefaultTime;
use App\Models\Employee;
use App\Models\Holiday;
use App\Modules\Worktime\Enums\EmployeeEventType;
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
                'dispensation_minutes' => 0,
                'overtime_minutes' => 0,
                'dsr_worked_minutes' => 0,
                'inconsistent_days' => 0,
                'worked_days' => 0,
                'warning_days' => 0,
            ];

            foreach (CarbonPeriod::create($start->copy()->startOfDay(), $end->copy()->startOfDay()) as $date) {
                $dateKey = $date->format('Y-m-d');
                $weekdayKey = strtolower($date->englishDayOfWeek);
                $weekdayLabel = $this->resolveWeekdayLabel($date);

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
                    ->filter(fn (EmployeeEvent $event) => $this->eventTouchesDay($event, $date))
                    ->values();

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
                $dispensationMinutes = $schedule['dispensation_minutes'];
                $overtimeMinutes = 0;
                $dsrWorkedMinutes = 0;
                $notes = collect($schedule['notes']);

                if ($worked['is_inconsistent'] && $worked['inconsistency_reason']) {
                    $notes->push($worked['inconsistency_reason']);
                }

                if ($schedule['expected_minutes'] > 0 && ! $worked['is_inconsistent'] && $records->isNotEmpty()) {
                    $firstRecord = $this->normalizeDateTime($records->first()?->recorded_at);
                    $lastRecord = $this->normalizeDateTime($records->last()?->recorded_at);

                    if ($firstRecord && $schedule['expected_start']) {
                        $rawDelay = $schedule['expected_start']->diffInMinutes($firstRecord, false);
                        $entryDelayMinutes = $rawDelay > $this->delayToleranceMinutes ? $rawDelay : 0;

                        if ($entryDelayMinutes > 0) {
                            $delayMinutes += $entryDelayMinutes;
                            $notes->push('Atraso identificado.');
                        }
                    }

                    if ($lastRecord && $schedule['expected_end']) {
                        $rawEarlyExit = $lastRecord->diffInMinutes($schedule['expected_end'], false);
                        $earlyExitMinutes = $rawEarlyExit > $this->earlyExitToleranceMinutes ? $rawEarlyExit : 0;

                        if ($earlyExitMinutes > 0) {
                            $delayMinutes += $earlyExitMinutes;
                            $notes->push('Saída antecipada identificada.');
                        }
                    }
                }

                if (! $worked['is_inconsistent']) {
                    if ($schedule['expected_minutes'] > 0) {
                        $overtimeMinutes = max(0, $worked['worked_minutes'] - $schedule['expected_minutes']);

                        $deficitMinutes = max(0, $schedule['expected_minutes'] - $worked['worked_minutes']);
                        $alreadyCountedAsDelay = min($delayMinutes, $deficitMinutes);
                        $remainingDeficitMinutes = max(0, $deficitMinutes - $alreadyCountedAsDelay);

                        $delayMinutes += $remainingDeficitMinutes;

                        if ($overtimeMinutes > 0) {
                            $notes->push('Horas extras no dia.');
                        }

                        if (
                            $worked['worked_minutes'] === 0
                            && $schedule['expected_minutes'] > 0
                            && $dispensationMinutes === 0
                            && ! $schedule['has_event']
                            && ! $schedule['is_holiday']
                        ) {
                            $notes->push('Ausência no dia.');
                        } elseif (
                            $remainingDeficitMinutes > 0
                            && $worked['worked_minutes'] > 0
                            && ! $schedule['has_event']
                        ) {
                            $notes->push('Déficit de jornada no dia.');
                        }
                    } else {
                        $dsrWorkedMinutes = $worked['worked_minutes'];

                        if ($dsrWorkedMinutes > 0) {
                            $notes->push(
                                $schedule['is_holiday']
                                    ? 'Trabalho realizado em feriado.'
                                    : 'Trabalho realizado em DSR.'
                            );
                        }
                    }
                }

                $dayStatus = $this->resolveDayStatus(
                    expectedMinutes: $schedule['expected_minutes'],
                    workedMinutes: $worked['worked_minutes'],
                    isInconsistent: $worked['is_inconsistent'],
                    delayMinutes: $delayMinutes,
                    dispensationMinutes: $dispensationMinutes,
                    overtimeMinutes: $overtimeMinutes,
                    dsrWorkedMinutes: $dsrWorkedMinutes,
                    hasEvent: $schedule['has_event'],
                    isHoliday: $schedule['is_holiday'],
                );

                $day = [
                    'date' => $dateKey,
                    'date_label' => $date->format('d/m/Y'),
                    'weekday_label' => $weekdayLabel,
                    'expected_minutes' => $schedule['expected_minutes'],
                    'expected_hours' => $this->formatMinutes($schedule['expected_minutes']),
                    'worked_minutes' => $worked['worked_minutes'],
                    'worked_hours' => $this->formatMinutes($worked['worked_minutes']),
                    'delay_minutes' => $delayMinutes,
                    'delay_hours' => $this->formatMinutes($delayMinutes),
                    'dispensation_minutes' => $dispensationMinutes,
                    'dispensation_hours' => $this->formatMinutes($dispensationMinutes),
                    'overtime_minutes' => $overtimeMinutes,
                    'overtime_hours' => $this->formatMinutes($overtimeMinutes),
                    'dsr_worked_minutes' => $dsrWorkedMinutes,
                    'dsr_worked_hours' => $this->formatMinutes($dsrWorkedMinutes),
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
                $summary['dispensation_minutes'] += $day['dispensation_minutes'];
                $summary['overtime_minutes'] += $day['overtime_minutes'];
                $summary['dsr_worked_minutes'] += $day['dsr_worked_minutes'];

                if ($day['status'] === 'inconsistent') {
                    $summary['inconsistent_days']++;
                }

                if ($day['status'] === 'warning' || $day['status'] === 'absence') {
                    $summary['warning_days']++;
                }

                if ($day['worked_minutes'] > 0) {
                    $summary['worked_days']++;
                }

                $flatDays[] = [
                    'employee_name' => $employee->name,
                    'date' => $day['date_label'],
                    'weekday_label' => $day['weekday_label'],
                    'expected_minutes' => $day['expected_minutes'],
                    'expected_hours' => $day['expected_hours'],
                    'worked_minutes' => $day['worked_minutes'],
                    'worked_hours' => $day['worked_hours'],
                    'delay_minutes' => $day['delay_minutes'],
                    'delay_hours' => $day['delay_hours'],
                    'dispensation_minutes' => $day['dispensation_minutes'],
                    'dispensation_hours' => $day['dispensation_hours'],
                    'overtime_minutes' => $day['overtime_minutes'],
                    'overtime_hours' => $day['overtime_hours'],
                    'dsr_worked_minutes' => $day['dsr_worked_minutes'],
                    'dsr_worked_hours' => $day['dsr_worked_hours'],
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
                    'dispensation_minutes' => $summary['dispensation_minutes'],
                    'dispensation_hours' => $this->formatMinutes($summary['dispensation_minutes']),
                    'overtime_minutes' => $summary['overtime_minutes'],
                    'overtime_hours' => $this->formatMinutes($summary['overtime_minutes']),
                    'dsr_worked_minutes' => $summary['dsr_worked_minutes'],
                    'dsr_worked_hours' => $this->formatMinutes($summary['dsr_worked_minutes']),
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
                'dispensation_minutes' => collect($flatDays)->sum('dispensation_minutes'),
                'dispensation_hours' => $this->formatMinutes((int) collect($flatDays)->sum('dispensation_minutes')),
                'overtime_minutes' => collect($flatDays)->sum('overtime_minutes'),
                'overtime_hours' => $this->formatMinutes((int) collect($flatDays)->sum('overtime_minutes')),
                'dsr_worked_minutes' => collect($flatDays)->sum('dsr_worked_minutes'),
                'dsr_worked_hours' => $this->formatMinutes((int) collect($flatDays)->sum('dsr_worked_minutes')),
                'inconsistent_days' => collect($flatDays)->where('status', 'inconsistent')->count(),
                'warning_days' => collect($flatDays)->whereIn('status', ['warning', 'absence'])->count(),
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
                'dispensation_minutes' => 0,
                'has_event' => false,
                'is_holiday' => false,
                'notes' => ['DSR'],
            ];
        }

        if ($holiday) {
            return [
                'expected_start' => null,
                'expected_end' => null,
                'expected_minutes' => 0,
                'dispensation_minutes' => 0,
                'has_event' => false,
                'is_holiday' => true,
                'notes' => ['Feriado'],
            ];
        }

        $expectedStart = Carbon::parse($date->format('Y-m-d') . ' ' . $defaultTime->start_time);
        $expectedEnd = Carbon::parse($date->format('Y-m-d') . ' ' . $defaultTime->end_time);
        $breakMinutes = $this->timeToMinutes($defaultTime->break_duration);

        $baseExpectedMinutes = max(0, $expectedStart->diffInMinutes($expectedEnd) - $breakMinutes);
        $expectedMinutes = $baseExpectedMinutes;
        $dispensationMinutes = 0;
        $notes = [];
        $hasEvent = false;

        foreach ($events as $event) {
            $eventType = $event->event_type;

            if (! $eventType instanceof EmployeeEventType) {
                continue;
            }

            if (! $this->eventAffectsExpectedSchedule($eventType)) {
                continue;
            }

            $hasEvent = true;
            $eventLabel = $eventType->label() ?? 'Evento';

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
                    'dispensation_minutes' => $eventType === EmployeeEventType::Dispensation ? $baseExpectedMinutes : 0,
                    'has_event' => true,
                    'is_holiday' => false,
                    'notes' => [$eventLabel],
                ];
            }

            $overlapStart = $eventStart->greaterThan($expectedStart) ? $eventStart : $expectedStart;
            $overlapEnd = $eventEnd->lessThan($expectedEnd) ? $eventEnd : $expectedEnd;

            if (! $overlapStart->lessThan($overlapEnd)) {
                $notes[] = $eventLabel;
                continue;
            }

            $overlapMinutes = $overlapStart->diffInMinutes($overlapEnd);

            $expectedMinutes -= $overlapMinutes;

            if ($eventType === EmployeeEventType::Dispensation) {
                $dispensationMinutes += $overlapMinutes;
            }

            $notes[] = $eventLabel;
        }

        return [
            'expected_start' => $expectedStart,
            'expected_end' => $expectedEnd,
            'expected_minutes' => max(0, $expectedMinutes),
            'dispensation_minutes' => min(max(0, $dispensationMinutes), $baseExpectedMinutes),
            'has_event' => $hasEvent,
            'is_holiday' => false,
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
        int $dispensationMinutes,
        int $overtimeMinutes,
        int $dsrWorkedMinutes,
        bool $hasEvent,
        bool $isHoliday,
    ): string {
        if ($isInconsistent) {
            return 'inconsistent';
        }

        if ($expectedMinutes === 0 && $workedMinutes === 0) {
            return 'neutral';
        }

        if (
            $expectedMinutes > 0
            && $workedMinutes === 0
            && $dispensationMinutes === 0
            && ! $hasEvent
            && ! $isHoliday
        ) {
            return 'absence';
        }

        if (
            $delayMinutes > 0
            || $dispensationMinutes > 0
            || $overtimeMinutes > 0
            || $dsrWorkedMinutes > 0
            || $hasEvent
        ) {
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

    protected function resolveWeekdayLabel(Carbon $date): string
    {
        return match ((int) $date->dayOfWeek) {
            Carbon::SUNDAY => 'DOM',
            Carbon::MONDAY => 'SEG',
            Carbon::TUESDAY => 'TER',
            Carbon::WEDNESDAY => 'QUA',
            Carbon::THURSDAY => 'QUI',
            Carbon::FRIDAY => 'SEX',
            Carbon::SATURDAY => 'SÁB',
            default => '—',
        };
    }

    protected function eventTouchesDay(EmployeeEvent $event, Carbon $date): bool
    {
        return $event->starts_at->lessThanOrEqualTo($date->copy()->endOfDay())
            && $event->ends_at->greaterThanOrEqualTo($date->copy()->startOfDay());
    }

    protected function eventAffectsExpectedSchedule(EmployeeEventType $eventType): bool
    {
        return in_array($eventType, [
            EmployeeEventType::MedicalCertificate,
            EmployeeEventType::DayOff,
            EmployeeEventType::Suspension,
            EmployeeEventType::Vacation,
            EmployeeEventType::Leave,
            EmployeeEventType::Declaration,
            EmployeeEventType::Absence,
            EmployeeEventType::Dispensation,
        ], true);
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
