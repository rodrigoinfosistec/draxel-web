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

        /**
         * Seeds essenciais depois dos syncs
         */
        $this->call([
            TenantSeeder::class,
            CompanySeeder::class,
            UserSeeder::class,
            RoleSeeder::class,
            RolePermissionSeeder::class,
        ]);
    }
}
