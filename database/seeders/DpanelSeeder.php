<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Modules\Worktime\Models\ClockDevice;
use Illuminate\Database\Seeder;

class DpanelSeeder extends Seeder
{
    public function run(): void
    {
        $tenantConfig = config('dpanel.tenant');
        $companyConfig = config('dpanel.company');
        $roleConfig = config('dpanel.role');
        $usersConfig = config('dpanel.user');

        $tenant = Tenant::updateOrCreate(
            [
                'slug' => $tenantConfig['slug'],
            ],
            [
                'name' => $tenantConfig['name'],
                'is_active' => true,
            ]
        );

        $company = Company::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'cnpj' => $companyConfig['cnpj'],
            ],
            [
                'name' => $companyConfig['name'],
                'alias' => $companyConfig['alias'],
                'color' => $companyConfig['color'],
                'is_active' => true,
            ]
        );

        $modules = Module::query()->active()->get();

        foreach ($modules as $module) {
            TenantModule::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'module_id' => $module->id,
                ],
                [
                    'is_active' => true,
                ]
            );
        }

        $clockDeviceIds = ClockDevice::query()
            ->pluck('id')
            ->toArray();

        $tenant->clockDevices()->syncWithoutDetaching($clockDeviceIds);

        $role = Role::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'slug' => $roleConfig['slug'],
            ],
            [
                'name' => $roleConfig['name'],
                'description' => $roleConfig['description'],
                'is_active' => true,
            ]
        );

        $permissionIds = Permission::query()
            ->active()
            ->pluck('id')
            ->toArray();

        $role->permissions()->syncWithoutDetaching($permissionIds);

        foreach ($usersConfig as $userData) {
            $user = User::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'email' => $userData['email'],
                ],
                [
                    'name' => $userData['name'],
                    'password' => $userData['password'],
                    'email_verified_at' => $userData['email_verified_at'],
                    'is_admin' => $userData['is_admin'],
                    'is_active' => true,
                ]
            );

            $user->companies()->syncWithoutDetaching([
                $company->id => [
                    'tenant_id' => $tenant->id,
                ],
            ]);

            if ($user->default_company_id !== $company->id) {
                $user->update([
                    'default_company_id' => $company->id,
                ]);
            }

            $user->roles()->syncWithoutDetaching([$role->id]);

            $user->modules()->syncWithoutDetaching(
                $modules->pluck('id')->toArray()
            );
        }
    }
}
