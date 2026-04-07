<?php

namespace Database\Seeders;

use App\Modules\Worktime\Database\Seeders\WorktimeSeeder;
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
             * Módulo de Controle de Ponto
             * Dispositivos de ponto suportados
             */
            WorktimeSeeder::class,

            /**
             * Painel Global (Dpanel)
             * Permissão total
             */
            DpanelSeeder::class,

            /**
             * Departamentos onde ficam alocados os funcionários
             */
            DepartmentSeeder::class,

            /**
             * Cargos de funcionários
             */
            PositionSeeder::class,

            /**
             * Base Construlaje
             */
            ConstrulajeSeeder::class,

            /**
             * Categorias de produto
             */
            ProductCategorySeeder::class,
        ]);

        /**
         * Seeds apenas para ambiente local
         */
        if (app()->environment('local')) {
            // Seeds
            $this->call([
                /**
                 * Marcas
                 */
                BrandSeeder::class,
            ]);
        }
    }
}
