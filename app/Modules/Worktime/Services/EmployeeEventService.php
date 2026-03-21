<?php

namespace App\Modules\Worktime\Services;

use App\Models\CompanyDefaultTime;
use App\Models\User;
use App\Modules\Worktime\Enums\EmployeeEventType;
use App\Modules\Worktime\Models\EmployeeEvent;
use Carbon\Carbon;

class EmployeeEventService
{
    public function __construct(
        protected EmployeeEventBankHourSyncService $bankHourSyncService,
    ) {
    }

    public function create(array $data, User $user): EmployeeEvent
    {
        [$startsAt, $endsAt] = $this->resolvePeriod(
            tenantId: $user->tenant_id,
            companyId: session('current_company_id'),
            inputMode: $data['input_mode'],
            date: $data['date'] ?? null,
            startsAt: $data['starts_at'] ?? null,
            endsAt: $data['ends_at'] ?? null,
        );

        $employeeEvent = EmployeeEvent::create([
            'tenant_id' => $user->tenant_id,
            'company_id' => session('current_company_id'),
            'employee_id' => $data['employee_id'],
            'event_type' => EmployeeEventType::from($data['event_type']),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'notes' => $data['notes'] ?? null,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->bankHourSyncService->sync($employeeEvent, $user);

        return $employeeEvent->refresh();
    }

    public function update(EmployeeEvent $employeeEvent, array $data, User $user): EmployeeEvent
    {
        [$startsAt, $endsAt] = $this->resolvePeriod(
            tenantId: $user->tenant_id,
            companyId: session('current_company_id'),
            inputMode: $data['input_mode'],
            date: $data['date'] ?? null,
            startsAt: $data['starts_at'] ?? null,
            endsAt: $data['ends_at'] ?? null,
        );

        $employeeEvent->update([
            'employee_id' => $data['employee_id'],
            'event_type' => EmployeeEventType::from($data['event_type']),
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'notes' => $data['notes'] ?? null,
            'updated_by' => $user->id,
        ]);

        $employeeEvent->refresh();

        $this->bankHourSyncService->sync($employeeEvent, $user);

        return $employeeEvent->refresh();
    }

    public function delete(EmployeeEvent $employeeEvent): void
    {
        $this->bankHourSyncService->delete($employeeEvent);
        $employeeEvent->delete();
    }

    protected function resolvePeriod(
        int $tenantId,
        int $companyId,
        string $inputMode,
        ?string $date = null,
        ?string $startsAt = null,
        ?string $endsAt = null,
    ): array {
        if ($inputMode === 'custom_period') {
            $startDateTime = Carbon::parse($startsAt);
            $endDateTime = Carbon::parse($endsAt);

            if ($endDateTime->lessThanOrEqualTo($startDateTime)) {
                abort(422, 'A data/hora final deve ser maior que a data/hora inicial.');
            }

            return [$startDateTime, $endDateTime];
        }

        $baseDate = Carbon::parse($date);
        $weekdayKey = strtolower($baseDate->englishDayOfWeek);

        $defaultTime = CompanyDefaultTime::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->get()
            ->first(fn (CompanyDefaultTime $time) => $time->weekday->value === $weekdayKey);

        if ($defaultTime && $defaultTime->start_time && $defaultTime->end_time) {
            return [
                Carbon::parse($date . ' ' . $defaultTime->start_time),
                Carbon::parse($date . ' ' . $defaultTime->end_time),
            ];
        }

        return [
            $baseDate->copy()->startOfDay(),
            $baseDate->copy()->endOfDay(),
        ];
    }
}
