<?php

namespace App\Modules\Worktime\Services;

use App\Models\User;
use App\Modules\Worktime\Enums\EmployeeEventTimeMode;
use App\Modules\Worktime\Enums\EmployeeEventType;
use App\Modules\Worktime\Models\EmployeeEvent;
use Carbon\Carbon;

class EmployeeEventService
{
    public function create(array $data, User $user): EmployeeEvent
    {
        $eventType = EmployeeEventType::from($data['event_type']);
        $timeMode = $eventType->timeMode();

        $this->ensureTimeModeMatchesInput($timeMode, (bool) $data['is_partial']);

        [$startsAt, $endsAt] = $this->resolvePeriod(
            date: $data['date'],
            timeMode: $timeMode,
            startsAt: $data['starts_at'] ?? null,
            endsAt: $data['ends_at'] ?? null,
        );

        return EmployeeEvent::create([
            'tenant_id' => $user->tenant_id,
            'company_id' => session('current_company_id'),
            'employee_id' => $data['employee_id'],
            'event_type' => $eventType,
            'time_mode' => $timeMode,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'notes' => $data['notes'] ?? null,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    public function update(EmployeeEvent $employeeEvent, array $data, User $user): EmployeeEvent
    {
        $eventType = EmployeeEventType::from($data['event_type']);
        $timeMode = $eventType->timeMode();

        $this->ensureTimeModeMatchesInput($timeMode, (bool) $data['is_partial']);

        [$startsAt, $endsAt] = $this->resolvePeriod(
            date: $data['date'],
            timeMode: $timeMode,
            startsAt: $data['starts_at'] ?? null,
            endsAt: $data['ends_at'] ?? null,
        );

        $employeeEvent->update([
            'employee_id' => $data['employee_id'],
            'event_type' => $eventType,
            'time_mode' => $timeMode,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'notes' => $data['notes'] ?? null,
            'updated_by' => $user->id,
        ]);

        return $employeeEvent->refresh();
    }

    protected function resolvePeriod(
        string $date,
        EmployeeEventTimeMode $timeMode,
        ?string $startsAt = null,
        ?string $endsAt = null,
    ): array {
        $baseDate = Carbon::parse($date);

        if ($timeMode === EmployeeEventTimeMode::Day) {
            return [
                $baseDate->copy()->startOfDay(),
                $baseDate->copy()->endOfDay(),
            ];
        }

        $startDateTime = Carbon::parse($date . ' ' . $startsAt);
        $endDateTime = Carbon::parse($date . ' ' . $endsAt);

        return [$startDateTime, $endDateTime];
    }

    protected function ensureTimeModeMatchesInput(EmployeeEventTimeMode $timeMode, bool $isPartial): void
    {
        if ($timeMode === EmployeeEventTimeMode::Partial && ! $isPartial) {
            abort(422, 'O tipo informado exige evento parcial.');
        }

        if ($timeMode === EmployeeEventTimeMode::Day && $isPartial) {
            abort(422, 'O tipo informado exige evento diário.');
        }
    }
}
