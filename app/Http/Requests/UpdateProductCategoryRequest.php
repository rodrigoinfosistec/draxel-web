<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\ProductCategory $productCategory */
        $productCategory = $this->route('productCategory');

        return $this->user()->can('update', $productCategory);
    }

    public function rules(): array
    {
        /** @var \App\Models\ProductCategory $productCategory */
        $productCategory = $this->route('productCategory');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('product_categories', 'name')
                    ->where(fn ($query) => $query->where('tenant_id', $this->user()->tenant_id))
                    ->ignore($productCategory->id),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->name),
            'description' => filled($this->description)
                ? trim((string) $this->description)
                : null,
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'description' => 'descrição',
            'is_active' => 'status',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Já existe uma categoria com este nome.',
        ];
    }
}
