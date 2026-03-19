<?php

namespace App\Observers;

use App\Models\Department;
use App\Models\Position;
use App\Models\Tenant;
use Illuminate\Support\Str;

class TenantObserver
{
    public function created(Tenant $tenant): void
    {
        $this->syncPositions($tenant);
        $this->syncDepartments($tenant);
    }

    protected function syncPositions(Tenant $tenant): void
    {
        $positions = collect(config('positions', []))
            ->filter(fn (array $position) => filled($position['name'] ?? null))
            ->map(fn (array $position) => [
                'tenant_id' => $tenant->id,
                'name' => trim($position['name']),
                'slug' => Str::slug($position['name']),
                'description' => filled($position['description'] ?? null)
                    ? trim($position['description'])
                    : null,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->unique('slug')
            ->values();

        if ($positions->isEmpty()) {
            return;
        }

        Position::withoutGlobalScopes()->upsert(
            $positions->all(),
            ['tenant_id', 'slug'],
            ['name', 'description', 'updated_at'],
        );
    }

    protected function syncDepartments(Tenant $tenant): void
    {
        $departments = collect(config('departments', []))
            ->filter(fn (array $department) => filled($department['name'] ?? null))
            ->map(fn (array $department) => [
                'tenant_id' => $tenant->id,
                'name' => trim($department['name']),
                'slug' => Str::slug($department['name']),
                'description' => filled($department['description'] ?? null)
                    ? trim($department['description'])
                    : null,
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->unique('slug')
            ->values();

        if ($departments->isEmpty()) {
            return;
        }

        Department::withoutGlobalScopes()->upsert(
            $departments->all(),
            ['tenant_id', 'slug'],
            ['name', 'description', 'updated_at'],
        );
    }
}
