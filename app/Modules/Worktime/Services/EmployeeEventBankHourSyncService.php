<?php

namespace App\Modules\Worktime\Services;

use App\Models\CompanyDefaultTime;
use App\Models\User;
use App\Modules\Worktime\Enums\BankHourEntryType;
use App\Modules\Worktime\Models\BankHourEntry;
use App\Modules\Worktime\Models\EmployeeEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class EmployeeEventBankHourSyncService
{
    public function __construct(
        protected BankHourService $bankHourService,
    ) {
    }

    public function sync(EmployeeEvent $event, ?User $user = null): void
    {
        DB::transaction(function () use ($event, $user) {
            $company = DB::table('companies')
                ->where('id', $event->company_id)
                ->where('tenant_id', $event->tenant_id)
                ->first([
                    'id',
                    'uses_hour_bank',
                    'hour_bank_starts_at',
                ]);

            if (! $company || ! $company->uses_hour_bank) {
                $this->deleteLinkedEntries($event);
                return;
            }

            if (! $event->event_type?->movesBankHourImmediately()) {
                $this->deleteLinkedEntries($event);
                return;
            }

            if (
                filled($company->hour_bank_starts_at)
                && $event->starts_at
                && $event->starts_at->toDateString() < $company->hour_bank_starts_at
            ) {
                $this->deleteLinkedEntries($event);
                return;
            }

            $minutes = $this->resolveCompensationMinutes($event);

            if ($minutes <= 0) {
                $this->deleteLinkedEntries($event);
                return;
            }

            $existingEntry = BankHourEntry::query()
                ->where('source_type', $event::class)
                ->where('source_id', $event->id)
                ->first();

            if (! $existingEntry) {
                $this->bankHourService->registerEntry(
                    tenantId: $event->tenant_id,
                    companyId: $event->company_id,
                    employeeId: $event->employee_id,
                    entryType: BankHourEntryType::Compensation,
                    minutes: -$minutes,
                    occurredOn: $event->starts_at->toDateString(),
                    description: 'Débito automático por compensação.',
                    metadata: [
                        'employee_event_id' => $event->id,
                    ],
                    source: $event,
                    user: $user,
                );

                return;
            }

            $oldMinutes = (int) $existingEntry->minutes;
            $newMinutes = -$minutes;

            if (
                $existingEntry->bank_hour_account_id
                && (
                    $existingEntry->employee_id !== $event->employee_id
                    || $existingEntry->company_id !== $event->company_id
                    || $existingEntry->tenant_id !== $event->tenant_id
                )
            ) {
                DB::table('bank_hour_accounts')
                    ->where('id', $existingEntry->bank_hour_account_id)
                    ->update([
                        'current_balance_minutes' => DB::raw('current_balance_minutes - (' . $oldMinutes . ')'),
                    ]);

                $newAccount = $this->bankHourService->ensureAccount(
                    tenantId: $event->tenant_id,
                    companyId: $event->company_id,
                    employeeId: $event->employee_id,
                );

                DB::table('bank_hour_accounts')
                    ->where('id', $newAccount->id)
                    ->update([
                        'current_balance_minutes' => DB::raw('current_balance_minutes + (' . $newMinutes . ')'),
                    ]);

                $existingEntry->update([
                    'tenant_id' => $event->tenant_id,
                    'company_id' => $event->company_id,
                    'employee_id' => $event->employee_id,
                    'bank_hour_account_id' => $newAccount->id,
                    'entry_type' => BankHourEntryType::Compensation,
                    'minutes' => $newMinutes,
                    'occurred_on' => $event->starts_at->toDateString(),
                    'description' => 'Débito automático por compensação.',
                    'metadata' => [
                        'employee_event_id' => $event->id,
                    ],
                ]);

                return;
            }

            $delta = $newMinutes - $oldMinutes;

            if ($delta !== 0 && $existingEntry->bank_hour_account_id) {
                DB::table('bank_hour_accounts')
                    ->where('id', $existingEntry->bank_hour_account_id)
                    ->update([
                        'current_balance_minutes' => DB::raw('current_balance_minutes + (' . $delta . ')'),
                    ]);
            }

            $existingEntry->update([
                'entry_type' => BankHourEntryType::Compensation,
                'minutes' => $newMinutes,
                'occurred_on' => $event->starts_at->toDateString(),
                'description' => 'Débito automático por compensação.',
                'metadata' => [
                    'employee_event_id' => $event->id,
                ],
            ]);
        });
    }

    public function delete(EmployeeEvent $event): void
    {
        DB::transaction(function () use ($event) {
            $this->deleteLinkedEntries($event);
        });
    }

    protected function deleteLinkedEntries(EmployeeEvent $event): void
    {
        $entries = BankHourEntry::query()
            ->where('source_type', $event::class)
            ->where('source_id', $event->id)
            ->get();

        foreach ($entries as $entry) {
            if ($entry->bank_hour_account_id) {
                DB::table('bank_hour_accounts')
                    ->where('id', $entry->bank_hour_account_id)
                    ->update([
                        'current_balance_minutes' => DB::raw('current_balance_minutes - (' . $entry->minutes . ')'),
                    ]);
            }

            $entry->delete();
        }
    }

    protected function resolveCompensationMinutes(EmployeeEvent $event): int
    {
        if (! $event->starts_at || ! $event->ends_at) {
            return 0;
        }

        if ($event->starts_at->toDateString() !== $event->ends_at->toDateString()) {
            return max(0, $event->starts_at->diffInMinutes($event->ends_at));
        }

        $weekdayKey = strtolower($event->starts_at->englishDayOfWeek);

        $defaultTime = CompanyDefaultTime::query()
            ->where('tenant_id', $event->tenant_id)
            ->where('company_id', $event->company_id)
            ->get()
            ->first(fn (CompanyDefaultTime $time) => $time->weekday->value === $weekdayKey);

        if (! $defaultTime || ! $defaultTime->start_time || ! $defaultTime->end_time) {
            return max(0, $event->starts_at->diffInMinutes($event->ends_at));
        }

        $expectedStart = Carbon::parse($event->starts_at->format('Y-m-d') . ' ' . $defaultTime->start_time);
        $expectedEnd = Carbon::parse($event->starts_at->format('Y-m-d') . ' ' . $defaultTime->end_time);
        $breakMinutes = $this->timeToMinutes($defaultTime->break_duration);

        $fullDayMinutes = max(0, $expectedStart->diffInMinutes($expectedEnd) - $breakMinutes);

        if (
            $event->starts_at->format('H:i:s') === $expectedStart->format('H:i:s')
            && $event->ends_at->format('H:i:s') === $expectedEnd->format('H:i:s')
        ) {
            return $fullDayMinutes;
        }

        return max(0, $event->starts_at->diffInMinutes($event->ends_at));
    }

    protected function timeToMinutes(?string $time): int
    {
        if (! $time) {
            return 0;
        }

        [$hours, $minutes] = explode(':', $time);

        return ((int) $hours * 60) + (int) $minutes;
    }
}
