<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, router } from '@inertiajs/vue3'
import { CalendarClock, Download, FileText, Pencil, Plus, Search } from 'lucide-vue-next'
import { ref } from 'vue'

type EventItem = {
    id: number
    employee_name: string | null
    event_type: string | null
    event_type_label: string | null
    starts_at: string | null
    ends_at: string | null
    notes: string | null
}

type PaginationLink = {
    url: string | null
    label: string
    active: boolean
}

const props = defineProps<{
    events: {
        data: EventItem[]
        links: PaginationLink[]
    }
    filters: {
        search: string
    }
}>()

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Ponto',
        href: '/worktime',
    },
    {
        title: 'Eventos de funcionário',
        href: '/worktime/employee-events',
    },
]

const search = ref(props.filters.search ?? '')

function submitSearch() {
    router.get(
        '/worktime/employee-events',
        {
            search: search.value || undefined,
        },
        {
            preserveState: true,
            replace: true,
        },
    )
}
</script>

<template>
    <Head title="Eventos de funcionário" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <div
                class="relative overflow-hidden rounded-2xl border bg-card/40 p-4 shadow-sm backdrop-blur-[1px] sm:p-5"
            >
                <div
                    class="absolute inset-0"
                    style="background: linear-gradient(to bottom right, var(--company-color-soft), transparent, transparent);"
                />

                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Eventos de funcionário"
                        description="Gerencie períodos de afastamento, justificativas e compensações."
                        :icon="CalendarClock"
                    />

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Can permission="worktime.export">
                            <Button as-child variant="outline" class="w-full sm:w-auto">
                                <a :href="`/worktime/employee-events/export/csv?search=${encodeURIComponent(search)}`">
                                    <Download class="mr-2 h-4 w-4" />
                                    Exportar CSV
                                </a>
                            </Button>
                        </Can>

                        <Can permission="worktime.export">
                            <Button as-child variant="outline" class="w-full sm:w-auto">
                                <a :href="`/worktime/employee-events/export/pdf?search=${encodeURIComponent(search)}`">
                                    <FileText class="mr-2 h-4 w-4" />
                                    Exportar PDF
                                </a>
                            </Button>
                        </Can>

                        <Can permission="worktime.create">
                            <Link href="/worktime/employee-events/create" class="w-full sm:w-auto">
                                <Button class="w-full sm:w-auto">
                                    <Plus class="mr-2 h-4 w-4" />
                                    Novo evento
                                </Button>
                            </Link>
                        </Can>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border bg-card/50 p-4 shadow-sm">
                <form class="flex flex-col gap-3 sm:flex-row" @submit.prevent="submitSearch">
                    <div class="relative flex-1">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Buscar por funcionário"
                            class="flex h-10 w-full rounded-md border bg-card pl-9 pr-3 py-2 text-sm"
                        >
                    </div>

                    <Button type="submit" variant="outline" class="w-full sm:w-auto">
                        Filtrar
                    </Button>
                </form>
            </div>

            <div class="rounded-xl border bg-card/50 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-[980px] w-full text-sm">
                        <thead class="bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left">Funcionário</th>
                                <th class="px-4 py-3 text-left">Tipo</th>
                                <th class="px-4 py-3 text-left">Início</th>
                                <th class="px-4 py-3 text-left">Fim</th>
                                <th class="px-4 py-3 text-left">Observações</th>
                                <th class="px-4 py-3 text-right">Ações</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="event in events.data"
                                :key="event.id"
                                class="border-t"
                            >
                                <td class="px-4 py-3">{{ event.employee_name || '—' }}</td>
                                <td class="px-4 py-3">{{ event.event_type_label || '—' }}</td>
                                <td class="px-4 py-3">{{ event.starts_at || '—' }}</td>
                                <td class="px-4 py-3">{{ event.ends_at || '—' }}</td>
                                <td class="px-4 py-3">{{ event.notes || '—' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <Can permission="worktime.update">
                                            <Link
                                                :href="`/worktime/employee-events/${event.id}/edit`"
                                                class="inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-medium transition hover:bg-muted"
                                            >
                                                <Pencil class="h-4 w-4" />
                                                Editar
                                            </Link>
                                        </Can>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="events.data.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">
                                    Nenhum evento encontrado.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <Link
                    v-for="link in events.links"
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
