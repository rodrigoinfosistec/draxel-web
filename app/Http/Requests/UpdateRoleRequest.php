<?php

namespace App\Http\Requests;

use App\Models\Permission;
use App\Models\Role;
use App\Models\TenantModule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Role $role */
        $role = $this->route('role');

        return $this->user()->can('update', $role);
    }

    public function rules(): array
    {
        /** @var Role $role */
        $role = $this->route('role');

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
                Rule::unique('roles', 'slug')
                    ->where(fn ($query) => $query->where('tenant_id', $tenantId))
                    ->ignore($role->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'permission_ids' => ['array'],
            'permission_ids.*' => ['integer', Rule::in($allowedPermissionIds)],
            'is_active' => ['boolean'],
        ];
    }
}
