<?php

namespace App\Modules\Worktime\Services;

use App\Models\Employee;
use App\Models\EmployeeTime;
use App\Models\Holiday;
use App\Modules\Worktime\Enums\EmployeeEventType;
use App\Modules\Worktime\Models\ClockRecord;
use App\Modules\Worktime\Models\EmployeeEvent;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class HourBankSnapshotDayApurationService
{
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

        $baseExpectedStartTime = $employeeTime?->start_time;
        $baseExpectedEndTime = $employeeTime?->end_time;
        $baseExpectedBreakDuration = $employeeTime?->break_duration;

        $baseExpectedMinutes = $this->calculateExpectedMinutes(
            startTime: $baseExpectedStartTime,
            endTime: $baseExpectedEndTime,
            breakDuration: $baseExpectedBreakDuration,
        );

        $workedMinutes = $this->calculateWorkedMinutes($records);

        $expectedStartTime = $baseExpectedStartTime;
        $expectedEndTime = $baseExpectedEndTime;
        $expectedBreakDuration = $baseExpectedBreakDuration;

        $justifiedMinutes = 0;
        $lateMinutes = 0;
        $extraMinutes = 0;
        $absenceMinutes = 0;
        $suspensionMinutes = 0;
        $hasDivergence = false;
        $divergenceReason = null;
        $notes = null;

        if ($holiday) {
            if ($records->isNotEmpty()) {
                $hasDivergence = true;
                $divergenceReason = 'Existem registros em um dia de feriado.';
            }

            return [
                'work_date' => $date->format('Y-m-d'),
                'weekday' => $weekday,
                'weekday_label' => $weekdayLabel,
                'expected_start_time' => null,
                'expected_end_time' => null,
                'expected_break_duration' => null,
                'records' => $records->map(fn (string $time) => substr($time, 0, 5))->values()->all(),
                'justified_minutes' => 0,
                'late_minutes' => 0,
                'extra_minutes' => 0,
                'absence_minutes' => 0,
                'suspension_minutes' => 0,
                'balance_minutes' => 0,
                'has_divergence' => $hasDivergence,
                'divergence_reason' => $divergenceReason,
                'notes' => 'Feriado' . ($holiday->name ? ': ' . $holiday->name : '.'),
            ];
        }

        $fullDayEvent = $this->resolveFullDayEvent(
            events: $events,
            date: $date,
            expectedStartTime: $baseExpectedStartTime,
            expectedEndTime: $baseExpectedEndTime,
            expectedMinutes: $baseExpectedMinutes,
        );

        if ($fullDayEvent) {
            if ($records->isNotEmpty()) {
                $hasDivergence = true;
                $divergenceReason = 'Existem registros em um dia coberto integralmente por evento.';
            }

            if ($this->storesBlockedMinutesInSnapshot($fullDayEvent)) {
                $suspensionMinutes = $baseExpectedMinutes;
            }

            return [
                'work_date' => $date->format('Y-m-d'),
                'weekday' => $weekday,
                'weekday_label' => $weekdayLabel,
                'expected_start_time' => null,
                'expected_end_time' => null,
                'expected_break_duration' => null,
                'records' => $records->map(fn (string $time) => substr($time, 0, 5))->values()->all(),
                'justified_minutes' => 0,
                'late_minutes' => 0,
                'extra_minutes' => 0,
                'absence_minutes' => 0,
                'suspension_minutes' => $suspensionMinutes,
                'balance_minutes' => 0,
                'has_divergence' => $hasDivergence,
                'divergence_reason' => $divergenceReason,
                'notes' => $this->buildEmployeeEventNote($fullDayEvent),
            ];
        }

        if ($baseExpectedMinutes <= 0 && $records->isEmpty()) {
            return [
                'work_date' => $date->format('Y-m-d'),
                'weekday' => $weekday,
                'weekday_label' => $weekdayLabel,
                'expected_start_time' => null,
                'expected_end_time' => null,
                'expected_break_duration' => null,
                'records' => [],
                'justified_minutes' => 0,
                'late_minutes' => 0,
                'extra_minutes' => 0,
                'absence_minutes' => 0,
                'suspension_minutes' => 0,
                'balance_minutes' => 0,
                'has_divergence' => false,
                'divergence_reason' => null,
                'notes' => null,
            ];
        }

        if ($baseExpectedMinutes <= 0 && $records->isNotEmpty()) {
            return [
                'work_date' => $date->format('Y-m-d'),
                'weekday' => $weekday,
                'weekday_label' => $weekdayLabel,
                'expected_start_time' => null,
                'expected_end_time' => null,
                'expected_break_duration' => null,
                'records' => $records->map(fn (string $time) => substr($time, 0, 5))->values()->all(),
                'justified_minutes' => 0,
                'late_minutes' => 0,
                'extra_minutes' => 0,
                'absence_minutes' => 0,
                'suspension_minutes' => 0,
                'balance_minutes' => 0,
                'has_divergence' => true,
                'divergence_reason' => 'Existem registros em um dia sem jornada prevista.',
                'notes' => null,
            ];
        }

        if ($records->count() % 2 !== 0) {
            $hasDivergence = true;
            $divergenceReason = 'Quantidade ímpar de registros de ponto no dia.';
        }

        $blockedMinutes = $this->calculatePartialBlockedMinutes(
            events: $events,
            date: $date,
            expectedStartTime: $baseExpectedStartTime,
            expectedEndTime: $baseExpectedEndTime,
            fullDayEventId: $fullDayEvent?->id,
        );

        $effectiveExpectedMinutes = max(0, $baseExpectedMinutes - $blockedMinutes);
        $suspensionMinutes = $blockedMinutes;

        if ($records->isEmpty()) {
            $hasDivergence = true;
            $divergenceReason = 'Dia com jornada prevista e sem registros.';
        }

        if ($workedMinutes > $effectiveExpectedMinutes) {
            $extraMinutes = $workedMinutes - $effectiveExpectedMinutes;
        }

        if ($workedMinutes < $effectiveExpectedMinutes) {
            $lateMinutes = $effectiveExpectedMinutes - $workedMinutes;
        }

        $balanceMinutes = $extraMinutes - $lateMinutes;

        return [
            'work_date' => $date->format('Y-m-d'),
            'weekday' => $weekday,
            'weekday_label' => $weekdayLabel,
            'expected_start_time' => $expectedStartTime,
            'expected_end_time' => $expectedEndTime,
            'expected_break_duration' => $expectedBreakDuration,
            'records' => $records->map(fn (string $time) => substr($time, 0, 5))->values()->all(),
            'justified_minutes' => $justifiedMinutes,
            'late_minutes' => $lateMinutes,
            'extra_minutes' => $extraMinutes,
            'absence_minutes' => $absenceMinutes,
            'suspension_minutes' => $suspensionMinutes,
            'balance_minutes' => $balanceMinutes,
            'has_divergence' => $hasDivergence,
            'divergence_reason' => $divergenceReason,
            'notes' => $notes,
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
            ->get()
            ->map(fn (ClockRecord $record) => Carbon::parse($record->recorded_at)->format('H:i:s'))
            ->values();
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
            ->where('starts_at', '<=', $date->copy()->endOfDay())
            ->where('ends_at', '>=', $date->copy()->startOfDay())
            ->orderBy('starts_at')
            ->get();
    }

    protected function resolveFullDayEvent(
        Collection $events,
        Carbon $date,
        ?string $expectedStartTime,
        ?string $expectedEndTime,
        int $expectedMinutes,
    ): ?EmployeeEvent {
        if ($events->isEmpty()) {
            return null;
        }

        return $events->first(function (EmployeeEvent $event) use ($date, $expectedStartTime, $expectedEndTime, $expectedMinutes) {
            $eventType = $event->event_type;

            if (! $eventType instanceof EmployeeEventType) {
                return false;
            }

            if (! $this->canBlockTheDayInSnapshot($eventType)) {
                return false;
            }

            return $this->coversEntireRelevantDay(
                event: $event,
                date: $date,
                expectedStartTime: $expectedStartTime,
                expectedEndTime: $expectedEndTime,
                expectedMinutes: $expectedMinutes,
            );
        });
    }

    protected function calculatePartialBlockedMinutes(
        Collection $events,
        Carbon $date,
        ?string $expectedStartTime,
        ?string $expectedEndTime,
        ?int $fullDayEventId = null,
    ): int {
        if (! $expectedStartTime || ! $expectedEndTime) {
            return 0;
        }

        $scheduleStart = Carbon::parse($date->format('Y-m-d') . ' ' . $this->normalizeTime($expectedStartTime));
        $scheduleEnd = Carbon::parse($date->format('Y-m-d') . ' ' . $this->normalizeTime($expectedEndTime));

        if ($scheduleEnd->lessThanOrEqualTo($scheduleStart)) {
            return 0;
        }

        $minutes = 0;

        foreach ($events as $event) {
            if ($fullDayEventId && (int) $event->id === (int) $fullDayEventId) {
                continue;
            }

            if (! $this->isDispensationEvent($event)) {
                continue;
            }

            $minutes += $this->calculateOverlapMinutes(
                startA: $scheduleStart,
                endA: $scheduleEnd,
                startB: Carbon::parse($event->starts_at),
                endB: Carbon::parse($event->ends_at),
            );
        }

        return min($minutes, $scheduleStart->diffInMinutes($scheduleEnd));
    }

    protected function coversEntireRelevantDay(
        EmployeeEvent $event,
        Carbon $date,
        ?string $expectedStartTime,
        ?string $expectedEndTime,
        int $expectedMinutes,
    ): bool {
        if ($expectedMinutes > 0 && $expectedStartTime && $expectedEndTime) {
            $scheduleStart = Carbon::parse($date->format('Y-m-d') . ' ' . $this->normalizeTime($expectedStartTime));
            $scheduleEnd = Carbon::parse($date->format('Y-m-d') . ' ' . $this->normalizeTime($expectedEndTime));

            if ($scheduleEnd->lessThanOrEqualTo($scheduleStart)) {
                return false;
            }

            $overlap = $this->calculateOverlapMinutes(
                startA: $scheduleStart,
                endA: $scheduleEnd,
                startB: Carbon::parse($event->starts_at),
                endB: Carbon::parse($event->ends_at),
            );

            return $overlap >= $scheduleStart->diffInMinutes($scheduleEnd);
        }

        return Carbon::parse($event->starts_at)->lessThanOrEqualTo($date->copy()->startOfDay())
            && Carbon::parse($event->ends_at)->greaterThanOrEqualTo($date->copy()->endOfDay());
    }

    protected function calculateOverlapMinutes(
        Carbon $startA,
        Carbon $endA,
        Carbon $startB,
        Carbon $endB,
    ): int {
        $start = $startA->greaterThan($startB) ? $startA : $startB;
        $end = $endA->lessThan($endB) ? $endA : $endB;

        if ($end->lessThanOrEqualTo($start)) {
            return 0;
        }

        return $start->diffInMinutes($end);
    }

    protected function canBlockTheDayInSnapshot(EmployeeEventType $eventType): bool
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

    protected function storesBlockedMinutesInSnapshot(EmployeeEvent $employeeEvent): bool
    {
        return $this->isSuspensionEvent($employeeEvent) || $this->isDispensationEvent($employeeEvent);
    }

    protected function isSuspensionEvent(EmployeeEvent $employeeEvent): bool
    {
        return $employeeEvent->event_type === EmployeeEventType::Suspension;
    }

    protected function isDispensationEvent(EmployeeEvent $employeeEvent): bool
    {
        return $employeeEvent->event_type === EmployeeEventType::Dispensation;
    }

    protected function buildEmployeeEventNote(EmployeeEvent $employeeEvent): string
    {
        $label = $employeeEvent->event_type?->label() ?? 'Evento';

        if (filled($employeeEvent->notes)) {
            return $label . ': ' . trim($employeeEvent->notes);
        }

        return $label;
    }

    protected function calculateExpectedMinutes(
        ?string $startTime,
        ?string $endTime,
        ?string $breakDuration,
    ): int {
        if (! filled($startTime) || ! filled($endTime)) {
            return 0;
        }

        $start = Carbon::createFromFormat('H:i:s', $this->normalizeTime($startTime));
        $end = Carbon::createFromFormat('H:i:s', $this->normalizeTime($endTime));

        if ($end->lessThanOrEqualTo($start)) {
            return 0;
        }

        $minutes = $start->diffInMinutes($end);

        if (filled($breakDuration)) {
            $minutes -= $this->timeToMinutes($breakDuration);
        }

        return max(0, $minutes);
    }

    protected function calculateWorkedMinutes(Collection $records): int
    {
        if ($records->count() < 2 || $records->count() % 2 !== 0) {
            return 0;
        }

        $minutes = 0;

        foreach ($records->chunk(2) as $pair) {
            $pair = $pair->values();

            $start = Carbon::createFromFormat('H:i:s', $pair->get(0));
            $end = Carbon::createFromFormat('H:i:s', $pair->get(1));

            if ($end->greaterThan($start)) {
                $minutes += $start->diffInMinutes($end);
            }
        }

        return $minutes;
    }

    protected function timeToMinutes(string $time): int
    {
        [$hours, $minutes, $seconds] = array_pad(explode(':', $time), 3, '0');

        return ((int) $hours * 60) + (int) $minutes + ((int) $seconds > 0 ? 1 : 0);
    }

    protected function normalizeTime(string $time): string
    {
        return strlen($time) === 5 ? $time . ':00' : $time;
    }
}
