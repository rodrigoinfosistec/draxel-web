<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tenant;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'slug' => 'admin',
                'name' => 'Administrador',
                'description' => 'Acesso completo ao sistema'
            ],
            [
                'slug' => 'manager',
                'name' => 'Gerente',
                'description' => 'Gestão operacional'
            ],
            [
                'slug' => 'operator',
                'name' => 'Operador',
                'description' => 'Usuário operacional'
            ],
        ];

        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {

            foreach ($roles as $role) {

                Role::updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'slug' => $role['slug']
                    ],
                    [
                        'name' => $role['name'],
                        'description' => $role['description'],
                        'is_active' => true
                    ]
                );

            }
        }
    }
}
