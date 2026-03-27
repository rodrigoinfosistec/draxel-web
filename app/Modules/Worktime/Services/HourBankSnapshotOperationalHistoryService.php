<?php

namespace App\Modules\Worktime\Services;

use App\Models\User;
use App\Modules\Worktime\Enums\BankHourEntryType;
use App\Modules\Worktime\Models\BankHourEntry;
use App\Modules\Worktime\Models\HourBankSnapshot;
use App\Modules\Worktime\Models\HourBankSnapshotEmployee;
use Illuminate\Support\Facades\DB;

class HourBankSnapshotOperationalHistoryService
{
    public function register(
        HourBankSnapshot $snapshot,
        HourBankSnapshotEmployee $snapshotEmployee,
        User $user,
    ): void {
        $bankHourAccountId = $this->resolveBankHourAccountId($snapshot, $snapshotEmployee);

        BankHourEntry::query()->updateOrCreate(
            [
                'tenant_id' => $snapshot->tenant_id,
                'company_id' => $snapshot->company_id,
                'employee_id' => $snapshotEmployee->employee_id,
                'entry_type' => BankHourEntryType::SnapshotClosure,
                'occurred_on' => $snapshot->period_end->format('Y-m-d'),
                'description' => $this->buildDescription($snapshot),
            ],
            [
                'bank_hour_account_id' => $bankHourAccountId,
                'minutes' => $snapshotEmployee->balance_minutes,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ],
        );
    }

    public function remove(HourBankSnapshot $snapshot, HourBankSnapshotEmployee $snapshotEmployee): void
    {
        $bankHourAccountId = $this->resolveBankHourAccountId($snapshot, $snapshotEmployee);

        BankHourEntry::query()
            ->where('tenant_id', $snapshot->tenant_id)
            ->where('company_id', $snapshot->company_id)
            ->where('employee_id', $snapshotEmployee->employee_id)
            ->where('bank_hour_account_id', $bankHourAccountId)
            ->where('entry_type', BankHourEntryType::SnapshotClosure)
            ->where('occurred_on', $snapshot->period_end->format('Y-m-d'))
            ->where('description', $this->buildDescription($snapshot))
            ->delete();
    }

    protected function resolveBankHourAccountId(
        HourBankSnapshot $snapshot,
        HourBankSnapshotEmployee $snapshotEmployee,
    ): int {
        $existingEntryAccountId = BankHourEntry::query()
            ->where('tenant_id', $snapshot->tenant_id)
            ->where('company_id', $snapshot->company_id)
            ->where('employee_id', $snapshotEmployee->employee_id)
            ->whereNotNull('bank_hour_account_id')
            ->value('bank_hour_account_id');

        if ($existingEntryAccountId) {
            return (int) $existingEntryAccountId;
        }

        $existingAccountId = DB::table('bank_hour_accounts')
            ->where('tenant_id', $snapshot->tenant_id)
            ->where('company_id', $snapshot->company_id)
            ->where('employee_id', $snapshotEmployee->employee_id)
            ->value('id');

        if ($existingAccountId) {
            return (int) $existingAccountId;
        }

        $createdAt = now();

        return (int) DB::table('bank_hour_accounts')->insertGetId([
            'tenant_id' => $snapshot->tenant_id,
            'company_id' => $snapshot->company_id,
            'employee_id' => $snapshotEmployee->employee_id,
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);
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
