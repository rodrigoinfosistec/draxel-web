<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class TestIntegrationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $filename = 'test-' . now()->timestamp . '.txt';

        $content = "Teste de integração\n";
        $content .= "Data: " . now()->toDateTimeString();

        Storage::disk('s3')->put($filename, $content);

        $exists = Storage::disk('s3')->exists($filename);

        logger()->info('Teste integração executado', [
            'file' => $filename,
            'exists_in_s3' => $exists,
        ]);

        Mail::raw(
            "Arquivo criado com sucesso.\n\nArquivo: {$filename}\nExiste no S3: " . ($exists ? 'SIM' : 'NÃO'),
            function ($message) {
                $message->to('rodrigo.infosistec@gmail.com')
                        ->subject('Teste Integração Draxel');
            }
        );
    }
}
