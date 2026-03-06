<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissionIds = Permission::query()
            ->where('is_active', true)
            ->pluck('id')
            ->all();

        if (empty($permissionIds)) {
            return;
        }

        $roles = Role::query()
            ->where('is_active', true)
            ->get(['id']);

        if ($roles->isEmpty()) {
            return;
        }

        $now = now();

        foreach ($roles as $role) {
            $rows = [];

            foreach ($permissionIds as $permissionId) {
                $rows[] = [
                    'role_id' => $role->id,
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::table('role_permissions')->upsert(
                $rows,
                ['role_id', 'permission_id'],
                ['updated_at']
            );
        }
    }
}
