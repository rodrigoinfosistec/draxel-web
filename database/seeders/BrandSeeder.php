<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            '3M',
            'Ambev',
            'Apple',
            'Astra',
            'Bosch',
            'Brastemp',
            'Colgate',
            'Consul',
            'Coca-Cola',
            'Dell',
            'Esmaltec',
            'Fortlev',
            'LG',
            'Motorola',
            'Natura',
            'Nestlé',
            'Philips',
            'Samsung',
            'Suvinil',
            'Tigre',
            'Tramontina',
            'Vonder',
            'Ypê',
        ];

        Tenant::query()->select('id')->chunkById(100, function ($tenants) use ($brands) {
            foreach ($tenants as $tenant) {
                foreach ($brands as $brand) {
                    Brand::query()->firstOrCreate(
                        [
                            'tenant_id' => $tenant->id,
                            'name' => $brand,
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
