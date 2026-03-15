<?php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Employee $employee */
        $employee = $this->route('employee');

        return $this->user()?->can('update', $employee) ?? false;
    }

    public function rules(): array
    {
        /** @var Employee $employee */
        $employee = $this->route('employee');

        $tenantId = $this->user()->tenant_id;
        $companyId = app(\App\Support\CompanyContext::class)->current()?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'cpf' => [
                'required',
                'string',
                'max:14',
                Rule::unique('employees', 'cpf')
                    ->ignore($employee->id)
                    ->where(fn ($query) => $query
                        ->where('tenant_id', $tenantId)
                        ->where('company_id', $companyId)),
            ],
            'registration' => [
                'required',
                'string',
                'max:100',
                Rule::unique('employees', 'registration')
                    ->ignore($employee->id)
                    ->where(fn ($query) => $query
                        ->where('tenant_id', $tenantId)
                        ->where('company_id', $companyId)),
            ],
            'position_id' => ['nullable', 'integer', 'exists:positions,id'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
