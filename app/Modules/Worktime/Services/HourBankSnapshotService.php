<?php

namespace App\Modules\Worktime\Services;

use App\Models\Employee;
use App\Models\User;
use App\Modules\Worktime\Enums\HourBankSnapshotStatus;
use App\Modules\Worktime\Models\HourBankSnapshot;
use App\Modules\Worktime\Models\HourBankSnapshotEmployee;
use App\Modules\Worktime\Models\HourBankSnapshotEmployeeDay;
use App\Support\CompanyContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class HourBankSnapshotService
{
    public function __construct(
        protected HourBankSnapshotCaptureService $captureService,
        protected HourBankSnapshotDuplicateDateGuardService $duplicateDateGuardService,
        protected HourBankSnapshotOperationalHistoryService $operationalHistoryService,
    ) {
    }

    public function create(array $data, User $user): HourBankSnapshot
    {
        return HourBankSnapshot::create([
            'tenant_id' => $user->tenant_id,
            'company_id' => CompanyContext::id(),
            'name' => $data['name'],
            'period_start' => $data['period_start'],
            'period_end' => $data['period_end'],
            'notes' => $data['notes'] ?? null,
            'status' => HourBankSnapshotStatus::DRAFT,
            'created_by' => $user->id,
        ]);
    }

    public function addEmployees(HourBankSnapshot $snapshot, array $employeeIds, User $user): void
    {
        $this->ensureEditable($snapshot);

        DB::transaction(function () use ($snapshot, $employeeIds, $user) {
            $employees = Employee::query()
                ->where('tenant_id', $snapshot->tenant_id)
                ->where('company_id', $snapshot->company_id)
                ->whereIn('id', $employeeIds)
                ->orderBy('name')
                ->get();

            foreach ($employees as $employee) {
                $this->replaceEmployeeSnapshot(
                    snapshot: $snapshot,
                    employee: $employee,
                    user: $user,
                );
            }
        });
    }

    public function removeEmployee(HourBankSnapshot $snapshot, HourBankSnapshotEmployee $snapshotEmployee): void
    {
        $this->ensureEditable($snapshot);
        $this->ensureEmployeeBelongsToSnapshot($snapshot, $snapshotEmployee);

        $snapshotEmployee->delete();
    }

    public function delete(HourBankSnapshot $snapshot): void
    {
        $this->ensureEditable($snapshot);

        $snapshot->delete();
    }

    public function validateConsolidation(HourBankSnapshot $snapshot): array
    {
        $snapshot->loadMissing('employees.days');

        $errors = [];

        if ($snapshot->employees->isEmpty()) {
            $errors[] = 'Adicione ao menos um funcionário ao fechamento.';
        }

        if ($snapshot->employees->contains(fn (HourBankSnapshotEmployee $item) => $item->has_divergence)) {
            $errors[] = 'Existem funcionários com divergência pendente.';
        }

        $duplicateDates = $this->duplicateDateGuardService->findConflicts($snapshot);

        if (! empty($duplicateDates)) {
            $errors[] = 'Existem datas já consolidadas para um ou mais funcionários.';
        }

        return [
            'can_consolidate' => empty($errors),
            'errors' => array_values(array_unique($errors)),
            'duplicate_dates' => $duplicateDates,
        ];
    }

    public function consolidate(HourBankSnapshot $snapshot, User $user): void
    {
        $this->ensureEditable($snapshot);

        $validation = $this->validateConsolidation($snapshot);

        if (! $validation['can_consolidate']) {
            throw ValidationException::withMessages([
                'snapshot' => $validation['errors'],
            ]);
        }

        DB::transaction(function () use ($snapshot, $user) {
            $snapshot->loadMissing('employees.days');

            foreach ($snapshot->employees as $snapshotEmployee) {
                $this->operationalHistoryService->register(
                    snapshot: $snapshot,
                    snapshotEmployee: $snapshotEmployee,
                    user: $user,
                );
            }

            $snapshot->update([
                'status' => HourBankSnapshotStatus::CONSOLIDATED,
                'consolidated_by' => $user->id,
                'consolidated_at' => now(),
            ]);
        });
    }

    public function reverse(HourBankSnapshot $snapshot, User $user, string $reason): void
    {
        if (! $snapshot->status->isConsolidated()) {
            throw ValidationException::withMessages([
                'snapshot' => 'Apenas fechamentos consolidados podem ser revertidos.',
            ]);
        }

        if (blank($reason)) {
            throw ValidationException::withMessages([
                'reason' => 'Informe o motivo da reversão.',
            ]);
        }

        DB::transaction(function () use ($snapshot, $user, $reason) {
            $snapshot->loadMissing('employees');

            foreach ($snapshot->employees as $snapshotEmployee) {
                $this->operationalHistoryService->remove(
                    snapshot: $snapshot,
                    snapshotEmployee: $snapshotEmployee,
                );
            }

            $snapshot->update([
                'status' => HourBankSnapshotStatus::REVERSED,
                'reversed_by' => $user->id,
                'reversed_at' => now(),
                'reversal_reason' => trim($reason),
            ]);
        });
    }

    protected function replaceEmployeeSnapshot(
        HourBankSnapshot $snapshot,
        Employee $employee,
        User $user,
    ): void {
        HourBankSnapshotEmployee::query()
            ->where('hour_bank_snapshot_id', $snapshot->id)
            ->where('employee_id', $employee->id)
            ->delete();

        $captured = $this->captureService->capture(
            employee: $employee,
            periodStart: $snapshot->period_start->format('Y-m-d'),
            periodEnd: $snapshot->period_end->format('Y-m-d'),
        );

        $snapshotEmployee = HourBankSnapshotEmployee::create([
            'tenant_id' => $snapshot->tenant_id,
            'company_id' => $snapshot->company_id,
            'hour_bank_snapshot_id' => $snapshot->id,
            'employee_id' => $employee->id,
            'employee_name' => $captured['employee_name'],
            'employee_registration' => $captured['employee_registration'],
            'justified_minutes' => $captured['totals']['justified_minutes'],
            'late_minutes' => $captured['totals']['late_minutes'],
            'extra_minutes' => $captured['totals']['extra_minutes'],
            'absence_minutes' => $captured['totals']['absence_minutes'],
            'suspension_minutes' => $captured['totals']['suspension_minutes'],
            'balance_minutes' => $captured['totals']['balance_minutes'],
            'has_divergence' => $captured['totals']['has_divergence'],
            'divergence_summary' => $captured['totals']['divergence_summary'],
            'captured_at' => $captured['captured_at'],
            'captured_by' => $user->id,
        ]);

        $this->storeEmployeeDays(
            snapshot: $snapshot,
            snapshotEmployee: $snapshotEmployee,
            employee: $employee,
            days: collect($captured['days']),
        );
    }

    protected function storeEmployeeDays(
        HourBankSnapshot $snapshot,
        HourBankSnapshotEmployee $snapshotEmployee,
        Employee $employee,
        Collection $days,
    ): void {
        foreach ($days as $day) {
            HourBankSnapshotEmployeeDay::create([
                'tenant_id' => $snapshot->tenant_id,
                'company_id' => $snapshot->company_id,
                'hour_bank_snapshot_id' => $snapshot->id,
                'hour_bank_snapshot_employee_id' => $snapshotEmployee->id,
                'employee_id' => $employee->id,
                'work_date' => $day['work_date'],
                'weekday' => $day['weekday'],
                'weekday_label' => $day['weekday_label'],
                'expected_start_time' => $day['expected_start_time'],
                'expected_end_time' => $day['expected_end_time'],
                'expected_break_duration' => $day['expected_break_duration'],
                'records' => $day['records'],
                'justified_minutes' => $day['justified_minutes'],
                'late_minutes' => $day['late_minutes'],
                'extra_minutes' => $day['extra_minutes'],
                'absence_minutes' => $day['absence_minutes'],
                'suspension_minutes' => $day['suspension_minutes'],
                'balance_minutes' => $day['balance_minutes'],
                'has_divergence' => $day['has_divergence'],
                'divergence_reason' => $day['divergence_reason'],
                'notes' => $day['notes'],
            ]);
        }
    }

    protected function ensureEditable(HourBankSnapshot $snapshot): void
    {
        if (! $snapshot->status->isEditable()) {
            throw ValidationException::withMessages([
                'snapshot' => 'Este fechamento não pode mais ser alterado.',
            ]);
        }
    }

    protected function ensureEmployeeBelongsToSnapshot(
        HourBankSnapshot $snapshot,
        HourBankSnapshotEmployee $snapshotEmployee,
    ): void {
        if ((int) $snapshotEmployee->hour_bank_snapshot_id !== (int) $snapshot->id) {
            throw ValidationException::withMessages([
                'snapshot_employee' => 'O funcionário informado não pertence a este fechamento.',
            ]);
        }
    }
}
