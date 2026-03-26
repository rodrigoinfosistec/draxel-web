<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, router } from '@inertiajs/vue3'
import { Download, FileClock, FileText, Search, Trash2, X } from 'lucide-vue-next'
import Swal from 'sweetalert2'
import { ref } from 'vue'

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
    can_delete: boolean
}

type PaginationLink = {
    url: string | null
    label: string
    active: boolean
}

const props = defineProps<{
    imports: {
        data: ImportItem[]
        links: PaginationLink[]
    }
    filters: {
        search: string
    }
}>()

const search = ref(props.filters.search ?? '')

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Ponto', href: '/worktime' },
    { title: 'Importações', href: '/worktime/clock-record-imports' },
]

const submitSearch = () => {
    router.get(
        '/worktime/clock-record-imports',
        {
            search: search.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    )
}

const clearSearch = () => {
    search.value = ''

    router.get(
        '/worktime/clock-record-imports',
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    )
}

const statusClass = (status: string) => {
    if (status === 'ready_to_launch') {
        return 'bg-sky-100 text-sky-700 ring-sky-200'
    }

    if (status === 'awaiting_review') {
        return 'bg-amber-100 text-amber-700 ring-amber-200'
    }

    if (status === 'launched') {
        return 'bg-emerald-100 text-emerald-700 ring-emerald-200'
    }

    if (status === 'processing') {
        return 'bg-violet-100 text-violet-700 ring-violet-200'
    }

    if (status === 'failed') {
        return 'bg-red-100 text-red-700 ring-red-200'
    }

    return 'bg-zinc-100 text-zinc-700 ring-zinc-200'
}

async function destroyImport(importId: number) {
    const result = await Swal.fire({
        title: 'Excluir importação?',
        text: 'Essa importação ainda não foi lançada e será removida definitivamente.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
    })

    if (!result.isConfirmed) {
        return
    }

    router.delete(`/worktime/clock-record-imports/${importId}`)
}
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

            <div class="rounded-xl border bg-card/50 p-4 shadow-sm">
                <form class="flex flex-col gap-3 sm:flex-row" @submit.prevent="submitSearch">
                    <div class="relative flex-1">
                        <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />

                        <input
                            v-model="search"
                            type="text"
                            placeholder="Buscar por arquivo, device ou status..."
                            class="w-full rounded-lg border bg-background px-10 py-2.5 text-sm outline-none transition focus:ring-2 focus:ring-ring"
                        />
                    </div>

                    <div class="flex gap-2">
                        <Button type="submit" variant="outline">
                            Buscar
                        </Button>

                        <Button
                            v-if="filters.search"
                            type="button"
                            variant="outline"
                            @click="clearSearch"
                        >
                            <X class="mr-2 h-4 w-4" />
                            Limpar
                        </Button>
                    </div>
                </form>
            </div>

            <div class="rounded-xl border bg-card/50 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-[920px] w-full text-sm">
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
                                <td class="px-4 py-3">
                                    <div class="font-medium text-foreground">
                                        {{ item.original_filename }}
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    {{ item.device_name || '—' }}
                                </td>

                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset"
                                        :class="statusClass(item.status)"
                                    >
                                        {{ item.status_label }}
                                    </span>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex flex-col gap-1">
                                        <span class="text-foreground">
                                            {{ item.valid_items }}/{{ item.total_items }} válidos
                                        </span>

                                        <span
                                            v-if="item.invalid_items > 0"
                                            class="text-xs font-medium text-red-600"
                                        >
                                            {{ item.invalid_items }} divergentes
                                        </span>
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    {{ item.created_at || '—' }}
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <Link
                                            :href="`/worktime/clock-record-imports/${item.id}`"
                                            class="inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-medium transition hover:bg-muted"
                                        >
                                            Ver
                                        </Link>

                                        <Can permission="worktime.deleteClockRecordImport">
                                            <Button
                                                v-if="item.can_delete"
                                                variant="outline"
                                                type="button"
                                                class="border-red-200 text-red-600 hover:bg-red-50 hover:text-red-700"
                                                @click="destroyImport(item.id)"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </Can>
                                    </div>
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
