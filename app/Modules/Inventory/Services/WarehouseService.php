<?php

namespace App\Modules\Inventory\Services;

use App\Models\User;
use App\Modules\Inventory\Models\Warehouse;
use Illuminate\Validation\ValidationException;

class WarehouseService
{
    public function create(array $data, User $user): Warehouse
    {
        return Warehouse::query()->create([
            'tenant_id' => $user->tenant_id,
            'company_id' => session('current_company_id'),
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'description' => $data['description'] ?? null,
            'is_active' => (bool) $data['is_active'],
        ]);
    }

    public function update(Warehouse $warehouse, array $data): Warehouse
    {
        $warehouse->update([
            'name' => $data['name'],
            'code' => $data['code'] ?? null,
            'description' => $data['description'] ?? null,
            'is_active' => (bool) $data['is_active'],
        ]);

        return $warehouse->refresh();
    }

    public function delete(Warehouse $warehouse): void
    {
        $hasStock = $warehouse->productStocks()
            ->where('quantity', '>', 0)
            ->exists();

        $hasMovements = $warehouse->stockMovements()->exists();

        if ($hasStock || $hasMovements) {
            throw ValidationException::withMessages([
                'warehouse' => 'Este depósito não pode ser excluído porque já possui saldo ou movimentações.',
            ]);
        }

        $warehouse->delete();
    }
}
