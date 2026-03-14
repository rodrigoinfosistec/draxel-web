<?php

namespace App\Jobs;

use App\Models\DatabaseBackup;
use App\Support\Audit\Audit;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use Throwable;

class RunDatabaseBackup implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $backupId,
    ) {}

    public function handle(): void
    {
        $backup = DatabaseBackup::query()->findOrFail($this->backupId);

        $backup->update([
            'status' => 'processing',
            'started_at' => now(),
            'error_message' => null,
        ]);

        $databaseUrl = env('DATABASE_URL');

        if (! $databaseUrl) {
            $connection = config('database.connections.pgsql');

            $host = $connection['host'] ?? null;
            $port = $connection['port'] ?? '5432';
            $database = $connection['database'] ?? null;
            $username = $connection['username'] ?? null;
            $password = $connection['password'] ?? null;

            if ($host && $database && $username !== null) {
                $encodedUsername = rawurlencode($username);
                $encodedPassword = rawurlencode((string) $password);

                $databaseUrl = "postgresql://{$encodedUsername}:{$encodedPassword}@{$host}:{$port}/{$database}";
            }
        }

        if (! $databaseUrl) {
            $backup->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error_message' => 'Não foi possível montar a conexão do banco para o pg_dump.',
            ]);

            return;
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "database-backup-{$timestamp}-{$backup->id}.dump";
        $tempPath = storage_path("app/temp/{$filename}");
        $remotePath = "backups/database/{$filename}";

        if (! is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0775, true);
        }

        try {
            $result = Process::timeout(600)->run([
                'pg_dump',
                $databaseUrl,
                '--format=custom',
                '--file=' . $tempPath,
            ]);

            if ($result->failed()) {
                throw new \RuntimeException(
                    trim($result->errorOutput() ?: $result->output() ?: 'Falha ao executar pg_dump.')
                );
            }

            $stream = fopen($tempPath, 'r');

            if (! $stream) {
                throw new \RuntimeException('Não foi possível abrir o arquivo temporário do backup.');
            }

            Storage::disk($backup->disk)->put($remotePath, $stream);

            fclose($stream);

            $size = filesize($tempPath) ?: null;

            $backup->update([
                'status' => 'completed',
                'path' => $remotePath,
                'filename' => $filename,
                'size' => $size,
                'finished_at' => now(),
            ]);

            if ($backup->requested_by) {
                Audit::event('database_backups.completed', $backup, [
                    'filename' => $filename,
                    'disk' => $backup->disk,
                    'path' => $remotePath,
                    'size' => $size,
                ]);
            }
        } catch (Throwable $e) {
            $backup->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            if ($backup->requested_by) {
                Audit::event('database_backups.failed', $backup, [
                    'error' => $e->getMessage(),
                ]);
            }
        } finally {
            if (is_file($tempPath)) {
                @unlink($tempPath);
            }
        }
    }
}
