<?php

namespace App\Modules\Order\Http\Requests;

use App\Modules\Order\Enums\OrderType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('order.createOrder') ?? false;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'type' => ['required', new Enum(OrderType::class)],
            'destination_name' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'issued_at' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id', 'distinct'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Adicione ao menos um item ao pedido.',
            'items.min' => 'Adicione ao menos um item ao pedido.',
            'items.*.product_id.distinct' => 'O mesmo produto não pode ser repetido na lista.',
            'items.*.quantity.gt' => 'A quantidade deve ser maior que zero.',
        ];
    }
}
