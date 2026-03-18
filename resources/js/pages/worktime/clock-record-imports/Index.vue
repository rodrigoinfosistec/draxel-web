<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link } from '@inertiajs/vue3'
import { Download, FileClock, FileText } from 'lucide-vue-next'

type ImportItem = {
    id: number
    original_filename: string
    status: string
    status_label: string
    device_name: string | null
    total_items: number
    valid_items: number
    invalid_items: number
    created_at: string | null
}

type PaginationLink = {
    url: string | null
    label: string
    active: boolean
}

defineProps<{
    imports: {
        data: ImportItem[]
        links: PaginationLink[]
    }
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Ponto', href: '/worktime' },
    { title: 'Importações', href: '/worktime/clock-record-imports' },
]
</script>

<template>
    <Head title="Importações de ponto" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <div class="relative overflow-hidden rounded-2xl border bg-card/40 p-4 shadow-sm backdrop-blur-[1px] sm:p-5">
                <div
                    class="absolute inset-0"
                    style="background: linear-gradient(to bottom right, var(--company-color-soft), transparent, transparent);"
                />

                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Importações de ponto"
                        description="Envie arquivos TXT, revise divergências e lance os registros."
                        :icon="FileClock"
                    />

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Can permission="worktime.exportClockRecordImport">
                            <Button as-child variant="outline" class="w-full sm:w-auto">
                                <a href="/worktime/clock-record-imports/export/csv">
                                    <Download class="mr-2 h-4 w-4" />
                                    Exportar CSV
                                </a>
                            </Button>
                        </Can>

                        <Can permission="worktime.exportClockRecordImport">
                            <Button as-child variant="outline" class="w-full sm:w-auto">
                                <a href="/worktime/clock-record-imports/export/pdf">
                                    <FileText class="mr-2 h-4 w-4" />
                                    Exportar PDF
                                </a>
                            </Button>
                        </Can>

                        <Can permission="worktime.createClockRecordImport">
                            <Link href="/worktime/clock-record-imports/create" class="w-full sm:w-auto">
                                <Button class="w-full sm:w-auto">Nova importação</Button>
                            </Link>
                        </Can>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border bg-card/50 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-[760px] w-full text-sm">
                        <thead class="bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left">Arquivo</th>
                                <th class="px-4 py-3 text-left">Device</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-left">Itens</th>
                                <th class="px-4 py-3 text-left">Criado em</th>
                                <th class="px-4 py-3 text-right">Ações</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="item in imports.data" :key="item.id" class="border-t">
                                <td class="px-4 py-3">{{ item.original_filename }}</td>
                                <td class="px-4 py-3">{{ item.device_name || '—' }}</td>
                                <td class="px-4 py-3">{{ item.status_label }}</td>
                                <td class="px-4 py-3">
                                    {{ item.valid_items }}/{{ item.total_items }} válidos
                                    <span v-if="item.invalid_items > 0"> • {{ item.invalid_items }} divergentes</span>
                                </td>
                                <td class="px-4 py-3">{{ item.created_at || '—' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <Link
                                        :href="`/worktime/clock-record-imports/${item.id}`"
                                        class="inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-medium transition hover:bg-muted"
                                    >
                                        Ver
                                    </Link>
                                </td>
                            </tr>

                            <tr v-if="imports.data.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">
                                    Nenhuma importação encontrada.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <Link
                    v-for="link in imports.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    v-html="link.label"
                    class="rounded-md border px-3 py-2 text-sm"
                    :class="{
                        'bg-muted': link.active,
                        'pointer-events-none opacity-50': !link.url,
                    }"
                />
            </div>
        </div>
    </AppLayout>
</template>
