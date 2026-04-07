<?php

namespace App\Http\Requests;

use App\Models\UnitOfMeasure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUnitOfMeasureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', UnitOfMeasure::class);
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('unit_of_measures', 'name')
                    ->where(fn ($query) => $query->where('tenant_id', $this->user()->tenant_id)),
            ],
            'symbol' => [
                'required',
                'string',
                'max:20',
                Rule::unique('unit_of_measures', 'symbol')
                    ->where(fn ($query) => $query->where('tenant_id', $this->user()->tenant_id)),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->name),
            'symbol' => strtoupper(trim((string) $this->symbol)),
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
            'symbol' => 'sigla',
            'description' => 'descrição',
            'is_active' => 'status',
        ];
    }

    public function messages(): array
    {
        return [
            'name.unique' => 'Já existe uma unidade com este nome.',
            'symbol.unique' => 'Já existe uma unidade com esta sigla.',
        ];
    }
}
