<?php

namespace App\Modules\PurchaseReceipt\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdatePurchaseReceiptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('purchaseReceipt.updatePurchaseReceipt') ?? false;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'number' => ['nullable', 'string', 'max:100'],
            'invoice_number' => ['nullable', 'string', 'max:100'],
            'issue_date' => ['nullable', 'date'],
            'receipt_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_cost' => ['required', 'numeric', 'gte:0'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.required' => 'Selecione o fornecedor.',
            'warehouse_id.required' => 'Selecione o depósito.',
            'receipt_date.required' => 'Informe a data de recebimento.',
            'items.required' => 'Adicione ao menos um item ao recebimento.',
            'items.min' => 'Adicione ao menos um item ao recebimento.',
            'items.*.product_id.required' => 'Selecione o produto do item.',
            'items.*.quantity.required' => 'Informe a quantidade do item.',
            'items.*.quantity.gt' => 'A quantidade deve ser maior que zero.',
            'items.*.unit_cost.required' => 'Informe o custo unitário do item.',
            'items.*.unit_cost.gte' => 'O custo unitário não pode ser negativo.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $items = $this->input('items', []);

            $productIds = collect($items)
                ->pluck('product_id')
                ->filter()
                ->values();

            if ($productIds->count() !== $productIds->unique()->count()) {
                $validator->errors()->add('items', 'Não é permitido repetir o mesmo produto no recebimento.');
            }
        });
    }
}
