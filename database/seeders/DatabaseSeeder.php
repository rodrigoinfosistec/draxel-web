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
         * Seeds apenas para ambiente local
         */
        if (app()->environment('local')) {
            // Seeds
            $this->call([
                //
            ]);
        }

        /**
         * Seeds essenciais depois dos syncs
         */
        $this->call([
            TenantSeeder::class,
            CompanySeeder::class,
            UserSeeder::class,
            RoleSeeder::class,
            ModuleSeeder::class,
            RolePermissionSeeder::class,
        ]);
    }
}
