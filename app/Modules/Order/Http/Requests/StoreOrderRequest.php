<?php

namespace App\Modules\Order\Http\Requests;

use App\Modules\Order\Enums\OrderType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('order.createOrder') ?? false;
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'client_id' => [
                'required',
                'integer',
                Rule::exists('clients', 'id')->where(fn ($query) => $query
                    ->where('tenant_id', $tenantId)),
            ],
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
            'client_id.required' => 'Selecione o cliente do pedido.',
            'client_id.exists' => 'O cliente selecionado é inválido.',
            'items.required' => 'Adicione ao menos um item ao pedido.',
            'items.min' => 'Adicione ao menos um item ao pedido.',
            'items.*.product_id.distinct' => 'O mesmo produto não pode ser repetido na lista.',
            'items.*.quantity.gt' => 'A quantidade deve ser maior que zero.',
        ];
    }
}
