<?php

namespace App\Http\Requests;

use App\Models\Permission;
use App\Models\TenantModule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\Role::class);
    }

    public function rules(): array
    {
        $tenantId = $this->user()->tenant_id;

        $allowedPermissionIds = Permission::query()
            ->whereIn('module_id', TenantModule::query()
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->pluck('module_id'))
            ->where('is_active', true)
            ->pluck('id')
            ->all();

        return [
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles', 'slug')->where(fn ($query) => $query->where('tenant_id', $tenantId)),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'permission_ids' => ['array'],
            'permission_ids.*' => ['integer', Rule::in($allowedPermissionIds)],
            'is_active' => ['boolean'],
        ];
    }
}
