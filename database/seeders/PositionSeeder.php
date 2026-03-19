<?php

namespace Database\Seeders;

use App\Models\Position;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PositionSeeder extends Seeder
{
    public function run(): void
    {
        $positions = collect(config('positions', []))
            ->filter(fn (array $position) => filled($position['name'] ?? null))
            ->map(fn (array $position) => [
                'name' => trim($position['name']),
                'slug' => Str::slug($position['name']),
                'description' => filled($position['description'] ?? null)
                    ? trim($position['description'])
                    : null,
            ])
            ->unique('slug')
            ->values();

        Tenant::query()
            ->select(['id'])
            ->lazy()
            ->each(function (Tenant $tenant) use ($positions) {
                $now = now();

                $rows = $positions
                    ->map(fn (array $position) => [
                        'tenant_id' => $tenant->id,
                        'name' => $position['name'],
                        'slug' => $position['slug'],
                        'description' => $position['description'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                    ->all();

                Position::withoutGlobalScopes()->upsert(
                    $rows,
                    ['tenant_id', 'slug'],
                    ['name', 'description', 'updated_at'],
                );
            });
    }
}
