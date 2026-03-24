<?php

namespace App\Modules\Worktime\Services;

use App\Modules\Worktime\Enums\HourBankSnapshotStatus;
use App\Modules\Worktime\Models\HourBankSnapshot;
use App\Modules\Worktime\Models\HourBankSnapshotEmployeeDay;
use Illuminate\Support\Collection;

class HourBankSnapshotDuplicateDateGuardService
{
    public function findConflicts(HourBankSnapshot $snapshot): array
    {
        $rows = HourBankSnapshotEmployeeDay::query()
            ->select([
                'hour_bank_snapshot_employee_days.employee_id',
                'hour_bank_snapshot_employee_days.work_date',
            ])
            ->join(
                'hour_bank_snapshots',
                'hour_bank_snapshots.id',
                '=',
                'hour_bank_snapshot_employee_days.hour_bank_snapshot_id'
            )
            ->where('hour_bank_snapshot_employee_days.tenant_id', $snapshot->tenant_id)
            ->where('hour_bank_snapshot_employee_days.company_id', $snapshot->company_id)
            ->where('hour_bank_snapshots.status', HourBankSnapshotStatus::CONSOLIDATED->value)
            ->where('hour_bank_snapshot_employee_days.hour_bank_snapshot_id', '!=', $snapshot->id)
            ->whereExists(function ($query) use ($snapshot) {
                $query->selectRaw('1')
                    ->from('hour_bank_snapshot_employee_days as current_days')
                    ->join(
                        'hour_bank_snapshot_employees as current_employees',
                        'current_employees.id',
                        '=',
                        'current_days.hour_bank_snapshot_employee_id'
                    )
                    ->whereColumn('current_days.employee_id', 'hour_bank_snapshot_employee_days.employee_id')
                    ->whereColumn('current_days.work_date', 'hour_bank_snapshot_employee_days.work_date')
                    ->where('current_employees.hour_bank_snapshot_id', $snapshot->id);
            })
            ->orderBy('hour_bank_snapshot_employee_days.employee_id')
            ->orderBy('hour_bank_snapshot_employee_days.work_date')
            ->get();

        return $rows
            ->groupBy('employee_id')
            ->map(fn (Collection $items) => $items
                ->pluck('work_date')
                ->map(fn ($date) => date('d/m/Y', strtotime($date)))
                ->values()
                ->all())
            ->toArray();
    }
}
