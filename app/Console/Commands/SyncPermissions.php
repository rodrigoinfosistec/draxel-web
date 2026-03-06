<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Permission;

class SyncPermissions extends Command
{
    protected $signature = 'permissions:sync';

    protected $description = 'Sincroniza as permissões definidas em config/permissions.php com o banco de dados';

    public function handle(): int
    {
        $map = config('permissions');

        $total = 0;

        foreach ($map as $module => $actions) {

            foreach ($actions as $action => $data) {

                $slug = "{$module}.{$action}";

                Permission::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'name' => $data['name'],
                        'description' => $data['description'] ?? null,
                        'is_active' => true,
                    ]
                );

                $this->line("Synced: {$slug}");

                $total++;
            }
        }

        $this->info("Total de permissões sincronizadas: {$total}");

        return Command::SUCCESS;
    }
}
