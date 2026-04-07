<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Product::class);
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => [
                'required',
                'string',
                'max:255',
                Rule::unique('products', 'sku')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
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
            'tracks_stock' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->name),
            'sku' => strtoupper(trim((string) $this->sku)),
            'description' => filled($this->description)
                ? trim((string) $this->description)
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
            'product_category_id' => 'categoria',
            'unit_of_measure_id' => 'unidade de medida',
            'brand_id' => 'marca',
            'description' => 'descrição',
            'tracks_stock' => 'controla estoque',
            'is_active' => 'status',
        ];
    }

    public function messages(): array
    {
        return [
            'sku.unique' => 'Já existe um produto com este SKU.',
        ];
    }
}
