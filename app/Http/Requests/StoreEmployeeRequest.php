<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('employees.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'cpf' => preg_replace('/\D+/', '', (string) $this->cpf),
        ]);
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;
        $companyId = app(\App\Support\CompanyContext::class)->current()?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'cpf' => [
                'required',
                'digits:11',
                Rule::unique('employees', 'cpf')->where(fn ($query) => $query
                    ->where('tenant_id', $tenantId)
                    ->where('company_id', $companyId)),
            ],
            'registration' => [
                'required',
                'string',
                'max:100',
                Rule::unique('employees', 'registration')->where(fn ($query) => $query
                    ->where('tenant_id', $tenantId)
                    ->where('company_id', $companyId)),
            ],
            'position_id' => ['nullable', 'integer', 'exists:positions,id'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
