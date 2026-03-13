<div class="space-y-6">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">ID</div>
            <div class="mt-1 text-sm text-gray-950 dark:text-white">{{ $record->id }}</div>
        </div>

        <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Evento</div>
            <div class="mt-1 text-sm text-gray-950 dark:text-white">{{ $record->event }}</div>
        </div>

        <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Tenant</div>
            <div class="mt-1 text-sm text-gray-950 dark:text-white">{{ $record->tenant_id }}</div>
        </div>

        <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Empresa</div>
            <div class="mt-1 text-sm text-gray-950 dark:text-white">{{ $record->company?->name ?? '-' }}</div>
        </div>

        <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Usuário</div>
            <div class="mt-1 text-sm text-gray-950 dark:text-white">{{ $record->user?->name ?? '-' }}</div>
        </div>

        <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Data</div>
            <div class="mt-1 text-sm text-gray-950 dark:text-white">{{ $record->created_at?->format('d/m/Y H:i:s') }}
            </div>
        </div>

        <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10 md:col-span-2">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Entidade</div>
            <div class="mt-1 text-sm text-gray-950 dark:text-white">{{ $record->subject_type ?? '-' }}</div>
        </div>

        <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">ID da entidade</div>
            <div class="mt-1 text-sm text-gray-950 dark:text-white">{{ $record->subject_id ?? '-' }}</div>
        </div>

        <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Método</div>
            <div class="mt-1 text-sm text-gray-950 dark:text-white">{{ $record->method ?? '-' }}</div>
        </div>

        <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10 md:col-span-2">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Rota</div>
            <div class="mt-1 break-all text-sm text-gray-950 dark:text-white">{{ $record->route ?? '-' }}</div>
        </div>

        <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">IP</div>
            <div class="mt-1 text-sm text-gray-950 dark:text-white">{{ $record->ip ?? '-' }}</div>
        </div>

        <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">User Agent</div>
            <div class="mt-1 break-all text-sm text-gray-950 dark:text-white">{{ $record->user_agent ?? '-' }}</div>
        </div>

        <div class="rounded-xl border border-gray-200 p-4 dark:border-white/10 md:col-span-2">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Properties</div>
            <pre class="mt-2 overflow-x-auto rounded-lg bg-gray-50 p-4 text-xs text-gray-950 dark:bg-white/5 dark:text-white">{{ json_encode($record->properties ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
        </div>
    </div>
</div>
