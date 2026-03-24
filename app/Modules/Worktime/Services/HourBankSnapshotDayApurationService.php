<?php

namespace App\Modules\Worktime\Services;

use App\Models\Employee;
use App\Models\EmployeeTime;
use App\Models\Holiday;
use App\Modules\Worktime\Models\ClockRecord;
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

        $expectedStartTime = $employeeTime?->start_time;
        $expectedEndTime = $employeeTime?->end_time;
        $expectedBreakDuration = $employeeTime?->break_duration;

        $expectedMinutes = $this->calculateExpectedMinutes(
            startTime: $expectedStartTime,
            endTime: $expectedEndTime,
            breakDuration: $expectedBreakDuration,
        );

        $workedMinutes = $this->calculateWorkedMinutes($records);

        $hasSchedule = $expectedMinutes > 0;
        $hasRecords = $records->isNotEmpty();
        $isHoliday = (bool) $holiday;

        $justifiedMinutes = 0;
        $lateMinutes = 0;
        $extraMinutes = 0;
        $absenceMinutes = 0;
        $suspensionMinutes = 0;
        $hasDivergence = false;
        $divergenceReason = null;
        $notes = null;

        if ($isHoliday) {
            $justifiedMinutes = $expectedMinutes;
            $absenceMinutes = 0;
            $lateMinutes = 0;
            $notes = 'Feriado' . ($holiday?->name ? ': ' . $holiday->name : '.');
        } else {
            if ($hasSchedule && ! $hasRecords) {
                $absenceMinutes = $expectedMinutes;
                $hasDivergence = true;
                $divergenceReason = 'Dia com jornada prevista e sem registros.';
            }

            if (! $hasSchedule && $hasRecords) {
                $hasDivergence = true;
                $divergenceReason = 'Existem registros em um dia sem jornada prevista.';
            }

            if ($hasRecords && $records->count() % 2 !== 0) {
                $hasDivergence = true;
                $divergenceReason = 'Quantidade ímpar de registros de ponto no dia.';
            }

            if ($hasSchedule && $hasRecords && filled($expectedStartTime)) {
                $firstRecord = $records->first();

                if ($firstRecord > $this->normalizeTime($expectedStartTime)) {
                    $lateMinutes = $this->diffInMinutes(
                        startTime: $expectedStartTime,
                        endTime: $firstRecord,
                    );
                }
            }

            if ($hasSchedule) {
                if ($workedMinutes > $expectedMinutes) {
                    $extraMinutes = $workedMinutes - $expectedMinutes;
                }

                if ($hasRecords && $workedMinutes < $expectedMinutes) {
                    $absenceMinutes = max($absenceMinutes, $expectedMinutes - $workedMinutes);
                }
            }
        }

        $balanceMinutes = $workedMinutes + $justifiedMinutes - $expectedMinutes;

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

    protected function diffInMinutes(string $startTime, string $endTime): int
    {
        $start = Carbon::createFromFormat('H:i:s', $this->normalizeTime($startTime));
        $end = Carbon::createFromFormat('H:i:s', $this->normalizeTime($endTime));

        if ($end->lessThanOrEqualTo($start)) {
            return 0;
        }

        return $start->diffInMinutes($end);
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
