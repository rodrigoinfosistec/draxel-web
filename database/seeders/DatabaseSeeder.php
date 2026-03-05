<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        /**
         * Seeds local e produção.
         */
        $this->call([
            TenantSeeder::class,
            CompanySeeder::class,
            UserSeeder::class,
        ]);
    }
}
