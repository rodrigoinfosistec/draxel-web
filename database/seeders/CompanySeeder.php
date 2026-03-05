<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            'dpanel' => [
                ['Empresa Global', 'Empresa Global', '00000000000000', '#7C3AED'],
            ],
            'grupomix' => [
                ['Armarinho Mundial', 'Mundial', '52801379000132', '#DC2626'],
                ['Mix Distribuidora', 'Mix', '33059026000164', '#1255e4'],
                ['Atacado dos Fogões', 'Atacado', '45966395000110', '#16A34A'],
            ],
            'extenfort' => [
                ['Extenfort', 'Extenfort', '12345678901234', '#EA580C'],
            ],
            'construlaje' => [
                ['Construlaje Joana Bezerra', 'Joana Bezerra', '11784192000190', '#2563EB'],
                ['Construlaje Brasilia Teimosa', 'Brasilia Teimosa', '57841206000161', '#DC2626'],
            ],
        ];

        foreach ($data as $tenantSlug => $companies) {
            $tenant = Tenant::where('slug', $tenantSlug)->first();

            if (! $tenant) {
                continue;
            }

            foreach ($companies as [$name, $alias, $cnpj, $color]) {
                Company::withoutGlobalScopes()->updateOrCreate(
                    ['cnpj' => $cnpj],
                    [
                        'tenant_id' => $tenant->id,
                        'name'      => $name,
                        'alias'     => $alias,
                        'color'     => $color,
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
