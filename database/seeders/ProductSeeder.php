<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Models\UnitOfMeasure;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'name' => 'Cimento CP-II 50kg',
                'sku' => 'CIM-CP2-50',
                'category' => 'Material de Consumo',
                'brand' => 'Nassau',
                'unit' => 'SC',
                'description' => 'Cimento ensacado 50kg',
                'tracks_stock' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Areia Média',
                'sku' => 'ARE-MED-001',
                'category' => 'Matéria-Prima',
                'brand' => null,
                'unit' => 'M3',
                'description' => 'Areia média para uso geral',
                'tracks_stock' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Brita 19mm',
                'sku' => 'BRI-019-001',
                'category' => 'Matéria-Prima',
                'brand' => null,
                'unit' => 'M3',
                'description' => 'Brita para concreto e construção',
                'tracks_stock' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Tubo Soldável 25mm',
                'sku' => 'TUB-SLD-25',
                'category' => 'Hidráulico',
                'brand' => 'Tigre',
                'unit' => 'UN',
                'description' => 'Tubo soldável PVC 25mm',
                'tracks_stock' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Joelho Soldável 25mm',
                'sku' => 'JOE-SLD-25',
                'category' => 'Hidráulico',
                'brand' => 'Tigre',
                'unit' => 'UN',
                'description' => 'Conexão joelho soldável 25mm',
                'tracks_stock' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Cabo Flexível 2,5mm',
                'sku' => 'CAB-FLX-25',
                'category' => 'Elétrico',
                'brand' => null,
                'unit' => 'M',
                'description' => 'Cabo flexível para instalações elétricas',
                'tracks_stock' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Disjuntor Monopolar 20A',
                'sku' => 'DIS-MON-20',
                'category' => 'Elétrico',
                'brand' => null,
                'unit' => 'UN',
                'description' => 'Disjuntor monopolar 20 amperes',
                'tracks_stock' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Parafuso Sextavado 5/16',
                'sku' => 'PAR-SEX-516',
                'category' => 'Ferragens',
                'brand' => null,
                'unit' => 'UN',
                'description' => 'Parafuso sextavado 5/16',
                'tracks_stock' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Furadeira de Impacto 650W',
                'sku' => 'FUR-IMP-650',
                'category' => 'Ferramentas',
                'brand' => 'Bosch',
                'unit' => 'UN',
                'description' => 'Furadeira elétrica de impacto 650W',
                'tracks_stock' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Luva de Proteção',
                'sku' => 'LUV-PRO-001',
                'category' => 'EPIs',
                'brand' => '3M',
                'unit' => 'PAR',
                'description' => 'Luva de proteção para uso operacional',
                'tracks_stock' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Detergente Neutro 500ml',
                'sku' => 'DET-NEU-500',
                'category' => 'Material de Limpeza',
                'brand' => 'Ypê',
                'unit' => 'UN',
                'description' => 'Detergente neutro frasco 500ml',
                'tracks_stock' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Papel A4',
                'sku' => 'PAP-A4-500',
                'category' => 'Material de Escritório',
                'brand' => null,
                'unit' => 'PCT',
                'description' => 'Papel A4 pacote com 500 folhas',
                'tracks_stock' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Notebook 15 Polegadas',
                'sku' => 'NOT-15-001',
                'category' => 'Informática',
                'brand' => 'Dell',
                'unit' => 'UN',
                'description' => 'Notebook para uso administrativo',
                'tracks_stock' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Produto de Catálogo',
                'sku' => 'CAT-NAOEST-001',
                'category' => 'Outros',
                'brand' => null,
                'unit' => 'UN',
                'description' => 'Produto sem controle de estoque para testes',
                'tracks_stock' => false,
                'is_active' => true,
            ],
        ];

        Tenant::query()->select('id')->chunkById(100, function ($tenants) use ($products) {
            foreach ($tenants as $tenant) {
                foreach ($products as $item) {
                    $category = ProductCategory::query()
                        ->where('tenant_id', $tenant->id)
                        ->where('name', $item['category'])
                        ->first();

                    $unitOfMeasure = UnitOfMeasure::query()
                        ->where('tenant_id', $tenant->id)
                        ->where('symbol', $item['unit'])
                        ->first();

                    $brand = null;

                    if ($item['brand']) {
                        $brand = Brand::query()
                            ->where('tenant_id', $tenant->id)
                            ->where('name', $item['brand'])
                            ->first();
                    }

                    if (! $category || ! $unitOfMeasure) {
                        continue;
                    }

                    Product::query()->firstOrCreate(
                        [
                            'tenant_id' => $tenant->id,
                            'sku' => $item['sku'],
                        ],
                        [
                            'product_category_id' => $category->id,
                            'unit_of_measure_id' => $unitOfMeasure->id,
                            'brand_id' => $brand?->id,
                            'name' => $item['name'],
                            'description' => $item['description'],
                            'tracks_stock' => $item['tracks_stock'],
                            'is_active' => $item['is_active'],
                        ]
                    );
                }
            }
        });
    }
}
