<?php

namespace App\Modules\Production\Services;

use App\Models\User;
use App\Modules\Inventory\Enums\StockMovementSourceType;
use App\Modules\Inventory\Enums\StockMovementType;
use App\Modules\Inventory\Services\StockMovementService;
use App\Modules\Production\Enums\ProductionEntryStatus;
use App\Modules\Production\Models\ProductionEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductionEntryService
{
    public function __construct(
        protected StockMovementService $stockMovementService,
    ) {
    }

    public function create(array $data, User $user): ProductionEntry
    {
        return DB::transaction(function () use ($data, $user) {
            $entry = ProductionEntry::query()->create([
                'tenant_id' => $user->tenant_id,
                'company_id' => session('current_company_id'),
                'warehouse_id' => (int) $data['warehouse_id'],
                'number' => $this->generateNumber(),
                'entry_date' => $data['entry_date'],
                'status' => ProductionEntryStatus::Draft,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            $this->syncItems($entry, $data['items']);

            return $entry->load('items');
        });
    }

    public function update(ProductionEntry $entry, array $data): ProductionEntry
    {
        if (! $entry->isDraft()) {
            throw ValidationException::withMessages([
                'entry' => 'Somente lançamentos em rascunho podem ser editados.',
            ]);
        }

        return DB::transaction(function () use ($entry, $data) {
            $entry->update([
                'entry_date' => $data['entry_date'],
                'warehouse_id' => (int) $data['warehouse_id'],
                'notes' => $data['notes'] ?? null,
            ]);

            $entry->items()->delete();
            $this->syncItems($entry, $data['items']);

            return $entry->refresh()->load('items');
        });
    }

    public function delete(ProductionEntry $entry): void
    {
        if (! $entry->isDraft()) {
            throw ValidationException::withMessages([
                'entry' => 'Somente lançamentos em rascunho podem ser excluídos.',
            ]);
        }

        $entry->delete();
    }

    public function post(ProductionEntry $entry, User $user): ProductionEntry
    {
        if (! $entry->isDraft()) {
            throw ValidationException::withMessages([
                'entry' => 'Somente lançamentos em rascunho podem ser lançados.',
            ]);
        }

        $entry->loadMissing('items');

        if ($entry->items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'A entrada de produção deve possuir ao menos um item.',
            ]);
        }

        return DB::transaction(function () use ($entry, $user) {
            foreach ($entry->items as $item) {
                $this->stockMovementService->register([
                    'tenant_id' => $entry->tenant_id,
                    'company_id' => $entry->company_id,
                    'warehouse_id' => $entry->warehouse_id,
                    'product_id' => $item->product_id,
                    'type' => StockMovementType::Entry,
                    'source_type' => StockMovementSourceType::Production,
                    'source_id' => $entry->id,
                    'quantity' => (float) $item->quantity,
                    'unit_cost' => $item->unit_cost,
                    'reference' => $entry->number,
                    'notes' => 'Entrada de produção ' . $entry->number,
                    'moved_at' => $entry->entry_date->copy()->setTime(12, 0, 0),
                    'user_id' => $user->id,
                ]);
            }

            $entry->update([
                'status' => ProductionEntryStatus::Posted,
                'posted_by' => $user->id,
                'posted_at' => now(),
            ]);

            return $entry->refresh();
        });
    }

    public function cancel(ProductionEntry $entry, User $user, ?string $cancelReason = null): ProductionEntry
    {
        if (! $entry->isPosted()) {
            throw ValidationException::withMessages([
                'entry' => 'Somente lançamentos já lançados podem ser cancelados.',
            ]);
        }

        $entry->loadMissing('items');

        return DB::transaction(function () use ($entry, $user, $cancelReason) {
            foreach ($entry->items as $item) {
                $this->stockMovementService->register([
                    'tenant_id' => $entry->tenant_id,
                    'company_id' => $entry->company_id,
                    'warehouse_id' => $entry->warehouse_id,
                    'product_id' => $item->product_id,
                    'type' => StockMovementType::Exit,
                    'source_type' => StockMovementSourceType::Production,
                    'source_id' => $entry->id,
                    'quantity' => (float) $item->quantity,
                    'unit_cost' => $item->unit_cost,
                    'reference' => $entry->number . '-ESTORNO',
                    'notes' => 'Estorno da entrada de produção ' . $entry->number,
                    'moved_at' => now(),
                    'user_id' => $user->id,
                ]);
            }

            $entry->update([
                'status' => ProductionEntryStatus::Cancelled,
                'cancelled_by' => $user->id,
                'cancelled_at' => now(),
                'cancel_reason' => $cancelReason,
            ]);

            return $entry->refresh();
        });
    }

    protected function syncItems(ProductionEntry $entry, array $items): void
    {
        foreach ($items as $item) {
            $unitCost = isset($item['unit_cost']) && $item['unit_cost'] !== null && $item['unit_cost'] !== ''
                ? round((float) $item['unit_cost'], 2)
                : null;

            $quantity = round((float) $item['quantity'], 3);

            $entry->items()->create([
                'tenant_id' => $entry->tenant_id,
                'company_id' => $entry->company_id,
                'product_id' => (int) $item['product_id'],
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $unitCost !== null ? round($unitCost * $quantity, 2) : null,
                'notes' => $item['notes'] ?? null,
            ]);
        }
    }

    protected function generateNumber(): string
    {
        $companyId = session('current_company_id');

        $lastId = (int) ProductionEntry::query()
            ->where('company_id', $companyId)
            ->max('id');

        return 'EP-' . str_pad((string) ($lastId + 1), 6, '0', STR_PAD_LEFT);
    }
}
