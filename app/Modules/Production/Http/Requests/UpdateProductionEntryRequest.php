<?php

namespace App\Modules\Production\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductionEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Modules\Production\Models\ProductionEntry $entry */
        $entry = $this->route('entry');

        return $this->user()?->can('update', $entry) ?? false;
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;
        $companyId = session('current_company_id');

        return [
            'entry_date' => ['required', 'date'],
            'warehouse_id' => [
                'required',
                'integer',
                Rule::exists('warehouses', 'id')
                    ->where(fn ($query) => $query
                        ->where('tenant_id', $tenantId)
                        ->where('company_id', $companyId)
                        ->where('is_active', true)),
            ],
            'notes' => ['nullable', 'string'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('products', 'id')
                    ->where(fn ($query) => $query
                        ->where('tenant_id', $tenantId)
                        ->where('is_active', true)),
            ],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'gte:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'entry_date' => 'data',
            'warehouse_id' => 'depósito',
            'notes' => 'observação',
            'items' => 'itens',
            'items.*.product_id' => 'produto',
            'items.*.quantity' => 'quantidade',
            'items.*.unit_cost' => 'custo unitário',
            'items.*.notes' => 'observação do item',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Informe pelo menos um item.',
            'items.min' => 'Informe pelo menos um item.',
            'items.*.product_id.distinct' => 'Não é permitido repetir o mesmo produto no lançamento.',
            'items.*.quantity.gt' => 'A quantidade deve ser maior que zero.',
        ];
    }
}
