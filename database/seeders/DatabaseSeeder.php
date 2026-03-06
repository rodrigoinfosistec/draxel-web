<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        /**
         * Seeds essenciais (rodarão em qualquer ambiente)
         */
        $this->call([
            TenantSeeder::class,
            CompanySeeder::class,
            UserSeeder::class,
            RoleSeeder::class,
            RolePermissionSeeder::class,
        ]);

        /**
         * Seeds apenas para ambiente local
         */
        if (app()->environment('local')) {

            // Syncs
            Artisan::call('permissions:sync');
            Artisan::call('modules:sync');

            // Seeds
            $this->call([
                //
            ]);

        }
    }
}
