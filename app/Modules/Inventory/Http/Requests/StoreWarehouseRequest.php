<?php

namespace App\Modules\Inventory\Http\Requests;

use App\Modules\Inventory\Models\Warehouse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Warehouse::class) ?? false;
    }

    public function rules(): array
    {
        $companyId = session('current_company_id');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('warehouses', 'name')
                    ->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('warehouses', 'code')
                    ->where(fn ($query) => $query->where('company_id', $companyId)),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nome',
            'code' => 'código',
            'description' => 'descrição',
            'is_active' => 'ativo',
        ];
    }
}
