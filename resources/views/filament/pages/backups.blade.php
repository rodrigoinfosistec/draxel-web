<x-filament-panels::page>
    <div class="space-y-6">
        <div class="flex justify-end">
            <x-filament::button wire:click="runBackupNow">
                Gerar backup agora
            </x-filament::button>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">ID</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Tipo</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Arquivo</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Solicitado por</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Iniciado</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Finalizado</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600">Erro</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($this->backups as $backup)
                            <tr>
                                <td class="px-4 py-3">{{ $backup['id'] }}</td>
                                <td class="px-4 py-3">{{ $backup['type'] }}</td>
                                <td class="px-4 py-3">{{ $backup['status'] }}</td>
                                <td class="px-4 py-3">{{ $backup['filename'] ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $backup['requester'] ?? 'Sistema' }}</td>
                                <td class="px-4 py-3">{{ $backup['started_at'] ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $backup['finished_at'] ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $backup['error_message'] ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-6 text-center text-gray-500">
                                    Nenhum backup registrado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
