<?php

namespace App\Modules\Inventory\Services;

use App\Models\Product;
use App\Modules\Inventory\Enums\StockMovementSourceType;
use App\Modules\Inventory\Enums\StockMovementType;
use App\Modules\Inventory\Models\ProductStock;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockMovementService
{
    public function register(array $data): StockMovement
    {
        return DB::transaction(function () use ($data) {
            $type = $data['type'] instanceof StockMovementType
                ? $data['type']
                : StockMovementType::from($data['type']);

            $sourceType = ($data['source_type'] ?? StockMovementSourceType::Manual) instanceof StockMovementSourceType
                ? ($data['source_type'] ?? StockMovementSourceType::Manual)
                : StockMovementSourceType::from($data['source_type'] ?? StockMovementSourceType::Manual->value);

            $tenantId = (int) $data['tenant_id'];
            $companyId = (int) $data['company_id'];
            $warehouseId = (int) $data['warehouse_id'];
            $productId = (int) $data['product_id'];
            $quantity = round((float) $data['quantity'], 3);

            $warehouse = Warehouse::query()
                ->where('tenant_id', $tenantId)
                ->where('company_id', $companyId)
                ->where('id', $warehouseId)
                ->first();

            if (! $warehouse) {
                throw ValidationException::withMessages([
                    'warehouse_id' => 'O depósito informado não pertence à empresa em contexto.',
                ]);
            }

            if (! $warehouse->is_active) {
                throw ValidationException::withMessages([
                    'warehouse_id' => 'O depósito informado está inativo.',
                ]);
            }

            $productExists = Product::query()
                ->where('tenant_id', $tenantId)
                ->where('id', $productId)
                ->exists();

            if (! $productExists) {
                throw ValidationException::withMessages([
                    'product_id' => 'O produto informado não pertence ao tenant em contexto.',
                ]);
            }

            $productStock = ProductStock::query()
                ->where('tenant_id', $tenantId)
                ->where('company_id', $companyId)
                ->where('warehouse_id', $warehouseId)
                ->where('product_id', $productId)
                ->lockForUpdate()
                ->first();

            if (! $productStock) {
                $productStock = new ProductStock([
                    'tenant_id' => $tenantId,
                    'company_id' => $companyId,
                    'warehouse_id' => $warehouseId,
                    'product_id' => $productId,
                    'quantity' => 0,
                ]);
            }

            $currentQuantity = (float) $productStock->quantity;
            $newQuantity = round($currentQuantity + ($quantity * $type->signal()), 3);

            if ($newQuantity < 0) {
                throw ValidationException::withMessages([
                    'quantity' => 'Saldo insuficiente para esta movimentação neste depósito.',
                ]);
            }

            $movement = StockMovement::query()->create([
                'tenant_id' => $tenantId,
                'company_id' => $companyId,
                'warehouse_id' => $warehouseId,
                'product_id' => $productId,
                'type' => $type,
                'source_type' => $sourceType,
                'source_id' => $data['source_id'] ?? null,
                'quantity' => $quantity,
                'unit_cost' => $data['unit_cost'] ?? null,
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'moved_at' => $data['moved_at'],
                'user_id' => $data['user_id'] ?? null,
            ]);

            $productStock->quantity = $newQuantity;
            $productStock->save();

            return $movement->load(['warehouse', 'product', 'user']);
        });
    }
}
