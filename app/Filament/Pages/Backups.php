<?php

namespace App\Filament\Pages;

use App\Jobs\RunDatabaseBackup;
use App\Models\DatabaseBackup;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class Backups extends Page
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?string $navigationLabel = 'Backups';

    protected static ?string $title = 'Backups';

    protected static ?string $slug = 'backups';

    protected static ?int $navigationSort = 90;

    protected string $view = 'filament.pages.backups';

    public array $backups = [];

    public function mount(): void
    {
        $this->loadBackups();
    }

    public function loadBackups(): void
    {
        $this->backups = DatabaseBackup::query()
            ->with('requester:id,name,email')
            ->latest()
            ->limit(30)
            ->get()
            ->map(fn (DatabaseBackup $backup) => [
                'id' => $backup->id,
                'type' => $backup->type,
                'status' => $backup->status,
                'disk' => $backup->disk,
                'filename' => $backup->filename,
                'path' => $backup->path,
                'size' => $backup->size,
                'started_at' => $backup->started_at?->format('d/m/Y H:i:s'),
                'finished_at' => $backup->finished_at?->format('d/m/Y H:i:s'),
                'error_message' => $backup->error_message,
                'requester' => $backup->requester?->name,
            ])
            ->toArray();
    }

    public function runBackupNow(): void
    {
        $backup = DatabaseBackup::query()->create([
            'type' => 'manual',
            'status' => 'pending',
            'disk' => 's3',
            'requested_by' => Auth::id(),
        ]);

        RunDatabaseBackup::dispatch($backup->id);

        Notification::make()
            ->title('Backup iniciado com sucesso.')
            ->success()
            ->send();

        $this->loadBackups();
    }
}
