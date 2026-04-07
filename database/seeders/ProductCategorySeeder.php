<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Alimentos',
            'Bebidas',
            'Material de Limpeza',
            'Higiene Pessoal',
            'Material de Escritório',
            'Informática',
            'Elétrico',
            'Hidráulico',
            'Ferragens',
            'Ferramentas',
            'EPIs',
            'Utilidades',
            'Embalagens',
            'Têxtil',
            'Automotivo',
            'Peças e Acessórios',
            'Matéria-Prima',
            'Produto Acabado',
            'Material de Consumo',
            'Outros',
        ];

        Tenant::query()->select('id')->chunkById(100, function ($tenants) use ($categories) {
            foreach ($tenants as $tenant) {
                foreach ($categories as $category) {
                    ProductCategory::query()->firstOrCreate(
                        [
                            'tenant_id' => $tenant->id,
                            'name' => $category,
                        ],
                        [
                            'description' => null,
                            'is_active' => true,
                        ]
                    );
                }
            }
        });
    }
}
