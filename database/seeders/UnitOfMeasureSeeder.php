<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\UnitOfMeasure;
use Illuminate\Database\Seeder;

class UnitOfMeasureSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['name' => 'Unidade', 'symbol' => 'UN'],
            ['name' => 'Caixa', 'symbol' => 'CX'],
            ['name' => 'Pacote', 'symbol' => 'PCT'],
            ['name' => 'Quilograma', 'symbol' => 'KG'],
            ['name' => 'Grama', 'symbol' => 'G'],
            ['name' => 'Litro', 'symbol' => 'LT'],
            ['name' => 'Mililitro', 'symbol' => 'ML'],
            ['name' => 'Metro', 'symbol' => 'M'],
            ['name' => 'Centímetro', 'symbol' => 'CM'],
            ['name' => 'Milímetro', 'symbol' => 'MM'],
            ['name' => 'Metro Quadrado', 'symbol' => 'M2'],
            ['name' => 'Metro Cúbico', 'symbol' => 'M3'],
            ['name' => 'Par', 'symbol' => 'PAR'],
            ['name' => 'Jogo', 'symbol' => 'JG'],
            ['name' => 'Rolo', 'symbol' => 'RL'],
            ['name' => 'Saco', 'symbol' => 'SC'],
            ['name' => 'Galão', 'symbol' => 'GL'],
            ['name' => 'Lata', 'symbol' => 'LAT'],
            ['name' => 'Frasco', 'symbol' => 'FR'],
        ];

        Tenant::query()->select('id')->chunkById(100, function ($tenants) use ($units) {
            foreach ($tenants as $tenant) {
                foreach ($units as $unit) {
                    UnitOfMeasure::query()->firstOrCreate(
                        [
                            'tenant_id' => $tenant->id,
                            'symbol' => $unit['symbol'],
                        ],
                        [
                            'name' => $unit['name'],
                            'description' => null,
                            'is_active' => true,
                        ]
                    );
                }
            }
        });
    }
}
