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
         * Seeds essenciais
         */
        $this->call([
            /**
             * Módulos globais (definidas os "cores")
             * Permissões globais (vinculadas aos módulos)
             */
            ModuleSeeder::class,

            /**
             * Painel Global (Dpanel)
             * Permissão total
             */
            DpanelSeeder::class,
        ]);

        /**
         * Seeds apenas para ambiente local
         */
        if (app()->environment('local')) {
            // Seeds
            $this->call([
                //
            ]);
        }
    }
}
