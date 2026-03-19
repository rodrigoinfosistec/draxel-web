<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Position;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use App\Modules\Worktime\Models\ClockDevice;
use App\Modules\Worktime\Models\TenantClockDevice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ConstrulajeSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $tenant = Tenant::query()->updateOrCreate(
                [
                    'slug' => 'construlaje',
                ],
                [
                    'name' => 'Construlaje Recife',
                    'is_active' => true,
                ],
            );

            $this->syncPositions($tenant);
            $this->syncDepartments($tenant);

            $companyJoana = Company::query()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'cnpj' => '11784192000190',
                ],
                [
                    'name' => 'Construlaje Joana Bezerra',
                    'alias' => 'Joana Bezerra',
                    'color' => '#1D4ED8',
                    'is_active' => true,
                    'uses_hour_bank' => false,
                    'hour_bank_starts_at' => null,
                ],
            );

            $companyBrasilia = Company::query()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'cnpj' => '57841206000161',
                ],
                [
                    'name' => 'Construlaje Brasilia Teimosa',
                    'alias' => 'Brasilia Teimosa',
                    'color' => '#B91C1C',
                    'is_active' => true,
                    'uses_hour_bank' => false,
                    'hour_bank_starts_at' => null,
                ],
            );

            $modules = Module::query()->active()->get();

            foreach ($modules as $module) {
                TenantModule::query()->updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'module_id' => $module->id,
                    ],
                    [
                        'is_active' => true,
                    ],
                );
            }

            $clockDevices = ClockDevice::query()->get();

            foreach ($clockDevices as $clockDevice) {
                TenantClockDevice::query()->updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'clock_device_id' => $clockDevice->id,
                    ],
                    []
                );
            }

            $role = Role::query()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'slug' => 'administrador',
                ],
                [
                    'name' => 'Administrador',
                    'description' => 'Perfil administrativo com acesso total ao tenant Construlaje.',
                    'is_active' => true,
                ],
            );

            $permissionIds = Permission::query()
                ->active()
                ->pluck('id')
                ->toArray();

            $role->permissions()->sync($permissionIds);

            $adminEmail = env('CONSTRULAJE_ADMIN_EMAIL', 'admin@construlaje.com.br');
            $adminPassword = env('CONSTRULAJE_ADMIN_PASSWORD', 'Construlaje@123');

            $admin = User::query()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'email' => $adminEmail,
                ],
                [
                    'name' => 'Admin Construlaje',
                    'password' => Hash::make($adminPassword),
                    'email_verified_at' => now(),
                    'is_admin' => true,
                    'is_active' => true,
                ],
            );

            $admin->companies()->sync([
                $companyJoana->id => ['tenant_id' => $tenant->id],
                $companyBrasilia->id => ['tenant_id' => $tenant->id],
            ]);

            if ($admin->default_company_id !== $companyJoana->id) {
                $admin->update([
                    'default_company_id' => $companyJoana->id,
                ]);
            }

            $admin->roles()->sync([$role->id]);
            $admin->modules()->sync($modules->pluck('id')->toArray());

            $departments = Department::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->get()
                ->keyBy('name');

            $positions = Position::withoutGlobalScopes()
                ->where('tenant_id', $tenant->id)
                ->get()
                ->keyBy('name');

            $employees = [
                [
                    'company_id' => $companyJoana->id,
                    'name' => 'Ana Paula Gomes da Silva',
                    'cpf' => '68624590400',
                    'registration' => '16862',
                    'department' => 'Serviços Gerais',
                    'position' => 'Auxiliar de Serviços Gerais',
                    'is_active' => true,
                ],
                [
                    'company_id' => $companyJoana->id,
                    'name' => 'Eduardo Ruan da Silva',
                    'cpf' => '70866264493',
                    'registration' => '17086',
                    'department' => 'Logística',
                    'position' => 'Auxiliar de Entrega',
                    'is_active' => true,
                ],
                [
                    'company_id' => $companyJoana->id,
                    'name' => 'Genilson Martins de Lira',
                    'cpf' => '55078982434',
                    'registration' => '15507',
                    'department' => 'Logística',
                    'position' => 'Motorista',
                    'is_active' => true,
                ],
                [
                    'company_id' => $companyJoana->id,
                    'name' => 'Kevin de Paula Almeida',
                    'cpf' => '11294979818',
                    'registration' => '11129',
                    'department' => 'Logística',
                    'position' => 'Motorista',
                    'is_active' => true,
                ],
                [
                    'company_id' => $companyJoana->id,
                    'name' => 'Thiago Ferreira do Nascimento',
                    'cpf' => '71256401412',
                    'registration' => '17125',
                    'department' => 'Logística',
                    'position' => 'Auxiliar de Entrega',
                    'is_active' => true,
                ],
                [
                    'company_id' => $companyJoana->id,
                    'name' => 'Wagner Celio da Conceicao',
                    'cpf' => '08813814461',
                    'registration' => '10881',
                    'department' => 'Logística',
                    'position' => 'Auxiliar de Entrega',
                    'is_active' => true,
                ],
                [
                    'company_id' => $companyJoana->id,
                    'name' => 'Alisson da Assunção',
                    'cpf' => '17055282446',
                    'registration' => '12446',
                    'department' => 'Logística',
                    'position' => 'Auxiliar de Entrega',
                    'is_active' => true,
                ],
                [
                    'company_id' => $companyBrasilia->id,
                    'name' => 'Gustavo Alves dos Santos',
                    'cpf' => '07745740460',
                    'registration' => '10774',
                    'department' => 'Comercial',
                    'position' => 'Vendedor',
                    'is_active' => true,
                ],
                [
                    'company_id' => $companyBrasilia->id,
                    'name' => 'Taina Carolaine Pires de Almeida Nascimento',
                    'cpf' => '11294870408',
                    'registration' => '21129',
                    'department' => 'Comercial',
                    'position' => 'Vendedor',
                    'is_active' => true,
                ],
            ];

            foreach ($employees as $employeeData) {
                $department = $departments->get($employeeData['department']);
                $position = $positions->get($employeeData['position']);

                Employee::query()->updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'company_id' => $employeeData['company_id'],
                        'cpf' => $employeeData['cpf'],
                    ],
                    [
                        'department_id' => $department?->id,
                        'position_id' => $position?->id,
                        'name' => $employeeData['name'],
                        'registration' => $employeeData['registration'],
                        'is_active' => $employeeData['is_active'],
                    ],
                );
            }
        });
    }

    protected function syncPositions(Tenant $tenant): void
    {
        $positions = collect(config('positions', []))
            ->filter(fn (array $position) => filled($position['name'] ?? null))
            ->map(fn (array $position) => [
                'tenant_id' => $tenant->id,
                'name' => trim($position['name']),
                'slug' => Str::slug($position['name']),
                'description' => filled($position['description'] ?? null)
                    ? trim($position['description'])
                    : null,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->unique('slug')
            ->values();

        if ($positions->isEmpty()) {
            return;
        }

        Position::withoutGlobalScopes()->upsert(
            $positions->all(),
            ['tenant_id', 'slug'],
            ['name', 'description', 'updated_at'],
        );
    }

    protected function syncDepartments(Tenant $tenant): void
    {
        $departments = collect(config('departments', []))
            ->filter(fn (array $department) => filled($department['name'] ?? null))
            ->map(fn (array $department) => [
                'tenant_id' => $tenant->id,
                'name' => trim($department['name']),
                'slug' => Str::slug($department['name']),
                'description' => filled($department['description'] ?? null)
                    ? trim($department['description'])
                    : null,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->unique('slug')
            ->values();

        if ($departments->isEmpty()) {
            return;
        }

        Department::withoutGlobalScopes()->upsert(
            $departments->all(),
            ['tenant_id', 'slug'],
            ['name', 'description', 'updated_at'],
        );
    }
}
