<?php

namespace App\Modules\Worktime\Services;

use App\Models\User;
use App\Modules\Worktime\Enums\ClockRecordImportItemStatus;
use App\Modules\Worktime\Enums\ClockRecordImportStatus;
use App\Modules\Worktime\Enums\ClockRecordSourceType;
use App\Modules\Worktime\Factories\ClockDeviceParserFactory;
use App\Modules\Worktime\Models\ClockRecord;
use App\Modules\Worktime\Models\ClockRecordImport;
use App\Modules\Worktime\Models\ClockRecordImportItem;
use App\Modules\Worktime\Models\TenantClockDevice;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ClockRecordImportService
{
    public function __construct(
        protected ClockDeviceParserFactory $parserFactory,
    ) {
    }

    public function createFromUpload(array $data, User $user): ClockRecordImport
    {
        return DB::transaction(function () use ($data, $user) {
            /** @var UploadedFile $file */
            $file = $data['file'];

            $content = $file->get();

            $fileHash = sha1($content);

            $tenantClockDevice = TenantClockDevice::query()
                ->with('clockDevice')
                ->where('tenant_id', $user->tenant_id)
                ->findOrFail($data['tenant_clock_device_id']);

            $storedPath = $file->store('clock-record-imports');

            $import = ClockRecordImport::query()->create([
                'tenant_id' => $user->tenant_id,
                'company_id' => session('current_company_id'),
                'tenant_clock_device_id' => $tenantClockDevice->id,
                'source_type' => ClockRecordSourceType::File,
                'status' => ClockRecordImportStatus::Processing,
                'original_filename' => $file->getClientOriginalName(),
                'stored_path' => $storedPath,
                'file_hash' => $fileHash,
                'imported_by' => $user->id,
            ]);

            $parser = $this->parserFactory->make($tenantClockDevice);

            $parser->validate($content);

            $identifierColumn = config('worktime.employee_identifier_column', 'registration');

            $parsedItems = collect($parser->parse($content))->map(function (array $parsedItem) use ($import, $identifierColumn) {
                $employeeId = $this->resolveEmployeeId(
                    employeeCode: $parsedItem['employee_code'],
                    tenantId: $import->tenant_id,
                    companyId: $import->company_id,
                    identifierColumn: $identifierColumn,
                );

                $status = ClockRecordImportItemStatus::Valid;
                $divergenceReason = null;

                if (! $parsedItem['recorded_at']) {
                    $status = ClockRecordImportItemStatus::Invalid;
                    $divergenceReason = 'Data/hora inválida.';
                } elseif (! $employeeId) {
                    $status = ClockRecordImportItemStatus::Invalid;
                    $divergenceReason = 'Funcionário não encontrado.';
                }

                $recordHash = $parsedItem['recorded_at']
                    ? sha1(implode('|', [
                        $import->tenant_id,
                        $import->company_id,
                        $parsedItem['employee_code'],
                        $parsedItem['recorded_at']->format('Y-m-d H:i:s'),
                    ]))
                    : null;

                return ClockRecordImportItem::query()->create([
                    'clock_record_import_id' => $import->id,
                    'line_number' => $parsedItem['line_number'],
                    'raw_line' => $parsedItem['raw_line'],
                    'employee_code' => $parsedItem['employee_code'],
                    'employee_id' => $employeeId,
                    'recorded_at' => $parsedItem['recorded_at'],
                    'status' => $status,
                    'record_hash' => $recordHash,
                    'divergence_reason' => $divergenceReason,
                    'payload' => $parsedItem['payload'],
                ]);
            });

            $this->markOddGroups($import);

            $import->load('items');

            $validItems = $import->items->where('status', ClockRecordImportItemStatus::Valid)->count();
            $invalidItems = $import->items->where('status', ClockRecordImportItemStatus::Invalid)->count();

            $status = $import->items->isEmpty()
                ? ClockRecordImportStatus::Failed
                : ($invalidItems > 0 ? ClockRecordImportStatus::AwaitingReview : ClockRecordImportStatus::ReadyToLaunch);

            $import->update([
                'status' => $status,
                'total_items' => $import->items->count(),
                'valid_items' => $validItems,
                'invalid_items' => $invalidItems,
                'processed_at' => now(),
            ]);

            return $import->fresh(['items.employee', 'tenantClockDevice.clockDevice']);
        });
    }

    public function launch(ClockRecordImport $import, User $user): ClockRecordImport
    {
        return DB::transaction(function () use ($import, $user) {
            $import->loadMissing('items');

            abort_unless($import->invalid_items === 0 && $import->valid_items > 0, 422, 'A importação ainda possui divergências.');

            $items = $import->items()
                ->whereIn('status', [
                    ClockRecordImportItemStatus::Valid,
                    ClockRecordImportItemStatus::Resolved,
                ])
                ->get();

            foreach ($items as $item) {
                $clockRecord = ClockRecord::query()->firstOrCreate(
                    [
                        'tenant_id' => $import->tenant_id,
                        'source_hash' => sha1(implode('|', [
                            'file',
                            $import->id,
                            $item->id,
                            $item->recorded_at?->format('Y-m-d H:i:s'),
                        ])),
                    ],
                    [
                        'company_id' => $import->company_id,
                        'employee_id' => $item->employee_id,
                        'tenant_clock_device_id' => $import->tenant_clock_device_id,
                        'source_type' => ClockRecordSourceType::File,
                        'recorded_at' => $item->recorded_at,
                        'notes' => null,
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                    ],
                );

                $item->update([
                    'status' => ClockRecordImportItemStatus::Launched,
                    'launched_clock_record_id' => $clockRecord->id,
                ]);
            }

            $import->update([
                'status' => ClockRecordImportStatus::Launched,
                'launched_at' => now(),
            ]);

            return $import->fresh(['items.employee', 'tenantClockDevice.clockDevice']);
        });
    }

    protected function markOddGroups(ClockRecordImport $import): void
    {
        $groups = ClockRecordImportItem::query()
            ->where('clock_record_import_id', $import->id)
            ->whereNotNull('employee_id')
            ->whereNotNull('recorded_at')
            ->get()
            ->groupBy(fn (ClockRecordImportItem $item) => $item->employee_id . '|' . $item->recorded_at?->format('Y-m-d'));

        foreach ($groups as $items) {
            if ($items->count() % 2 !== 0) {
                foreach ($items as $item) {
                    $item->update([
                        'status' => ClockRecordImportItemStatus::Invalid,
                        'divergence_reason' => 'Quantidade ímpar de registros no dia.',
                    ]);
                }
            }
        }
    }

    protected function resolveEmployeeId(
        ?string $employeeCode,
        int $tenantId,
        int $companyId,
        string $identifierColumn,
    ): ?int {
        if (! $employeeCode || ! Schema::hasColumn('employees', $identifierColumn)) {
            return null;
        }

        return DB::table('employees')
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where($identifierColumn, $employeeCode)
            ->value('id');
    }
}
