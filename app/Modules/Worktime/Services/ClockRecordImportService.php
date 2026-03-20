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
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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

            $companyId = session('current_company_id');
            $content = $file->get();
            $fileHash = sha1($content);

            $tenantClockDevice = TenantClockDevice::query()
                ->with('clockDevice')
                ->where('tenant_id', $user->tenant_id)
                ->findOrFail($data['tenant_clock_device_id']);

            $alreadyExists = ClockRecordImport::query()
                ->where('tenant_id', $user->tenant_id)
                ->where('company_id', $companyId)
                ->where('file_hash', $fileHash)
                ->exists();

            if ($alreadyExists) {
                throw ValidationException::withMessages([
                    'file' => 'Este arquivo já foi importado para a empresa atual.',
                ]);
            }

            $parser = $this->parserFactory->make($tenantClockDevice);
            $parser->validate($content);

            $storedPath = null;

            try {
                $storedPath = $file->store('clock-record-imports');

                $import = ClockRecordImport::query()->create([
                    'tenant_id' => $user->tenant_id,
                    'company_id' => $companyId,
                    'tenant_clock_device_id' => $tenantClockDevice->id,
                    'source_type' => ClockRecordSourceType::File,
                    'status' => ClockRecordImportStatus::Processing,
                    'original_filename' => $file->getClientOriginalName(),
                    'stored_path' => $storedPath,
                    'file_hash' => $fileHash,
                    'imported_by' => $user->id,
                ]);

                $identifierColumn = config('worktime.employee_identifier_column', 'registration');

                collect($parser->parse($content))->each(function (array $parsedItem) use ($import, $identifierColumn) {
                    $employeeId = $this->resolveEmployeeId(
                        employeeCode: $parsedItem['employee_code'] ?? null,
                        tenantId: $import->tenant_id,
                        companyId: $import->company_id,
                        identifierColumn: $identifierColumn,
                    );

                    $status = ClockRecordImportItemStatus::Valid;
                    $divergenceReason = null;

                    if (! ($parsedItem['recorded_at'] ?? null)) {
                        $status = ClockRecordImportItemStatus::Invalid;
                        $divergenceReason = 'Data/hora inválida.';
                    } elseif (! $employeeId) {
                        $status = ClockRecordImportItemStatus::Invalid;
                        $divergenceReason = 'Funcionário não encontrado.';
                    }

                    $recordHash = $this->makeRecordHash(
                        tenantId: $import->tenant_id,
                        companyId: $import->company_id,
                        employeeId: $employeeId,
                        recordedAt: $parsedItem['recorded_at'] ?? null,
                    );

                    ClockRecordImportItem::query()->create([
                        'clock_record_import_id' => $import->id,
                        'line_number' => $parsedItem['line_number'],
                        'raw_line' => $parsedItem['raw_line'],
                        'employee_code' => $parsedItem['employee_code'] ?? null,
                        'employee_id' => $employeeId,
                        'recorded_at' => $parsedItem['recorded_at'] ?? null,
                        'status' => $status,
                        'record_hash' => $recordHash,
                        'divergence_reason' => $divergenceReason,
                        'payload' => $parsedItem['payload'] ?? null,
                    ]);
                });

                $this->recalculateImport($import);

                return $import->fresh(['items.employee', 'tenantClockDevice.clockDevice']);
            } catch (\Throwable $e) {
                if ($storedPath) {
                    Storage::delete($storedPath);
                }

                throw $e;
            }
        });
    }

    public function ignoreItem(
        ClockRecordImport $import,
        ClockRecordImportItem $item,
        User $user,
    ): ClockRecordImport {
        return DB::transaction(function () use ($import, $item, $user) {
            abort_unless($import->tenant_id === $user->tenant_id, 404);
            abort_unless($import->company_id === session('current_company_id'), 404);
            abort_unless($item->clock_record_import_id === $import->id, 404);
            abort_unless($import->status !== ClockRecordImportStatus::Launched, 422, 'A importação já foi lançada.');
            abort_unless($item->status === ClockRecordImportItemStatus::Invalid, 422, 'Apenas itens divergentes podem ser desconsiderados.');

            $item->update([
                'status' => ClockRecordImportItemStatus::Ignored,
                'divergence_reason' => 'Item desconsiderado manualmente.',
                'launched_clock_record_id' => null,
            ]);

            $this->recalculateImport($import);

            return $import->fresh(['items.employee', 'tenantClockDevice.clockDevice']);
        });
    }

    public function resolveEmployeeForItem(
        ClockRecordImport $import,
        ClockRecordImportItem $item,
        int $employeeId,
        User $user,
    ): ClockRecordImport {
        return DB::transaction(function () use ($import, $item, $employeeId, $user) {
            abort_unless($import->tenant_id === $user->tenant_id, 404);
            abort_unless($import->company_id === session('current_company_id'), 404);
            abort_unless($item->clock_record_import_id === $import->id, 404);
            abort_unless($import->status !== ClockRecordImportStatus::Launched, 422, 'A importação já foi lançada.');
            abort_unless($item->status === ClockRecordImportItemStatus::Invalid, 422, 'Apenas itens divergentes podem ser corrigidos.');
            abort_unless($item->divergence_reason === 'Funcionário não encontrado.', 422, 'Este item não permite vinculação manual de funcionário.');
            abort_unless($item->recorded_at !== null, 422, 'O item não possui data/hora válida para vinculação.');

            $employee = DB::table('employees')
                ->where('id', $employeeId)
                ->where('tenant_id', $import->tenant_id)
                ->where('company_id', $import->company_id)
                ->first();

            abort_unless($employee, 422, 'Funcionário inválido para a empresa atual.');

            $item->update([
                'employee_id' => $employeeId,
                'status' => ClockRecordImportItemStatus::Resolved,
                'record_hash' => $this->makeRecordHash(
                    tenantId: $import->tenant_id,
                    companyId: $import->company_id,
                    employeeId: $employeeId,
                    recordedAt: $item->recorded_at,
                ),
                'divergence_reason' => null,
                'launched_clock_record_id' => null,
            ]);

            $this->recalculateImport($import);

            return $import->fresh(['items.employee', 'tenantClockDevice.clockDevice']);
        });
    }

    public function adjustGroupTimes(
        ClockRecordImport $import,
        int $employeeId,
        string $date,
        array $times,
        User $user,
    ): ClockRecordImport {
        return DB::transaction(function () use ($import, $employeeId, $date, $times, $user) {
            abort_unless($import->tenant_id === $user->tenant_id, 404);
            abort_unless($import->company_id === session('current_company_id'), 404);
            abort_unless($import->status !== ClockRecordImportStatus::Launched, 422, 'A importação já foi lançada.');

            $employee = DB::table('employees')
                ->where('id', $employeeId)
                ->where('tenant_id', $import->tenant_id)
                ->where('company_id', $import->company_id)
                ->first();

            abort_unless($employee, 422, 'Funcionário inválido para a empresa atual.');

            $dateObject = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();

            $normalizedTimes = collect($times)
                ->map(fn ($time) => is_string($time) ? trim($time) : null)
                ->filter(fn ($time) => filled($time))
                ->map(function (string $time) use ($dateObject) {
                    return Carbon::createFromFormat('Y-m-d H:i', $dateObject->format('Y-m-d') . ' ' . $time);
                })
                ->sortBy(fn (Carbon $dateTime) => $dateTime->format('Y-m-d H:i:s'))
                ->values();

            if ($normalizedTimes->isEmpty()) {
                throw ValidationException::withMessages([
                    'times' => 'Informe ao menos um horário.',
                ]);
            }

            $existingItems = ClockRecordImportItem::query()
                ->where('clock_record_import_id', $import->id)
                ->where('employee_id', $employeeId)
                ->whereDate('recorded_at', $dateObject->format('Y-m-d'))
                ->whereIn('status', [
                    ClockRecordImportItemStatus::Valid,
                    ClockRecordImportItemStatus::Invalid,
                    ClockRecordImportItemStatus::Resolved,
                    ClockRecordImportItemStatus::Ignored,
                ])
                ->orderBy('recorded_at')
                ->orderBy('id')
                ->get();

            if ($existingItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'times' => 'Não há grupo disponível para ajuste nesta data.',
                ]);
            }

            $reusableItems = $existingItems
                ->whereNotIn('status', [
                    ClockRecordImportItemStatus::Launched,
                ])
                ->values();

            $this->applyTimeAdjustments(
                import: $import,
                employeeId: $employeeId,
                dateTimes: $normalizedTimes,
                existingItems: $reusableItems,
            );

            $this->recalculateImport($import);

            return $import->fresh(['items.employee', 'tenantClockDevice.clockDevice']);
        });
    }

    public function launch(ClockRecordImport $import, User $user): ClockRecordImport
    {
        return DB::transaction(function () use ($import, $user) {
            $import->loadMissing('items');

            $hasInvalidItems = $import->items()
                ->where('status', ClockRecordImportItemStatus::Invalid)
                ->exists();

            if ($hasInvalidItems) {
                abort(422, 'A importação ainda possui divergências.');
            }

            $items = $import->items()
                ->whereIn('status', [
                    ClockRecordImportItemStatus::Valid,
                    ClockRecordImportItemStatus::Resolved,
                ])
                ->whereNotNull('employee_id')
                ->whereNotNull('recorded_at')
                ->whereNotNull('record_hash')
                ->get();

            if ($items->isEmpty()) {
                abort(422, 'A importação não possui itens válidos para lançamento.');
            }

            foreach ($items as $item) {
                $clockRecord = ClockRecord::query()->firstOrCreate(
                    [
                        'tenant_id' => $import->tenant_id,
                        'source_hash' => $item->record_hash,
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

    protected function applyTimeAdjustments(
        ClockRecordImport $import,
        int $employeeId,
        Collection $dateTimes,
        Collection $existingItems,
    ): void {
        $existingItems = $existingItems->values();

        foreach ($dateTimes as $index => $dateTime) {
            $item = $existingItems->get($index);

            if ($item) {
                $item->update([
                    'employee_id' => $employeeId,
                    'recorded_at' => $dateTime,
                    'status' => ClockRecordImportItemStatus::Resolved,
                    'record_hash' => $this->makeRecordHash(
                        tenantId: $import->tenant_id,
                        companyId: $import->company_id,
                        employeeId: $employeeId,
                        recordedAt: $dateTime,
                    ),
                    'divergence_reason' => null,
                    'launched_clock_record_id' => null,
                ]);

                continue;
            }

            ClockRecordImportItem::query()->create([
                'clock_record_import_id' => $import->id,
                'line_number' => 0,
                'raw_line' => 'Ajuste manual de horário',
                'employee_code' => null,
                'employee_id' => $employeeId,
                'recorded_at' => $dateTime,
                'status' => ClockRecordImportItemStatus::Resolved,
                'record_hash' => $this->makeRecordHash(
                    tenantId: $import->tenant_id,
                    companyId: $import->company_id,
                    employeeId: $employeeId,
                    recordedAt: $dateTime,
                ),
                'divergence_reason' => null,
                'payload' => [
                    'manual_adjustment' => true,
                ],
            ]);
        }

        if ($existingItems->count() > $dateTimes->count()) {
            $existingItems
                ->slice($dateTimes->count())
                ->each(function (ClockRecordImportItem $item) {
                    $item->update([
                        'status' => ClockRecordImportItemStatus::Ignored,
                        'divergence_reason' => 'Item removido em ajuste manual de horários.',
                        'launched_clock_record_id' => null,
                    ]);
                });
        }
    }

    protected function recalculateImport(ClockRecordImport $import): void
    {
        $this->clearAutoOddDivergences($import);
        $this->markOddGroups($import);

        $import->load('items');

        $launchableItems = $import->items
            ->whereIn('status', [
                ClockRecordImportItemStatus::Valid,
                ClockRecordImportItemStatus::Resolved,
            ])
            ->count();

        $invalidItems = $import->items
            ->where('status', ClockRecordImportItemStatus::Invalid)
            ->count();

        $status = match (true) {
            $import->items->isEmpty() => ClockRecordImportStatus::Failed,
            $launchableItems === 0 => ClockRecordImportStatus::Failed,
            $invalidItems > 0 => ClockRecordImportStatus::AwaitingReview,
            default => ClockRecordImportStatus::ReadyToLaunch,
        };

        $import->update([
            'status' => $status,
            'total_items' => $import->items->count(),
            'valid_items' => $launchableItems,
            'invalid_items' => $invalidItems,
            'processed_at' => now(),
        ]);
    }

    protected function clearAutoOddDivergences(ClockRecordImport $import): void
    {
        ClockRecordImportItem::query()
            ->where('clock_record_import_id', $import->id)
            ->where('status', ClockRecordImportItemStatus::Invalid)
            ->where('divergence_reason', 'Quantidade ímpar de registros no dia.')
            ->whereNotNull('employee_id')
            ->whereNotNull('recorded_at')
            ->update([
                'status' => ClockRecordImportItemStatus::Valid,
                'divergence_reason' => null,
            ]);
    }

    protected function markOddGroups(ClockRecordImport $import): void
    {
        $groups = ClockRecordImportItem::query()
            ->where('clock_record_import_id', $import->id)
            ->whereIn('status', [
                ClockRecordImportItemStatus::Valid,
                ClockRecordImportItemStatus::Resolved,
            ])
            ->whereNotNull('employee_id')
            ->whereNotNull('recorded_at')
            ->get()
            ->groupBy(fn (ClockRecordImportItem $item) => $item->employee_id . '|' . $item->recorded_at->format('Y-m-d'));

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

    protected function makeRecordHash(
        int $tenantId,
        int $companyId,
        ?int $employeeId,
        mixed $recordedAt,
    ): ?string {
        if (! $employeeId || ! $recordedAt) {
            return null;
        }

        return sha1(implode('|', [
            $tenantId,
            $companyId,
            $employeeId,
            $recordedAt->format('Y-m-d H:i:s'),
        ]));
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
