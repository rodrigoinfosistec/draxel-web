<?php

namespace App\Modules\Worktime\Services;

use App\Models\User;
use App\Modules\Worktime\Enums\ClockRecordSourceType;
use App\Modules\Worktime\Models\ClockRecord;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ClockRecordService
{
    public function createManualBatch(array $data, User $user): Collection
    {
        $companyId = session('current_company_id');
        $date = $data['date'];
        $employeeId = $data['employee_id'];

        return collect($data['rows'])->map(function (array $row) use ($user, $companyId, $date, $employeeId) {
            $recordedAt = $this->makeRecordedAt($date, $row['time']);
            $sourceHash = $this->makeSourceHash(
                sourceType: ClockRecordSourceType::Manual,
                tenantId: $user->tenant_id,
                companyId: $companyId,
                employeeId: $employeeId,
                recordedAt: $recordedAt,
            );

            return ClockRecord::query()->firstOrCreate(
                [
                    'tenant_id' => $user->tenant_id,
                    'source_hash' => $sourceHash,
                ],
                [
                    'company_id' => $companyId,
                    'employee_id' => $employeeId,
                    'tenant_clock_device_id' => null,
                    'source_type' => ClockRecordSourceType::Manual,
                    'recorded_at' => $recordedAt,
                    'notes' => $row['notes'] ?? null,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ],
            );
        });
    }

    public function updateManual(ClockRecord $clockRecord, array $data, User $user): ClockRecord
    {
        $recordedAt = $this->makeRecordedAt($data['date'], $data['time']);
        $sourceHash = $this->makeSourceHash(
            sourceType: ClockRecordSourceType::Manual,
            tenantId: $user->tenant_id,
            companyId: session('current_company_id'),
            employeeId: $data['employee_id'],
            recordedAt: $recordedAt,
        );

        $clockRecord->update([
            'employee_id' => $data['employee_id'],
            'source_type' => ClockRecordSourceType::Manual,
            'source_hash' => $sourceHash,
            'recorded_at' => $recordedAt,
            'notes' => $data['notes'] ?? null,
            'updated_by' => $user->id,
        ]);

        return $clockRecord->refresh();
    }

    protected function makeRecordedAt(string $date, string $time): Carbon
    {
        return Carbon::parse($date . ' ' . $time);
    }

    protected function makeSourceHash(
        ClockRecordSourceType $sourceType,
        int $tenantId,
        int $companyId,
        int $employeeId,
        Carbon $recordedAt,
    ): string {
        return sha1(implode('|', [
            $sourceType->value,
            $tenantId,
            $companyId,
            $employeeId,
            $recordedAt->format('Y-m-d H:i:s'),
        ]));
    }
}
