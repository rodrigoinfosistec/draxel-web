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

    public function registerManualEntry(
        int $tenantId,
        int $companyId,
        int $employeeId,
        string $entryType,
        string $hours,
        string $occurredOn,
        ?string $description = null,
        ?User $user = null,
    ): BankHourEntry {
        $minutes = $this->hoursToSignedMinutes($hours, $entryType);

        return $this->registerEntry(
            tenantId: $tenantId,
            companyId: $companyId,
            employeeId: $employeeId,
            entryType: BankHourEntryType::from($entryType),
            minutes: $minutes,
            occurredOn: $occurredOn,
            description: $description,
            metadata: [
                'manual_entry' => true,
            ],
            source: null,
            user: $user,
        );
    }

    public function updateManualEntry(
        BankHourEntry $entry,
        string $entryType,
        string $hours,
        string $occurredOn,
        ?string $description = null,
    ): BankHourEntry {
        if (! in_array($entry->entry_type?->value, [
            BankHourEntryType::ManualCredit->value,
            BankHourEntryType::ManualDebit->value,
        ], true)) {
            throw new InvalidArgumentException('Apenas lançamentos manuais podem ser editados.');
        }

        $newMinutes = $this->hoursToSignedMinutes($hours, $entryType);
        $oldMinutes = (int) $entry->minutes;
        $delta = $newMinutes - $oldMinutes;

        return DB::transaction(function () use ($entry, $entryType, $newMinutes, $delta, $occurredOn, $description) {
            $entry->update([
                'entry_type' => BankHourEntryType::from($entryType),
                'minutes' => $newMinutes,
                'occurred_on' => $occurredOn,
                'description' => $description,
            ]);

            if ($delta !== 0) {
                $entry->account()->update([
                    'current_balance_minutes' => DB::raw('current_balance_minutes + (' . $delta . ')'),
                ]);
            }

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

    public function absoluteMinutesToHours(int $minutes): string
    {
        $minutes = abs($minutes);

        $hours = intdiv($minutes, 60);
        $remainingMinutes = $minutes % 60;

        return str_pad((string) $hours, 2, '0', STR_PAD_LEFT)
            . ':'
            . str_pad((string) $remainingMinutes, 2, '0', STR_PAD_LEFT);
    }

    protected function hoursToSignedMinutes(string $hours, string $entryType): int
    {
        [$h, $m] = explode(':', $hours);

        $minutes = ((int) $h * 60) + (int) $m;

        if ($minutes <= 0) {
            throw new InvalidArgumentException('A quantidade de horas deve ser maior que zero.');
        }

        return $entryType === BankHourEntryType::ManualDebit->value
            ? -$minutes
            : $minutes;
    }
}
