<?php

namespace App\Modules\Inventory\Http\Requests;

use App\Modules\Inventory\Enums\StockMovementType;
use App\Modules\Inventory\Models\StockMovement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', StockMovement::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'type' => ['required', new Enum(StockMovementType::class)],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'moved_at' => ['required', 'date'],
        ];
    }

    public function attributes(): array
    {
        return [
            'warehouse_id' => 'depósito',
            'product_id' => 'produto',
            'type' => 'tipo',
            'quantity' => 'quantidade',
            'unit_cost' => 'custo unitário',
            'reference' => 'referência',
            'notes' => 'observação',
            'moved_at' => 'data da movimentação',
        ];
    }
}
