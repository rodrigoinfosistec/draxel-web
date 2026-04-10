<?php

namespace App\Modules\Order\Services;

use App\Models\User;
use App\Modules\Inventory\Enums\StockMovementSourceType;
use App\Modules\Inventory\Enums\StockMovementType;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Services\StockMovementService;
use App\Modules\Order\Enums\OrderStatus;
use App\Modules\Order\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        protected StockMovementService $stockMovementService,
    ) {
    }

    public function create(array $data, User $user, int $companyId): Order
    {
        return DB::transaction(function () use ($data, $user, $companyId) {
            $order = Order::create([
                'tenant_id' => $user->tenant_id,
                'company_id' => $companyId,
                'warehouse_id' => (int) $data['warehouse_id'],
                'number' => $this->generateNumber($user->tenant_id, $companyId),
                'type' => $data['type'],
                'status' => OrderStatus::Draft,
                'destination_name' => $data['destination_name'] ?? null,
                'notes' => $data['notes'] ?? null,
                'issued_at' => $data['issued_at'] ?? now(),
                'created_by' => $user->id,
            ]);

            $this->syncItems($order, $data['items']);

            return $order->load(['items.product', 'warehouse', 'creator']);
        });
    }

    public function update(Order $order, array $data): Order
    {
        if (! $order->isDraft()) {
            throw ValidationException::withMessages([
                'order' => 'Somente pedidos em rascunho podem ser alterados.',
            ]);
        }

        return DB::transaction(function () use ($order, $data) {
            $order->update([
                'warehouse_id' => (int) $data['warehouse_id'],
                'type' => $data['type'],
                'destination_name' => $data['destination_name'] ?? null,
                'notes' => $data['notes'] ?? null,
                'issued_at' => $data['issued_at'] ?? $order->issued_at,
            ]);

            $order->items()->delete();

            $this->syncItems($order, $data['items']);

            return $order->load(['items.product', 'warehouse', 'creator']);
        });
    }

    public function confirm(Order $order, User $user): Order
    {
        if (! $order->isDraft()) {
            throw ValidationException::withMessages([
                'order' => 'Apenas pedidos em rascunho podem ser confirmados.',
            ]);
        }

        $order->loadMissing(['items.product', 'warehouse']);

        if ($order->items->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'O pedido precisa ter ao menos um item.',
            ]);
        }

        foreach ($order->items as $item) {
            $available = $this->currentStock(
                tenantId: $order->tenant_id,
                companyId: $order->company_id,
                warehouseId: $order->warehouse_id,
                productId: $item->product_id,
            );

            if ($available < (float) $item->quantity) {
                $productName = $item->product?->name ?? 'Produto';

                throw ValidationException::withMessages([
                    'stock' => "Estoque insuficiente para {$productName}. Disponível: {$available}. Solicitado: {$item->quantity}.",
                ]);
            }
        }

        return DB::transaction(function () use ($order, $user) {
            $confirmedAt = now();

            foreach ($order->items as $item) {
                $this->stockMovementService->register([
                    'tenant_id' => $order->tenant_id,
                    'company_id' => $order->company_id,
                    'warehouse_id' => $order->warehouse_id,
                    'product_id' => $item->product_id,
                    'type' => StockMovementType::Exit->value,
                    'source_type' => StockMovementSourceType::Order,
                    'source_id' => $order->id,
                    'quantity' => $item->quantity,
                    'unit_cost' => null,
                    'reference' => $order->number,
                    'notes' => $item->notes ?: "Saída gerada pelo pedido {$order->number}.",
                    'moved_at' => $confirmedAt->toDateTimeString(),
                    'user_id' => $user->id,
                ]);
            }

            $order->update([
                'status' => OrderStatus::Confirmed,
                'confirmed_at' => $confirmedAt,
                'confirmed_by' => $user->id,
            ]);

            return $order->fresh(['items.product', 'warehouse', 'creator', 'confirmer']);
        });
    }

    public function cancel(Order $order, User $user): Order
    {
        if (! $order->isDraft()) {
            throw ValidationException::withMessages([
                'order' => 'Somente pedidos em rascunho podem ser cancelados.',
            ]);
        }

        $order->update([
            'status' => OrderStatus::Cancelled,
            'cancelled_at' => now(),
            'cancelled_by' => $user->id,
        ]);

        return $order->fresh(['items.product', 'warehouse', 'creator', 'canceller']);
    }

    public function delete(Order $order): void
    {
        if (! $order->isDraft()) {
            throw ValidationException::withMessages([
                'order' => 'Somente pedidos em rascunho podem ser excluídos.',
            ]);
        }

        DB::transaction(function () use ($order) {
            $order->items()->delete();
            $order->delete();
        });
    }

    protected function syncItems(Order $order, array $items): void
    {
        foreach ($items as $item) {
            $order->items()->create([
                'product_id' => (int) $item['product_id'],
                'quantity' => $item['quantity'],
                'notes' => $item['notes'] ?? null,
            ]);
        }
    }

    protected function generateNumber(int $tenantId, int $companyId): string
    {
        $prefix = 'PED-' . now()->format('Ym');

        $lastOrder = Order::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where('number', 'like', "{$prefix}-%")
            ->latest('id')
            ->first();

        if (! $lastOrder) {
            return "{$prefix}-0001";
        }

        $lastSequence = (int) str($lastOrder->number)->afterLast('-')->value();
        $nextSequence = str_pad((string) ($lastSequence + 1), 4, '0', STR_PAD_LEFT);

        return "{$prefix}-{$nextSequence}";
    }

    protected function currentStock(
        int $tenantId,
        int $companyId,
        int $warehouseId,
        int $productId,
    ): float {
        $movements = StockMovement::query()
            ->where('tenant_id', $tenantId)
            ->where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->where('product_id', $productId)
            ->get(['quantity', 'type']);

        return (float) $movements->reduce(function (float $carry, StockMovement $movement) {
            $signal = $movement->type?->signal() ?? 0;

            return $carry + ((float) $movement->quantity * $signal);
        }, 0.0);
    }
}
