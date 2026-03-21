<?php

namespace App\Modules\Worktime\Services;

use App\Models\User;
use App\Modules\Worktime\Enums\BankHourEntryType;
use App\Modules\Worktime\Models\BankHourAccount;
use App\Modules\Worktime\Models\BankHourEntry;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class BankHourService
{
    public function ensureAccount(
        int $tenantId,
        int $companyId,
        int $employeeId,
    ): BankHourAccount {
        return BankHourAccount::query()->firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'company_id' => $companyId,
                'employee_id' => $employeeId,
            ],
            [
                'current_balance_minutes' => 0,
            ],
        );
    }

    public function registerEntry(
        int $tenantId,
        int $companyId,
        int $employeeId,
        BankHourEntryType $entryType,
        int $minutes,
        string $occurredOn,
        ?string $description = null,
        ?array $metadata = null,
        ?Model $source = null,
        ?User $user = null,
    ): BankHourEntry {
        if ($minutes === 0) {
            throw new InvalidArgumentException('O movimento de banco de horas não pode ser zero.');
        }

        return DB::transaction(function () use (
            $tenantId,
            $companyId,
            $employeeId,
            $entryType,
            $minutes,
            $occurredOn,
            $description,
            $metadata,
            $source,
            $user,
        ) {
            $account = $this->ensureAccount(
                tenantId: $tenantId,
                companyId: $companyId,
                employeeId: $employeeId,
            );

            $entry = BankHourEntry::query()->create([
                'tenant_id' => $tenantId,
                'company_id' => $companyId,
                'employee_id' => $employeeId,
                'bank_hour_account_id' => $account->id,
                'entry_type' => $entryType,
                'minutes' => $minutes,
                'occurred_on' => $occurredOn,
                'description' => $description,
                'metadata' => $metadata,
                'source_type' => $source ? $source::class : null,
                'source_id' => $source?->getKey(),
                'created_by' => $user?->id,
            ]);

            $account->update([
                'current_balance_minutes' => $account->current_balance_minutes + $minutes,
            ]);

            return $entry->fresh(['employee', 'account']);
        });
    }

    public function rebuildAccountBalance(BankHourAccount $account): BankHourAccount
    {
        $balance = BankHourEntry::query()
            ->where('bank_hour_account_id', $account->id)
            ->sum('minutes');

        $account->update([
            'current_balance_minutes' => $balance,
        ]);

        return $account->fresh();
    }

    public function formatMinutes(int $minutes): string
    {
        $negative = $minutes < 0;
        $minutes = abs($minutes);

        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        return ($negative ? '-' : '+')
            . str_pad((string) $hours, 2, '0', STR_PAD_LEFT)
            . ':'
            . str_pad((string) $remainingMinutes, 2, '0', STR_PAD_LEFT);
    }
}
