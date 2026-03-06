<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Module;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;

class SyncModules extends Command
{
    protected $signature = 'modules:sync';

    protected $description = 'Sincroniza módulos e permissões definidas em config/modules.php';

    public function handle(): int
    {
        $modules = config('modules');

        foreach ($modules as $slug => $data) {

            $module = Module::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                    'is_active' => true,
                ]
            );

            foreach ($data['permissions'] as $action => $perm) {

                $permissionSlug = "{$slug}.{$action}";

                $permission = Permission::updateOrCreate(
                    ['slug' => $permissionSlug],
                    [
                        'name' => $perm['name'],
                        'description' => $perm['description'] ?? null,
                        'is_active' => true,
                    ]
                );

                DB::table('module_permissions')->updateOrInsert(
                    [
                        'module_id' => $module->id,
                        'permission_id' => $permission->id,
                    ],
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }

        $this->info('Modules e permissions sincronizados.');

        return Command::SUCCESS;
    }
}
