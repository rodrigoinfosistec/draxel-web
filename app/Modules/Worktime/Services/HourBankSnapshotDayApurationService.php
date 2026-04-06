<?php

namespace App\Modules\Worktime\Services;

use App\Models\Employee;
use App\Models\EmployeeTime;
use App\Models\Holiday;
use App\Modules\Worktime\Enums\EmployeeEventType;
use App\Modules\Worktime\Models\ClockRecord;
use App\Modules\Worktime\Models\EmployeeEvent;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class HourBankSnapshotDayApurationService
{
    protected int $delayToleranceMinutes = 5;

    protected int $earlyExitToleranceMinutes = 5;

    public function apurate(Employee $employee, Carbon $date): array
    {
        $weekday = strtolower($date->englishDayOfWeek);
        $weekdayLabel = mb_strtoupper(substr($date->locale('pt_BR')->translatedFormat('D'), 0, 3));

        $employeeTime = EmployeeTime::query()
            ->where('tenant_id', $employee->tenant_id)
            ->where('company_id', $employee->company_id)
            ->where('employee_id', $employee->id)
            ->where('weekday', $weekday)
            ->first();

        $records = $this->loadRecords($employee, $date);
        $holiday = $this->findHoliday($employee, $date);
        $events = $this->loadEmployeeEvents($employee, $date);

        $schedule = $this->resolveExpectedSchedule(
            date: $date->copy(),
            employeeTime: $employeeTime,
            holiday: $holiday,
            events: $events,
        );

        $worked = $this->resolveWorkedMinutes($records);

        $justifiedMinutes = 0;
        $lateMinutes = 0;
        $extraMinutes = 0;
        $absenceMinutes = (int) $schedule['absence_minutes'];
        $suspensionMinutes = (int) $schedule['suspension_minutes'];
        $dsrWorkedMinutes = 0;
        $hasDivergence = false;
        $divergenceReason = null;
        $notes = collect($schedule['notes']);

        if ($worked['is_inconsistent']) {
            $hasDivergence = true;
            $divergenceReason = $worked['inconsistency_reason'];
        }

        if (! $worked['is_inconsistent']) {
            if ($schedule['expected_minutes'] > 0) {
                $balanceMinutes = $worked['worked_minutes'] - $schedule['expected_minutes'];

                $extraMinutesOutsideExpectedSchedule = $this->resolveExtraMinutesOutsideExpectedSchedule(
                    records: $records,
                    expectedStart: $schedule['expected_start'],
                    expectedEnd: $schedule['expected_end'],
                );

                $extraMinutes = max(
                    0,
                    $extraMinutesOutsideExpectedSchedule,
                    $balanceMinutes,
                );

                $grossLateMinutes = max(0, $extraMinutes - $balanceMinutes);

                $dueAbsentIntervals = $this->resolveDueAbsentIntervals(
                    records: $records,
                    expectedStart: $schedule['expected_start'],
                    expectedEnd: $schedule['expected_end'],
                    breakMinutes: $schedule['expected_break_minutes'],
                );

                $justifiedMinutes = min(
                    $grossLateMinutes,
                    $this->resolveJustifiedMinutesFromEvents(
                        events: $schedule['justifying_events'],
                        dueAbsentIntervals: $dueAbsentIntervals,
                    ),
                );

                $lateMinutes = max(0, $grossLateMinutes - $justifiedMinutes);

                if ($justifiedMinutes > 0) {
                    $notes->push('Período justificado no dia.');
                }

                if ($extraMinutes > 0) {
                    $notes->push('Horas extras no dia.');
                }

                $firstRecord = $this->normalizeDateTime($records->first()?->recorded_at);
                $lastRecord = $this->normalizeDateTime($records->last()?->recorded_at);

                if ($firstRecord && $schedule['expected_start']) {
                    $rawDelay = $this->diffMinutesSigned($schedule['expected_start'], $firstRecord);

                    if ($rawDelay > $this->delayToleranceMinutes) {
                        $notes->push('Atraso identificado.');
                    }
                }

                if ($lastRecord && $schedule['expected_end']) {
                    $rawEarlyExit = $this->diffMinutesSigned($lastRecord, $schedule['expected_end']);

                    if ($rawEarlyExit > $this->earlyExitToleranceMinutes) {
                        $notes->push('Saída antecipada identificada.');
                    }
                }

                if (
                    $worked['worked_minutes'] === 0
                    && $schedule['expected_minutes'] > 0
                    && $absenceMinutes === 0
                    && $suspensionMinutes === 0
                    && ! $schedule['has_event']
                    && ! $schedule['is_holiday']
                ) {
                    $absenceMinutes = $schedule['expected_minutes'];
                    $notes->push('Ausência no dia.');
                } elseif ($lateMinutes > 0 && $worked['worked_minutes'] > 0) {
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

        return [
            'work_date' => $date->format('Y-m-d'),
            'weekday' => $weekday,
            'weekday_label' => $weekdayLabel,
            'expected_start_time' => $schedule['expected_start']?->format('H:i:s'),
            'expected_end_time' => $schedule['expected_end']?->format('H:i:s'),
            'expected_break_duration' => $schedule['expected_break_duration'],
            'records' => $records
                ->map(fn (ClockRecord $record) => $this->normalizeDateTime($record->recorded_at)?->format('H:i'))
                ->filter()
                ->values()
                ->all(),
            'justified_minutes' => (int) $justifiedMinutes,
            'late_minutes' => (int) $lateMinutes,
            'extra_minutes' => (int) $extraMinutes,
            'absence_minutes' => (int) $absenceMinutes,
            'suspension_minutes' => (int) $suspensionMinutes,
            'dsr_worked_minutes' => (int) $dsrWorkedMinutes,
            'balance_minutes' => (int) ($extraMinutes - $lateMinutes),
            'has_divergence' => $hasDivergence,
            'divergence_reason' => $divergenceReason,
            'notes' => $notes->unique()->filter()->implode(' | ') ?: null,
        ];
    }

    protected function loadRecords(Employee $employee, Carbon $date): Collection
    {
        return ClockRecord::query()
            ->where('tenant_id', $employee->tenant_id)
            ->where('company_id', $employee->company_id)
            ->where('employee_id', $employee->id)
            ->whereDate('recorded_at', $date->toDateString())
            ->orderBy('recorded_at')
            ->get();
    }

    protected function findHoliday(Employee $employee, Carbon $date): ?Holiday
    {
        return Holiday::query()
            ->where('tenant_id', $employee->tenant_id)
            ->whereDate('date', $date->toDateString())
            ->first();
    }

    protected function loadEmployeeEvents(Employee $employee, Carbon $date): Collection
    {
        return EmployeeEvent::query()
            ->where('tenant_id', $employee->tenant_id)
            ->where('company_id', $employee->company_id)
            ->where('employee_id', $employee->id)
            ->where(function ($query) use ($date) {
                $query
                    ->whereBetween('starts_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])
                    ->orWhereBetween('ends_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()])
                    ->orWhere(function ($subQuery) use ($date) {
                        $subQuery
                            ->where('starts_at', '<=', $date->copy()->startOfDay())
                            ->where('ends_at', '>=', $date->copy()->endOfDay());
                    });
            })
            ->orderBy('starts_at')
            ->get();
    }

    protected function resolveExpectedSchedule(
        Carbon $date,
        ?EmployeeTime $employeeTime,
        ?Holiday $holiday,
        Collection $events,
    ): array {
        if (! $employeeTime || ! $employeeTime->start_time || ! $employeeTime->end_time) {
            return [
                'expected_start' => null,
                'expected_end' => null,
                'expected_break_duration' => null,
                'expected_break_minutes' => 0,
                'expected_minutes' => 0,
                'absence_minutes' => 0,
                'suspension_minutes' => 0,
                'justifying_events' => collect(),
                'has_event' => false,
                'is_holiday' => false,
                'notes' => ['DSR'],
            ];
        }

        if ($holiday) {
            return [
                'expected_start' => null,
                'expected_end' => null,
                'expected_break_duration' => null,
                'expected_break_minutes' => 0,
                'expected_minutes' => 0,
                'absence_minutes' => 0,
                'suspension_minutes' => 0,
                'justifying_events' => collect(),
                'has_event' => false,
                'is_holiday' => true,
                'notes' => ['Feriado' . ($holiday->name ? ': ' . $holiday->name : '')],
            ];
        }

        $expectedStart = Carbon::parse($date->format('Y-m-d') . ' ' . $employeeTime->start_time);
        $expectedEnd = Carbon::parse($date->format('Y-m-d') . ' ' . $employeeTime->end_time);
        $breakMinutes = $this->timeToMinutes($employeeTime->break_duration);

        $baseExpectedMinutes = max(0, $this->diffMinutesAbsolute($expectedStart, $expectedEnd) - $breakMinutes);
        $expectedMinutes = $baseExpectedMinutes;
        $absenceMinutes = 0;
        $suspensionMinutes = 0;
        $notes = [];
        $hasEvent = false;
        $justifyingEvents = collect();

        foreach ($events as $event) {
            $eventType = $event->event_type;

            if (! $eventType instanceof EmployeeEventType) {
                continue;
            }

            $eventLabel = $eventType->label();
            $eventStart = Carbon::parse($event->starts_at);
            $eventEnd = Carbon::parse($event->ends_at);

            if ($eventType->justifiesOnlyAbsentMinutes()) {
                $justifyingEvents->push($event);
                $notes[] = $eventLabel;
                continue;
            }

            if ($eventType === EmployeeEventType::Dispensation) {
                $notes[] = $eventLabel;
                $hasEvent = true;
                continue;
            }

            if (! $eventType->suppressesSchedule()) {
                $notes[] = $eventLabel;
                $hasEvent = true;
                continue;
            }

            $hasEvent = true;

            $overlapStart = $eventStart->greaterThan($expectedStart) ? $eventStart : $expectedStart;
            $overlapEnd = $eventEnd->lessThan($expectedEnd) ? $eventEnd : $expectedEnd;

            if (! $overlapStart->lessThan($overlapEnd)) {
                $notes[] = $eventLabel;
                continue;
            }

            $overlapMinutes = $this->diffMinutesAbsolute($overlapStart, $overlapEnd);

            $expectedMinutes -= $overlapMinutes;

            if ($eventType === EmployeeEventType::Absence) {
                $absenceMinutes += $overlapMinutes;
            }

            if ($eventType === EmployeeEventType::Suspension) {
                $suspensionMinutes += $overlapMinutes;
            }

            $notes[] = $eventLabel;
        }

        return [
            'expected_start' => $expectedStart,
            'expected_end' => $expectedEnd,
            'expected_break_duration' => $employeeTime->break_duration,
            'expected_break_minutes' => $breakMinutes,
            'expected_minutes' => max(0, (int) $expectedMinutes),
            'absence_minutes' => max(0, (int) $absenceMinutes),
            'suspension_minutes' => max(0, (int) $suspensionMinutes),
            'justifying_events' => $justifyingEvents,
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

            $workedMinutes += $this->diffMinutesAbsolute($start, $end);
        }

        return [
            'worked_minutes' => (int) $workedMinutes,
            'is_inconsistent' => false,
            'inconsistency_reason' => null,
        ];
    }

    protected function resolveExtraMinutesOutsideExpectedSchedule(
        Collection $records,
        ?Carbon $expectedStart,
        ?Carbon $expectedEnd,
    ): int {
        if (! $expectedStart || ! $expectedEnd || $records->count() === 0 || $records->count() % 2 !== 0) {
            return 0;
        }

        $orderedRecords = $records
            ->sortBy(fn (ClockRecord $record) => $this->normalizeDateTime($record->recorded_at)?->format('Y-m-d H:i:s'))
            ->values();

        $extraMinutes = 0;

        for ($i = 0; $i < $orderedRecords->count(); $i += 2) {
            $start = $this->normalizeDateTime($orderedRecords->get($i)?->recorded_at);
            $end = $this->normalizeDateTime($orderedRecords->get($i + 1)?->recorded_at);

            if (! $start || ! $end || $end->lessThanOrEqualTo($start)) {
                continue;
            }

            if ($start->lessThan($expectedStart)) {
                $beforeStartEnd = $end->lessThan($expectedStart) ? $end : $expectedStart;

                if ($start->lessThan($beforeStartEnd)) {
                    $extraMinutes += $this->diffMinutesAbsolute($start, $beforeStartEnd);
                }
            }

            if ($end->greaterThan($expectedEnd)) {
                $afterEndStart = $start->greaterThan($expectedEnd) ? $start : $expectedEnd;

                if ($afterEndStart->lessThan($end)) {
                    $extraMinutes += $this->diffMinutesAbsolute($afterEndStart, $end);
                }
            }
        }

        return (int) $extraMinutes;
    }

    protected function resolveDueAbsentIntervals(
        Collection $records,
        ?Carbon $expectedStart,
        ?Carbon $expectedEnd,
        int $breakMinutes,
    ): array {
        if (! $expectedStart || ! $expectedEnd) {
            return [];
        }

        $workedSegments = $this->resolveWorkedSegmentsWithinSchedule(
            records: $records,
            expectedStart: $expectedStart,
            expectedEnd: $expectedEnd,
        );

        $absentIntervals = [];
        $cursor = $expectedStart->copy();

        foreach ($workedSegments as $index => $segment) {
            $segmentStart = $segment['start'];
            $segmentEnd = $segment['end'];

            if ($cursor->lessThan($segmentStart)) {
                $absentIntervals[] = [
                    'start' => $cursor->copy(),
                    'end' => $segmentStart->copy(),
                    'kind' => $index === 0 ? 'leading' : 'internal',
                ];
            }

            if ($cursor->lessThan($segmentEnd)) {
                $cursor = $segmentEnd->copy();
            }
        }

        if ($cursor->lessThan($expectedEnd)) {
            $absentIntervals[] = [
                'start' => $cursor->copy(),
                'end' => $expectedEnd->copy(),
                'kind' => count($workedSegments) === 0 ? 'leading' : 'trailing',
            ];
        }

        return $this->applyBreakDeductionToAbsentIntervals($absentIntervals, $breakMinutes);
    }

    protected function resolveWorkedSegmentsWithinSchedule(
        Collection $records,
        Carbon $expectedStart,
        Carbon $expectedEnd,
    ): array {
        if ($records->count() === 0 || $records->count() % 2 !== 0) {
            return [];
        }

        $orderedRecords = $records
            ->sortBy(fn (ClockRecord $record) => $this->normalizeDateTime($record->recorded_at)?->format('Y-m-d H:i:s'))
            ->values();

        $segments = [];

        for ($i = 0; $i < $orderedRecords->count(); $i += 2) {
            $start = $this->normalizeDateTime($orderedRecords->get($i)?->recorded_at);
            $end = $this->normalizeDateTime($orderedRecords->get($i + 1)?->recorded_at);

            if (! $start || ! $end || $end->lessThanOrEqualTo($start)) {
                continue;
            }

            $clippedStart = $start->greaterThan($expectedStart) ? $start : $expectedStart;
            $clippedEnd = $end->lessThan($expectedEnd) ? $end : $expectedEnd;

            if ($clippedStart->lessThan($clippedEnd)) {
                $segments[] = [
                    'start' => $clippedStart->copy(),
                    'end' => $clippedEnd->copy(),
                ];
            }
        }

        return $segments;
    }

    protected function applyBreakDeductionToAbsentIntervals(array $absentIntervals, int $breakMinutes): array
    {
        if ($breakMinutes <= 0 || empty($absentIntervals)) {
            return array_values(array_filter(
                $absentIntervals,
                fn (array $interval) => $interval['start']->lessThan($interval['end'])
            ));
        }

        $breakRemaining = $breakMinutes;

        $prioritizedIndexes = [];

        foreach (['internal', 'leading', 'trailing'] as $kind) {
            foreach ($absentIntervals as $index => $interval) {
                if ($interval['kind'] === $kind) {
                    $prioritizedIndexes[] = $index;
                }
            }
        }

        foreach ($prioritizedIndexes as $index) {
            if ($breakRemaining <= 0) {
                break;
            }

            $interval = $absentIntervals[$index];
            $intervalMinutes = $this->diffMinutesAbsolute($interval['start'], $interval['end']);

            if ($intervalMinutes <= 0) {
                continue;
            }

            $deduction = min($breakRemaining, $intervalMinutes);

            $absentIntervals[$index]['start'] = $interval['start']->copy()->addMinutes($deduction);
            $breakRemaining -= $deduction;
        }

        return array_values(array_filter(
            $absentIntervals,
            fn (array $interval) => $interval['start']->lessThan($interval['end'])
        ));
    }

    protected function resolveJustifiedMinutesFromEvents(Collection $events, array $dueAbsentIntervals): int
    {
        if ($events->isEmpty() || empty($dueAbsentIntervals)) {
            return 0;
        }

        $remainingIntervals = array_map(
            fn (array $interval) => [
                'start' => $interval['start']->copy(),
                'end' => $interval['end']->copy(),
            ],
            $dueAbsentIntervals,
        );

        $justifiedMinutes = 0;

        foreach ($events as $event) {
            $eventStart = Carbon::parse($event->starts_at);
            $eventEnd = Carbon::parse($event->ends_at);

            if (! $eventStart->lessThan($eventEnd)) {
                continue;
            }

            foreach ($remainingIntervals as $index => $interval) {
                $overlapStart = $eventStart->greaterThan($interval['start']) ? $eventStart : $interval['start'];
                $overlapEnd = $eventEnd->lessThan($interval['end']) ? $eventEnd : $interval['end'];

                if (! $overlapStart->lessThan($overlapEnd)) {
                    continue;
                }

                $minutes = $this->diffMinutesAbsolute($overlapStart, $overlapEnd);

                if ($minutes <= 0) {
                    continue;
                }

                $justifiedMinutes += $minutes;
                $remainingIntervals[$index] = $this->subtractInterval($interval, $overlapStart, $overlapEnd);
            }

            $remainingIntervals = array_values(array_filter($remainingIntervals));
        }

        return (int) $justifiedMinutes;
    }

    protected function subtractInterval(array $interval, Carbon $removeStart, Carbon $removeEnd): array|null
    {
        $start = $interval['start'];
        $end = $interval['end'];

        if (! $removeStart->lessThan($removeEnd)) {
            return $interval;
        }

        if ($removeStart->lessThanOrEqualTo($start) && $removeEnd->greaterThanOrEqualTo($end)) {
            return null;
        }

        if ($removeStart->lessThanOrEqualTo($start) && $removeEnd->lessThan($end)) {
            return [
                'start' => $removeEnd->copy(),
                'end' => $end->copy(),
            ];
        }

        if ($removeStart->greaterThan($start) && $removeEnd->greaterThanOrEqualTo($end)) {
            return [
                'start' => $start->copy(),
                'end' => $removeStart->copy(),
            ];
        }

        if ($removeStart->greaterThan($start) && $removeEnd->lessThan($end)) {
            $leftMinutes = $this->diffMinutesAbsolute($start, $removeStart);
            $rightMinutes = $this->diffMinutesAbsolute($removeEnd, $end);

            if ($leftMinutes >= $rightMinutes) {
                return [
                    'start' => $start->copy(),
                    'end' => $removeStart->copy(),
                ];
            }

            return [
                'start' => $removeEnd->copy(),
                'end' => $end->copy(),
            ];
        }

        return $interval;
    }

    protected function timeToMinutes(?string $time): int
    {
        if (! $time) {
            return 0;
        }

        $parts = explode(':', $time);

        return ((int) ($parts[0] ?? 0) * 60) + (int) ($parts[1] ?? 0);
    }

    protected function diffMinutesAbsolute(Carbon $start, Carbon $end): int
    {
        return (int) floor(abs($start->diffInSeconds($end)) / 60);
    }

    protected function diffMinutesSigned(Carbon $start, Carbon $end): int
    {
        $seconds = $end->getTimestamp() - $start->getTimestamp();

        return $seconds >= 0
            ? (int) floor($seconds / 60)
            : (int) ceil($seconds / 60);
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
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }
}
