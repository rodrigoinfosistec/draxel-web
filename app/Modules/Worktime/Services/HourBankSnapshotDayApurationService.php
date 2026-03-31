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
        $absenceMinutes = 0;
        $dispensationMinutes = $schedule['dispensation_minutes'];
        $dsrWorkedMinutes = 0;
        $hasDivergence = false;
        $divergenceReason = null;
        $notes = collect($schedule['notes']);

        if ($worked['is_inconsistent']) {
            $hasDivergence = true;
            $divergenceReason = $worked['inconsistency_reason'];
        }

        if ($schedule['expected_minutes'] > 0 && ! $worked['is_inconsistent'] && $records->isNotEmpty()) {
            $firstRecord = $this->normalizeDateTime($records->first()?->recorded_at);
            $lastRecord = $this->normalizeDateTime($records->last()?->recorded_at);

            if ($firstRecord && $schedule['expected_start']) {
                $rawDelay = $this->diffMinutesSigned($schedule['expected_start'], $firstRecord);
                $entryDelayMinutes = $rawDelay > $this->delayToleranceMinutes ? $rawDelay : 0;

                if ($entryDelayMinutes > 0) {
                    $lateMinutes += $entryDelayMinutes;
                    $notes->push('Atraso identificado.');
                }
            }

            if ($lastRecord && $schedule['expected_end']) {
                $rawEarlyExit = $this->diffMinutesSigned($lastRecord, $schedule['expected_end']);
                $earlyExitMinutes = $rawEarlyExit > $this->earlyExitToleranceMinutes ? $rawEarlyExit : 0;

                if ($earlyExitMinutes > 0) {
                    $lateMinutes += $earlyExitMinutes;
                    $notes->push('Saída antecipada identificada.');
                }
            }
        }

        if (! $worked['is_inconsistent']) {
            if ($schedule['expected_minutes'] > 0) {
                $extraMinutes = max(0, $worked['worked_minutes'] - $schedule['expected_minutes']);

                $deficitMinutes = max(0, $schedule['expected_minutes'] - $worked['worked_minutes']);
                $alreadyCountedAsDelay = min($lateMinutes, $deficitMinutes);
                $remainingDeficitMinutes = max(0, $deficitMinutes - $alreadyCountedAsDelay);

                $lateMinutes += $remainingDeficitMinutes;

                if ($extraMinutes > 0) {
                    $notes->push('Horas extras no dia.');
                }

                if (
                    $worked['worked_minutes'] === 0
                    && $schedule['expected_minutes'] > 0
                    && $dispensationMinutes === 0
                    && ! $schedule['has_event']
                    && ! $schedule['is_holiday']
                ) {
                    $absenceMinutes = $schedule['expected_minutes'];
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
            'suspension_minutes' => (int) $dispensationMinutes,
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
                'expected_break_duration' => null,
                'expected_minutes' => 0,
                'dispensation_minutes' => 0,
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
            $eventLabel = $eventType->label();

            $eventStart = Carbon::parse($event->starts_at);
            $eventEnd = Carbon::parse($event->ends_at);

            if (
                $eventStart->lessThanOrEqualTo($date->copy()->startOfDay())
                && $eventEnd->greaterThanOrEqualTo($date->copy()->endOfDay())
            ) {
                return [
                    'expected_start' => null,
                    'expected_end' => null,
                    'expected_break_duration' => null,
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

            $overlapMinutes = $this->diffMinutesAbsolute($overlapStart, $overlapEnd);

            $expectedMinutes -= $overlapMinutes;

            if ($eventType === EmployeeEventType::Dispensation) {
                $dispensationMinutes += $overlapMinutes;
            }

            $notes[] = $eventLabel;
        }

        return [
            'expected_start' => $expectedStart,
            'expected_end' => $expectedEnd,
            'expected_break_duration' => $employeeTime->break_duration,
            'expected_minutes' => max(0, (int) $expectedMinutes),
            'dispensation_minutes' => min(max(0, (int) $dispensationMinutes), $baseExpectedMinutes),
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
