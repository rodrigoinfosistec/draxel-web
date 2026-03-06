<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Module;

class SyncModules extends Command
{
    protected $signature = 'modules:sync';

    protected $description = 'Sincroniza os módulos definidos em config/modules.php';

    public function handle(): int
    {
        $modules = config('modules');

        $count = 0;

        foreach ($modules as $slug => $data) {

            Module::updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                    'is_active' => true,
                ]
            );

            $this->line("Synced: {$slug}");

            $count++;
        }

        $this->info("Total de módulos sincronizados: {$count}");

        return Command::SUCCESS;
    }
}
