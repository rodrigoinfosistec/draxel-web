<?php

namespace App\Http\Requests;

use App\Models\ProductSupplierReference;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductSupplierReferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', ProductSupplierReference::class);
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;
        $supplierId = (int) $this->supplier_id;

        return [
            'supplier_id' => [
                'required',
                'integer',
                Rule::exists('suppliers', 'id')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'product_id' => [
                'required',
                'integer',
                Rule::exists('products', 'id')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)),
                Rule::unique('product_supplier_references', 'product_id')
                    ->where(fn ($query) => $query
                        ->where('tenant_id', $tenantId)
                        ->where('supplier_id', $supplierId)),
            ],
            'supplier_product_code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('product_supplier_references', 'supplier_product_code')
                    ->where(fn ($query) => $query
                        ->where('tenant_id', $tenantId)
                        ->where('supplier_id', $supplierId)),
            ],
            'supplier_product_description' => ['nullable', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'size:14'],
            'unit' => ['nullable', 'string', 'max:20'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $barcode = preg_replace('/\D+/', '', (string) $this->barcode);

        $this->merge([
            'supplier_product_code' => filled($this->supplier_product_code)
                ? trim((string) $this->supplier_product_code)
                : null,
            'supplier_product_description' => filled($this->supplier_product_description)
                ? trim((string) $this->supplier_product_description)
                : null,
            'barcode' => filled($barcode) ? str_pad($barcode, 14, '0', STR_PAD_LEFT) : null,
            'unit' => filled($this->unit) ? strtoupper(trim((string) $this->unit)) : null,
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function attributes(): array
    {
        return [
            'supplier_id' => 'fornecedor',
            'product_id' => 'produto',
            'supplier_product_code' => 'código do fornecedor',
            'supplier_product_description' => 'descrição do fornecedor',
            'barcode' => 'GTIN/EAN',
            'unit' => 'unidade',
            'is_active' => 'status',
        ];
    }

    public function messages(): array
    {
        return [
            'product_id.unique' => 'Este produto já possui vínculo com este fornecedor.',
            'supplier_product_code.unique' => 'Este código do fornecedor já está vinculado para este fornecedor.',
            'barcode.size' => 'O GTIN/EAN deve ter 14 dígitos.',
        ];
    }
}
