<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;

class SupplierPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            [
                'module' => 'suppliers',
                'slug' => 'suppliers.viewAny',
                'name' => 'Ver lista de fornecedores',
                'description' => 'Permite visualizar a lista de fornecedores do tenant',
            ],
            [
                'module' => 'suppliers',
                'slug' => 'suppliers.view',
                'name' => 'Ver fornecedor',
                'description' => 'Permite visualizar os dados de um fornecedor',
            ],
            [
                'module' => 'suppliers',
                'slug' => 'suppliers.create',
                'name' => 'Cadastrar fornecedor',
                'description' => 'Permite cadastrar novos fornecedores',
            ],
            [
                'module' => 'suppliers',
                'slug' => 'suppliers.update',
                'name' => 'Atualizar fornecedor',
                'description' => 'Permite editar fornecedores',
            ],
            [
                'module' => 'suppliers',
                'slug' => 'suppliers.delete',
                'name' => 'Excluir fornecedor',
                'description' => 'Permite excluir fornecedores',
            ],
            [
                'module' => 'supplierProductReferences',
                'slug' => 'supplierProductReferences.viewAny',
                'name' => 'Ver lista de vínculos fornecedor x produto',
                'description' => 'Permite visualizar a lista de vínculos comerciais entre fornecedores e produtos',
            ],
            [
                'module' => 'supplierProductReferences',
                'slug' => 'supplierProductReferences.view',
                'name' => 'Ver vínculo fornecedor x produto',
                'description' => 'Permite visualizar os dados de um vínculo comercial entre fornecedor e produto',
            ],
            [
                'module' => 'supplierProductReferences',
                'slug' => 'supplierProductReferences.create',
                'name' => 'Cadastrar vínculo fornecedor x produto',
                'description' => 'Permite cadastrar novos vínculos comerciais entre fornecedores e produtos',
            ],
            [
                'module' => 'supplierProductReferences',
                'slug' => 'supplierProductReferences.update',
                'name' => 'Atualizar vínculo fornecedor x produto',
                'description' => 'Permite editar vínculos comerciais entre fornecedores e produtos',
            ],
            [
                'module' => 'supplierProductReferences',
                'slug' => 'supplierProductReferences.delete',
                'name' => 'Excluir vínculo fornecedor x produto',
                'description' => 'Permite excluir vínculos comerciais entre fornecedores e produtos',
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::query()->updateOrCreate(
                ['slug' => $permission['slug']],
                [
                    'module' => $permission['module'],
                    'name' => $permission['name'],
                    'description' => $permission['description'],
                ]
            );
        }
    }
}
