<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = [
            ['slug' => 'dpanel',      'name' => 'Painel Global'],
            ['slug' => 'grupomix',    'name' => 'Grupo Mix'],
            ['slug' => 'extenfort',   'name' => 'Extenfort'],
            ['slug' => 'construlaje', 'name' => 'Construlaje'],
        ];

        foreach ($tenants as $data) {
            Tenant::updateOrCreate(
                ['slug' => $data['slug']],
                $data
            );
        }
    }
}
