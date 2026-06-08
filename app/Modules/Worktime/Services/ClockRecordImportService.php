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

                $startDate = Carbon::parse($data['start_date'])->startOfDay();
                $endDate = Carbon::parse($data['end_date'])->endOfDay();

                $selectedEmployees = collect($data['employee_ids'] ?? [])
                    ->map(fn ($id) => (int) $id)
                    ->values();

                collect($parser->parse($content))
                    ->each(function (array $parsedItem) use (
                        $import,
                        $identifierColumn,
                        $startDate,
                        $endDate,
                        $selectedEmployees
                    ) {
                        $recordedAt = $parsedItem['recorded_at'] ?? null;

                        if (! $recordedAt) {
                            return;
                        }

                        if (
                            $recordedAt->lt($startDate)
                            || $recordedAt->gt($endDate)
                        ) {
                            return;
                        }

                        $employeeId = $this->resolveEmployeeId(
                            employeeCode: $parsedItem['employee_code'] ?? null,
                            tenantId: $import->tenant_id,
                            companyId: $import->company_id,
                            identifierColumn: $identifierColumn,
                        );

                        /**
                         * Funcionário inexistente ou inativo
                         * simplesmente não entra na importação.
                         */
                        if (! $employeeId) {
                            return;
                        }

                        if (
                            $selectedEmployees->isNotEmpty()
                            && ! $selectedEmployees->contains($employeeId)
                        ) {
                            return;
                        }

                        $recordHash = $this->makeRecordHash(
                            tenantId: $import->tenant_id,
                            companyId: $import->company_id,
                            employeeId: $employeeId,
                            recordedAt: $recordedAt,
                        );

                        ClockRecordImportItem::query()->create([
                            'clock_record_import_id' => $import->id,
                            'line_number' => $parsedItem['line_number'],
                            'raw_line' => $parsedItem['raw_line'],
                            'employee_code' => $parsedItem['employee_code'] ?? null,
                            'employee_id' => $employeeId,
                            'recorded_at' => $recordedAt,
                            'status' => ClockRecordImportItemStatus::Valid,
                            'record_hash' => $recordHash,
                            'divergence_reason' => null,
                            'payload' => $parsedItem['payload'] ?? null,
                        ]);
                    });

                $this->recalculateImport($import);

                return $import->fresh([
                    'items.employee',
                    'tenantClockDevice.clockDevice',
                ]);

            } catch (\Throwable $e) {
                if ($storedPath) {
                    Storage::delete($storedPath);
                }

                throw $e;
            }
        });
    }

    public function delete(ClockRecordImport $import, User $user): void
    {
        DB::transaction(function () use ($import, $user) {
            abort_unless($import->tenant_id === $user->tenant_id, 404);
            abort_unless($import->company_id === session('current_company_id'), 404);

            if ($import->status === ClockRecordImportStatus::Launched || filled($import->launched_at)) {
                throw ValidationException::withMessages([
                    'import' => 'Importações já lançadas não podem ser excluídas.',
                ]);
            }

            $import->loadMissing('items');

            foreach ($import->items as $item) {
                if ($item->launched_clock_record_id) {
                    $this->deleteLinkedClockRecord($item, $import);
                }
            }

            if (filled($import->stored_path)) {
                Storage::delete($import->stored_path);
            }

            $import->delete();
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
            abort_unless($item->status === ClockRecordImportItemStatus::Invalid, 422, 'Apenas itens divergentes podem ser desconsiderados.');

            if ($item->launched_clock_record_id) {
                $this->deleteLinkedClockRecord($item, $import);
            }

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
            abort_unless($item->status === ClockRecordImportItemStatus::Invalid, 422, 'Apenas itens divergentes podem ser corrigidos.');
            abort_unless($item->divergence_reason === 'Funcionário não encontrado.', 422, 'Este item não permite vinculação manual de funcionário.');
            abort_unless($item->recorded_at !== null, 422, 'O item não possui data/hora válida para vinculação.');

            $employee = DB::table('employees')
                ->where('id', $employeeId)
                ->where('tenant_id', $import->tenant_id)
                ->where('company_id', $import->company_id)
                ->first();

            abort_unless($employee, 422, 'Funcionário inválido para a empresa atual.');

            $newStatus = $this->isLaunched($import)
                ? ClockRecordImportItemStatus::Launched
                : ClockRecordImportItemStatus::Resolved;

            $item->update([
                'employee_id' => $employeeId,
                'status' => $newStatus,
                'record_hash' => $this->makeRecordHash(
                    tenantId: $import->tenant_id,
                    companyId: $import->company_id,
                    employeeId: $employeeId,
                    recordedAt: $item->recorded_at,
                ),
                'divergence_reason' => null,
                'launched_clock_record_id' => $item->launched_clock_record_id,
            ]);

            if ($this->isLaunched($import)) {
                $this->syncClockRecordForItem($import, $item, $user);
            }

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

            if ($normalizedTimes->count() % 2 !== 0) {
                throw ValidationException::withMessages([
                    'times' => 'Informe uma quantidade par de horários para o dia.',
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
                    ClockRecordImportItemStatus::Launched,
                ])
                ->orderBy('recorded_at')
                ->orderBy('id')
                ->get()
                ->values();

            if ($existingItems->isEmpty()) {
                throw ValidationException::withMessages([
                    'times' => 'Não há registros para ajuste nesta data.',
                ]);
            }

            $this->applyTimeAdjustments(
                import: $import,
                employeeId: $employeeId,
                dateTimes: $normalizedTimes,
                existingItems: $existingItems,
                user: $user,
            );

            $this->recalculateImport($import);

            return $import->fresh(['items.employee', 'tenantClockDevice.clockDevice']);
        });
    }


    public function revert(ClockRecordImport $import, User $user): ClockRecordImport
    {
        return DB::transaction(function () use ($import, $user) {
            abort_unless($import->tenant_id === $user->tenant_id, 404);
            abort_unless($import->company_id === session('current_company_id'), 404);

            if (! $this->isLaunched($import)) {
                throw ValidationException::withMessages([
                    'import' => 'Apenas importações lançadas podem ser revertidas.',
                ]);
            }

            $this->assertImportCanBeReverted($import);

            $items = $import->items()
                ->whereNotNull('launched_clock_record_id')
                ->get();

            foreach ($items as $item) {
                $this->deleteLinkedClockRecord($item, $import);

                $item->update([
                    'status' => $this->determineStatusAfterRevert($import, $item),
                    'launched_clock_record_id' => null,
                ]);
            }

            $import->update([
                'status' => ClockRecordImportStatus::Processing,
                'launched_at' => null,
            ]);

            $import->refresh();

            $this->recalculateImport($import);

            return $import->fresh(['items.employee', 'tenantClockDevice.clockDevice']);
        });
    }

    public function canRevert(ClockRecordImport $import): bool
    {
        if (! $this->isLaunched($import)) {
            return false;
        }

        return ! $this->hasAnySnapshotUsingImport($import);
    }

    public function getCannotRevertReason(ClockRecordImport $import): ?string
    {
        if (! $this->isLaunched($import)) {
            return null;
        }

        if ($this->hasAnySnapshotUsingImport($import)) {
            return 'Esta importação não pode ser revertida porque um ou mais registros dela já foram utilizados em fechamento.';
        }

        return null;
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
        User $user,
    ): void {
        $launched = $this->isLaunched($import);

        $originalItems = $existingItems
            ->filter(fn (ClockRecordImportItem $item) => ! $this->isManualAdjustmentItem($item))
            ->sortBy(fn (ClockRecordImportItem $item) => [
                $item->recorded_at?->format('Y-m-d H:i:s'),
                $item->id,
            ])
            ->values();

        $manualItems = $existingItems
            ->filter(fn (ClockRecordImportItem $item) => $this->isManualAdjustmentItem($item))
            ->sortBy(fn (ClockRecordImportItem $item) => [
                $item->recorded_at?->format('Y-m-d H:i:s'),
                $item->id,
            ])
            ->values();

        $targetTimeKeys = $dateTimes
            ->map(fn (Carbon $dateTime) => $dateTime->format('Y-m-d H:i:s'))
            ->values();

        $usedOriginalIds = collect();
        $usedManualIds = collect();

        $originalItemsByTime = $originalItems
            ->groupBy(fn (ClockRecordImportItem $item) => $item->recorded_at?->format('Y-m-d H:i:s') ?? '')
            ->map(fn (Collection $items) => $items->values());

        foreach ($dateTimes as $dateTime) {
            $timeKey = $dateTime->format('Y-m-d H:i:s');
            $queue = $originalItemsByTime->get($timeKey);

            if (! $queue instanceof Collection || $queue->isEmpty()) {
                continue;
            }

            /** @var ClockRecordImportItem $item */
            $item = $queue->shift();
            $originalItemsByTime->put($timeKey, $queue);
            $usedOriginalIds->push($item->id);

            $payload = is_array($item->payload) ? $item->payload : [];
            unset(
                $payload['manual_adjustment'],
                $payload['original_raw_line'],
                $payload['original_line_number'],
                $payload['original_recorded_at']
            );

            $item->update([
                'employee_id' => $employeeId,
                'line_number' => $payload['original_line_number'] ?? $item->line_number,
                'raw_line' => $payload['original_raw_line'] ?? $item->raw_line,
                'recorded_at' => $dateTime,
                'status' => $launched
                    ? ClockRecordImportItemStatus::Launched
                    : ClockRecordImportItemStatus::Resolved,
                'record_hash' => $this->makeRecordHash(
                    tenantId: $import->tenant_id,
                    companyId: $import->company_id,
                    employeeId: $employeeId,
                    recordedAt: $dateTime,
                ),
                'divergence_reason' => null,
                'payload' => empty($payload) ? null : $payload,
            ]);

            if ($launched) {
                $this->syncClockRecordForItem($import, $item, $user);
            }
        }

        $remainingTargetTimes = $dateTimes
            ->reject(fn (Carbon $dateTime) => $usedOriginalIds->contains(
                fn (int $itemId) => $originalItems->firstWhere('id', $itemId)?->recorded_at?->format('Y-m-d H:i:s') === $dateTime->format('Y-m-d H:i:s')
            ))
            ->values();

        $manualItemsByTime = $manualItems
            ->groupBy(fn (ClockRecordImportItem $item) => $item->recorded_at?->format('Y-m-d H:i:s') ?? '')
            ->map(fn (Collection $items) => $items->values());

        foreach ($remainingTargetTimes as $dateTime) {
            $timeKey = $dateTime->format('Y-m-d H:i:s');
            $queue = $manualItemsByTime->get($timeKey);

            if (! $queue instanceof Collection || $queue->isEmpty()) {
                continue;
            }

            /** @var ClockRecordImportItem $item */
            $item = $queue->shift();
            $manualItemsByTime->put($timeKey, $queue);
            $usedManualIds->push($item->id);

            $payload = is_array($item->payload) ? $item->payload : [];
            $payload['manual_adjustment'] = true;

            $item->update([
                'employee_id' => $employeeId,
                'line_number' => 0,
                'raw_line' => 'Ajuste manual de horário',
                'recorded_at' => $dateTime,
                'status' => $launched
                    ? ClockRecordImportItemStatus::Launched
                    : ClockRecordImportItemStatus::Resolved,
                'record_hash' => $this->makeRecordHash(
                    tenantId: $import->tenant_id,
                    companyId: $import->company_id,
                    employeeId: $employeeId,
                    recordedAt: $dateTime,
                ),
                'divergence_reason' => null,
                'payload' => $payload,
            ]);

            if ($launched) {
                $this->syncClockRecordForItem($import, $item, $user);
            }
        }

        $remainingManualItems = $manualItems
            ->reject(fn (ClockRecordImportItem $item) => $usedManualIds->contains($item->id))
            ->values();

        $remainingTargetTimes = $remainingTargetTimes
            ->reject(function (Carbon $dateTime) use ($usedManualIds, $manualItems) {
                return $usedManualIds->contains(
                    fn (int $itemId) => $manualItems->firstWhere('id', $itemId)?->recorded_at?->format('Y-m-d H:i:s') === $dateTime->format('Y-m-d H:i:s')
                );
            })
            ->values();

        foreach ($remainingTargetTimes as $dateTime) {
            /** @var ClockRecordImportItem|null $manualItem */
            $manualItem = $remainingManualItems->shift();

            if ($manualItem) {
                $payload = is_array($manualItem->payload) ? $manualItem->payload : [];
                $payload['manual_adjustment'] = true;

                $manualItem->update([
                    'employee_id' => $employeeId,
                    'line_number' => 0,
                    'raw_line' => 'Ajuste manual de horário',
                    'recorded_at' => $dateTime,
                    'status' => $launched
                        ? ClockRecordImportItemStatus::Launched
                        : ClockRecordImportItemStatus::Resolved,
                    'record_hash' => $this->makeRecordHash(
                        tenantId: $import->tenant_id,
                        companyId: $import->company_id,
                        employeeId: $employeeId,
                        recordedAt: $dateTime,
                    ),
                    'divergence_reason' => null,
                    'payload' => $payload,
                ]);

                if ($launched) {
                    $this->syncClockRecordForItem($import, $manualItem, $user);
                }

                continue;
            }

            $newItem = ClockRecordImportItem::query()->create([
                'clock_record_import_id' => $import->id,
                'line_number' => 0,
                'raw_line' => 'Ajuste manual de horário',
                'employee_code' => null,
                'employee_id' => $employeeId,
                'recorded_at' => $dateTime,
                'status' => $launched
                    ? ClockRecordImportItemStatus::Launched
                    : ClockRecordImportItemStatus::Resolved,
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

            if ($launched) {
                $this->syncClockRecordForItem($import, $newItem, $user);
            }
        }

        $unusedOriginalItems = $originalItems
            ->reject(fn (ClockRecordImportItem $item) => $usedOriginalIds->contains($item->id))
            ->values();

        $unusedManualItems = $manualItems
            ->reject(fn (ClockRecordImportItem $item) => $usedManualIds->contains($item->id))
            ->values();

        $unusedOriginalItems->each(function (ClockRecordImportItem $item) use ($import) {
            if ($item->launched_clock_record_id) {
                $this->deleteLinkedClockRecord($item, $import);
            }

            $item->update([
                'status' => ClockRecordImportItemStatus::Ignored,
                'divergence_reason' => 'Item removido em ajuste manual de horários.',
                'launched_clock_record_id' => null,
            ]);
        });

        $unusedManualItems->each(function (ClockRecordImportItem $item) use ($import) {
            if ($item->launched_clock_record_id) {
                $this->deleteLinkedClockRecord($item, $import);
            }

            $item->update([
                'status' => ClockRecordImportItemStatus::Ignored,
                'divergence_reason' => 'Item removido em ajuste manual de horários.',
                'launched_clock_record_id' => null,
            ]);
        });
    }

    protected function syncClockRecordForItem(
        ClockRecordImport $import,
        ClockRecordImportItem $item,
        User $user,
    ): void {
        if (! $item->employee_id || ! $item->recorded_at || ! $item->record_hash) {
            return;
        }

        $clockRecord = $item->launched_clock_record_id
            ? ClockRecord::query()
                ->where('id', $item->launched_clock_record_id)
                ->where('tenant_id', $import->tenant_id)
                ->where('company_id', $import->company_id)
                ->first()
            : null;

        if ($clockRecord) {
            $clockRecord->update([
                'employee_id' => $item->employee_id,
                'tenant_clock_device_id' => $import->tenant_clock_device_id,
                'source_type' => ClockRecordSourceType::File,
                'source_hash' => $item->record_hash,
                'recorded_at' => $item->recorded_at,
                'updated_by' => $user->id,
            ]);

            return;
        }

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
            'launched_clock_record_id' => $clockRecord->id,
        ]);
    }

    protected function deleteLinkedClockRecord(
        ClockRecordImportItem $item,
        ClockRecordImport $import,
    ): void {
        ClockRecord::query()
            ->where('id', $item->launched_clock_record_id)
            ->where('tenant_id', $import->tenant_id)
            ->where('company_id', $import->company_id)
            ->delete();
    }

    protected function recalculateImport(ClockRecordImport $import): void
    {
        $this->clearAutoOddDivergences($import);
        $this->markOddGroups($import);

        $import->load('items');

        $activeItems = $import->items
            ->whereIn('status', [
                ClockRecordImportItemStatus::Valid,
                ClockRecordImportItemStatus::Resolved,
                ClockRecordImportItemStatus::Launched,
            ])
            ->count();

        $invalidItems = $import->items
            ->where('status', ClockRecordImportItemStatus::Invalid)
            ->count();

        $status = match (true) {
            $import->items->isEmpty() => ClockRecordImportStatus::Failed,
            $invalidItems > 0 => ClockRecordImportStatus::AwaitingReview,
            $this->isLaunched($import) => ClockRecordImportStatus::Launched,
            $activeItems === 0 => ClockRecordImportStatus::Failed,
            default => ClockRecordImportStatus::ReadyToLaunch,
        };

        $import->update([
            'status' => $status,
            'total_items' => $import->items->count(),
            'valid_items' => $activeItems,
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


    protected function determineStatusAfterRevert(
        ClockRecordImport $import,
        ClockRecordImportItem $item,
    ): ClockRecordImportItemStatus {
        if ($this->isManualAdjustmentItem($item)) {
            return ClockRecordImportItemStatus::Resolved;
        }

        if (! $item->employee_id || ! $item->recorded_at) {
            return ClockRecordImportItemStatus::Invalid;
        }

        $identifierColumn = config('worktime.employee_identifier_column', 'registration');

        $resolvedEmployeeId = $this->resolveEmployeeId(
            employeeCode: $item->employee_code,
            tenantId: $import->tenant_id,
            companyId: $import->company_id,
            identifierColumn: $identifierColumn,
        );

        if (! $resolvedEmployeeId || $resolvedEmployeeId !== (int) $item->employee_id) {
            return ClockRecordImportItemStatus::Resolved;
        }

        return ClockRecordImportItemStatus::Valid;
    }

    protected function assertImportCanBeReverted(ClockRecordImport $import): void
    {
        if ($this->hasAnySnapshotUsingImport($import)) {
            throw ValidationException::withMessages([
                'import' => 'Esta importação não pode ser revertida porque um ou mais registros dela já foram utilizados em fechamento.',
            ]);
        }
    }

    protected function hasAnySnapshotUsingImport(ClockRecordImport $import): bool
    {
        if (! Schema::hasTable('hour_bank_snapshot_employee_days')) {
            return false;
        }

        $employeeDates = $import->items()
            ->whereNotNull('launched_clock_record_id')
            ->whereNotNull('employee_id')
            ->whereNotNull('recorded_at')
            ->get(['employee_id', 'recorded_at'])
            ->map(fn (ClockRecordImportItem $item) => [
                'employee_id' => (int) $item->employee_id,
                'work_date' => $item->recorded_at->format('Y-m-d'),
            ])
            ->unique(fn (array $item) => $item['employee_id'] . '|' . $item['work_date'])
            ->values();

        if ($employeeDates->isEmpty()) {
            return false;
        }

        return DB::table('hour_bank_snapshot_employee_days as snapshot_days')
            ->where('snapshot_days.tenant_id', $import->tenant_id)
            ->where('snapshot_days.company_id', $import->company_id)
            ->where(function ($query) use ($employeeDates) {
                foreach ($employeeDates as $employeeDate) {
                    $query->orWhere(function ($subQuery) use ($employeeDate) {
                        $subQuery
                            ->where('snapshot_days.employee_id', $employeeDate['employee_id'])
                            ->whereDate('snapshot_days.work_date', $employeeDate['work_date']);
                    });
                }
            })
            ->exists();
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
            ->where('is_active', true)
            ->where($identifierColumn, $employeeCode)
            ->value('id');
        }


    protected function isLaunched(ClockRecordImport $import): bool
    {
        return $import->status === ClockRecordImportStatus::Launched || filled($import->launched_at);
    }

    protected function isManualAdjustmentItem(ClockRecordImportItem $item): bool
    {
        $payload = is_array($item->payload) ? $item->payload : [];

        return (bool) ($payload['manual_adjustment'] ?? false);
    }
}
