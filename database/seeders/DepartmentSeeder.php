<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = collect(config('departments', []))
            ->filter(fn (array $department) => filled($department['name'] ?? null))
            ->map(fn (array $department) => [
                'name' => trim($department['name']),
                'slug' => Str::slug($department['name']),
                'description' => filled($department['description'] ?? null)
                    ? trim($department['description'])
                    : null,
            ])
            ->unique('slug')
            ->values();

        Tenant::query()
            ->select(['id'])
            ->lazy()
            ->each(function (Tenant $tenant) use ($departments) {
                $now = now();

                $rows = $departments
                    ->map(fn (array $department) => [
                        'tenant_id' => $tenant->id,
                        'name' => $department['name'],
                        'slug' => $department['slug'],
                        'description' => $department['description'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                    ->all();

                Department::withoutGlobalScopes()->upsert(
                    $rows,
                    ['tenant_id', 'slug'],
                    ['name', 'description', 'updated_at'],
                );
            });
    }
}
