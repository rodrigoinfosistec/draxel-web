<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Module;
use App\Models\Permission;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        $modules = config('modules');

        foreach ($modules as $moduleSlug => $moduleData) {

            $module = Module::updateOrCreate(
                ['slug' => $moduleSlug],
                [
                    'name' => $moduleData['name'],
                    'description' => $moduleData['description'] ?? null,
                    'is_core' => $moduleData['is_core'] ?? false,
                    'is_active' => true,
                ]
            );

            if (!isset($moduleData['permissions'])) {
                continue;
            }

            foreach ($moduleData['permissions'] as $action => $permissionData) {

                $permissionSlug = "{$moduleSlug}.{$action}";

                $permission = Permission::updateOrCreate(
                    ['slug' => $permissionSlug],
                    [
                        'name' => $permissionData['name'],
                        'description' => $permissionData['description'] ?? null,
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
    }
}
