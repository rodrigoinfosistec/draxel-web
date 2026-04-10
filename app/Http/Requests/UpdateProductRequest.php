<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\Product $product */
        $product = $this->route('product');

        return $this->user()->can('update', $product);
    }

    public function rules(): array
    {
        /** @var \App\Models\Product $product */
        $product = $this->route('product');

        $tenantId = $this->user()->tenant_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'sku')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($product->id),
            ],
            'barcode' => [
                'nullable',
                'string',
                'size:14',
                Rule::unique('products', 'barcode')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($product->id),
            ],
            'ncm_code' => ['nullable', 'string', 'size:8'],
            'product_category_id' => [
                'required',
                'integer',
                Rule::exists('product_categories', 'id')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'unit_of_measure_id' => [
                'required',
                'integer',
                Rule::exists('unit_of_measures', 'id')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'brand_id' => [
                'nullable',
                'integer',
                Rule::exists('brands', 'id')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'description' => ['nullable', 'string'],
            'purchase_description' => ['nullable', 'string', 'max:255'],
            'tracks_stock' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $barcode = preg_replace('/\D+/', '', (string) $this->barcode);
        $ncmCode = preg_replace('/\D+/', '', (string) $this->ncm_code);

        $this->merge([
            'name' => trim((string) $this->name),
            'sku' => strtoupper(trim((string) $this->sku)),
            'barcode' => filled($barcode) ? str_pad($barcode, 14, '0', STR_PAD_LEFT) : null,
            'ncm_code' => filled($ncmCode) ? $ncmCode : null,
            'description' => filled($this->description)
                ? trim((string) $this->description)
                : null,
            'purchase_description' => filled($this->purchase_description)
                ? trim((string) $this->purchase_description)
                : null,
            'brand_id' => filled($this->brand_id) ? (int) $this->brand_id : null,
            'tracks_stock' => $this->boolean('tracks_stock'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'sku' => 'SKU',
            'barcode' => 'GTIN/EAN',
            'ncm_code' => 'NCM',
            'product_category_id' => 'categoria',
            'unit_of_measure_id' => 'unidade de medida',
            'brand_id' => 'marca',
            'description' => 'descrição',
            'purchase_description' => 'descrição de compra',
            'tracks_stock' => 'controla estoque',
            'is_active' => 'status',
        ];
    }

    public function messages(): array
    {
        return [
            'sku.unique' => 'Já existe um produto com este SKU.',
            'barcode.unique' => 'Já existe um produto com este GTIN/EAN.',
            'barcode.size' => 'O GTIN/EAN deve ter 14 dígitos.',
            'ncm_code.size' => 'O NCM deve ter 8 dígitos.',
        ];
    }
}
