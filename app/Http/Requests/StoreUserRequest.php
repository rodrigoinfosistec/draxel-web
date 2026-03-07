<?php

namespace App\Http\Requests;

use App\Models\Company;
use App\Models\Role;
use App\Models\TenantModule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\User::class);
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        $companyIds = Company::query()
            ->where('tenant_id', $tenantId)
            ->pluck('id')
            ->toArray();

        $moduleIds = TenantModule::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->pluck('module_id')
            ->toArray();

        $roleIds = Role::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->pluck('id')
            ->toArray();

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId)
                ),
            ],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'company_ids' => ['required', 'array', 'min:1'],
            'company_ids.*' => ['integer', Rule::in($companyIds)],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['integer', Rule::in($roleIds)],
            'module_ids' => ['nullable', 'array'],
            'module_ids.*' => ['integer', Rule::in($moduleIds)],
        ];
    }
}
