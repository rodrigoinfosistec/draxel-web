<?php

namespace App\Modules\Worktime\Services;

use App\Models\User;
use App\Modules\Worktime\Models\BankHourEntry;
use App\Modules\Worktime\Models\HourBankSnapshot;
use App\Modules\Worktime\Models\HourBankSnapshotEmployee;

class HourBankSnapshotOperationalHistoryService
{
    public function register(
        HourBankSnapshot $snapshot,
        HourBankSnapshotEmployee $snapshotEmployee,
        User $user,
    ): void {
        BankHourEntry::query()->updateOrCreate(
            [
                'tenant_id' => $snapshot->tenant_id,
                'company_id' => $snapshot->company_id,
                'employee_id' => $snapshotEmployee->employee_id,
                'entry_type' => 'snapshot_closure',
                'occurred_on' => $snapshot->period_end->format('Y-m-d'),
                'description' => $this->buildDescription($snapshot),
            ],
            [
                'minutes' => $snapshotEmployee->final_balance_minutes,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ],
        );
    }

    public function remove(HourBankSnapshot $snapshot, HourBankSnapshotEmployee $snapshotEmployee): void
    {
        BankHourEntry::query()
            ->where('tenant_id', $snapshot->tenant_id)
            ->where('company_id', $snapshot->company_id)
            ->where('employee_id', $snapshotEmployee->employee_id)
            ->where('entry_type', 'snapshot_closure')
            ->where('occurred_on', $snapshot->period_end->format('Y-m-d'))
            ->where('description', $this->buildDescription($snapshot))
            ->delete();
    }

    protected function buildDescription(HourBankSnapshot $snapshot): string
    {
        return sprintf(
            'Saldo consolidado do período de %s a %s',
            $snapshot->period_start->format('d/m/Y'),
            $snapshot->period_end->format('d/m/Y')
        );
    }
}
