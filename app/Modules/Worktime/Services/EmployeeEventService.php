<?php

namespace App\Modules\Worktime\Services;

use App\Models\User;
use App\Modules\Worktime\Enums\EmployeeEventType;
use App\Modules\Worktime\Models\EmployeeEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeEventService
{
    public function __construct(
        protected EmployeeEventBankHourSyncService $bankHourSyncService,
    ) {
    }

    public function create(array $data, User $user): EmployeeEvent
    {
        $eventType = EmployeeEventType::from($data['event_type']);

        [$startsAt, $endsAt] = $this->resolvePeriod(
            tenantId: $user->tenant_id,
            companyId: session('current_company_id'),
            employeeId: (int) $data['employee_id'],
            eventType: $eventType,
            inputMode: $data['input_mode'],
            date: $data['date'] ?? null,
            startsAt: $data['starts_at'] ?? null,
            endsAt: $data['ends_at'] ?? null,
        );

        $this->ensureNoDuplicateEvent(
            tenantId: $user->tenant_id,
            companyId: session('current_company_id'),
            employeeId: (int) $data['employee_id'],
            eventType: $eventType,
            startsAt: $startsAt,
            endsAt: $endsAt,
        );

        $employeeEvent = EmployeeEvent::create([
            'tenant_id' => $user->tenant_id,
            'company_id' => session('current_company_id'),
            'employee_id' => $data['employee_id'],
            'event_type' => $eventType,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'notes' => $this->normalizeNotes($data['notes'] ?? null),
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->bankHourSyncService->sync($employeeEvent, $user);

        return $employeeEvent->refresh();
    }

    public function update(EmployeeEvent $employeeEvent, array $data, User $user): EmployeeEvent
    {
        $eventType = EmployeeEventType::from($data['event_type']);

        [$startsAt, $endsAt] = $this->resolvePeriod(
            tenantId: $user->tenant_id,
            companyId: session('current_company_id'),
            employeeId: (int) $data['employee_id'],
            eventType: $eventType,
            inputMode: $data['input_mode'],
            date: $data['date'] ?? null,
            startsAt: $data['starts_at'] ?? null,
            endsAt: $data['ends_at'] ?? null,
        );

        $this->ensureNoDuplicateEvent(
            tenantId: $user->tenant_id,
            companyId: session('current_company_id'),
            employeeId: (int) $data['employee_id'],
            eventType: $eventType,
            startsAt: $startsAt,
            endsAt: $endsAt,
            ignoreEventId: $employeeEvent->id,
        );

        $employeeEvent->update([
            'employee_id' => $data['employee_id'],
            'event_type' => $eventType,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'notes' => $this->normalizeNotes($data['notes'] ?? null),
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
        int $employeeId,
        EmployeeEventType $eventType,
        string $inputMode,
        ?string $date = null,
        ?string $startsAt = null,
        ?string $endsAt = null,
    ): array {
        if ($eventType->requiresScheduleDayMode() && $inputMode !== 'schedule_day') {
            throw ValidationException::withMessages([
                'input_mode' => 'O evento de falta deve usar a jornada prevista do dia.',
            ]);
        }

        if ($inputMode === 'custom_period') {
            if (! $startsAt || ! $endsAt) {
                throw ValidationException::withMessages([
                    'starts_at' => 'Informe a data/hora inicial.',
                    'ends_at' => 'Informe a data/hora final.',
                ]);
            }

            $startDateTime = Carbon::parse($startsAt);
            $endDateTime = Carbon::parse($endsAt);

            if ($endDateTime->lessThanOrEqualTo($startDateTime)) {
                throw ValidationException::withMessages([
                    'ends_at' => 'A data/hora final deve ser maior que a data/hora inicial.',
                ]);
            }

            return [$startDateTime, $endDateTime];
        }

        if (! $date) {
            throw ValidationException::withMessages([
                'date' => 'Informe a data do evento.',
            ]);
        }

        $baseDate = Carbon::parse($date);
        $weekdayKey = strtolower($baseDate->englishDayOfWeek);

        $employeeTime = DB::table('employee_times')
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where('employee_id', $employeeId)
            ->where('weekday', $weekdayKey)
            ->first([
                'start_time',
                'end_time',
            ]);

        if (! $employeeTime || ! $employeeTime->start_time || ! $employeeTime->end_time) {
            throw ValidationException::withMessages([
                'date' => $eventType === EmployeeEventType::Compensation
                    ? 'Dia sem jornada prevista para este funcionário. Use período livre ou configure a jornada do funcionário antes de compensar.'
                    : ($eventType === EmployeeEventType::Absence
                        ? 'A falta exige um dia com jornada prevista para este funcionário.'
                        : 'Dia sem jornada prevista para este funcionário. Use período livre ou configure a jornada do funcionário.'),
            ]);
        }

        $startDateTime = Carbon::parse($date . ' ' . $employeeTime->start_time);
        $endDateTime = Carbon::parse($date . ' ' . $employeeTime->end_time);

        if ($endDateTime->lessThanOrEqualTo($startDateTime)) {
            throw ValidationException::withMessages([
                'date' => 'A jornada configurada para este funcionário neste dia é inválida.',
            ]);
        }

        return [$startDateTime, $endDateTime];
    }

    protected function ensureNoDuplicateEvent(
        int $tenantId,
        int $companyId,
        int $employeeId,
        EmployeeEventType $eventType,
        Carbon $startsAt,
        Carbon $endsAt,
        ?int $ignoreEventId = null,
    ): void {
        $exists = EmployeeEvent::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where('employee_id', $employeeId)
            ->where('event_type', $eventType->value)
            ->where('starts_at', $startsAt)
            ->where('ends_at', $endsAt)
            ->when($ignoreEventId, fn ($query) => $query->where('id', '!=', $ignoreEventId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'event_type' => 'Já existe um evento idêntico para este funcionário no mesmo período.',
            ]);
        }
    }

    protected function normalizeNotes(?string $notes): ?string
    {
        $notes = trim((string) $notes);

        return $notes !== '' ? $notes : null;
    }
}
