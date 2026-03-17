<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, router } from '@inertiajs/vue3'
import { CalendarClock, Download, FileText, Pencil } from 'lucide-vue-next'
import { computed, ref } from 'vue'

type EventItem = {
    id: number
    employee_name: string
    event_type: string
    event_type_label: string
    time_mode: string
    time_mode_label: string
    starts_at: string
    ends_at: string
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
        { search: search.value || undefined },
        {
            preserveState: true,
            replace: true,
        },
    )
}

const exportParams = computed(() => {
    const params = new URLSearchParams()

    if (search.value) params.set('search', search.value)

    const query = params.toString()

    return {
        csv: query ? `/worktime/employee-events/export/csv?${query}` : '/worktime/employee-events/export/csv',
        pdf: query ? `/worktime/employee-events/export/pdf?${query}` : '/worktime/employee-events/export/pdf',
    }
})
</script>

<template>
    <Head title="Eventos de funcionários" />

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
                        title="Eventos de funcionários"
                        description="Gerencie férias, folgas, suspensões e eventos parciais."
                        :icon="CalendarClock"
                    />

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Can permission="worktime.viewAny">
                            <Button as-child variant="outline" class="w-full sm:w-auto">
                                <a :href="exportParams.csv">
                                    <Download class="mr-2 h-4 w-4" />
                                    Exportar CSV
                                </a>
                            </Button>
                        </Can>

                        <Can permission="worktime.viewAny">
                            <Button as-child variant="outline" class="w-full sm:w-auto">
                                <a :href="exportParams.pdf">
                                    <FileText class="mr-2 h-4 w-4" />
                                    Exportar PDF
                                </a>
                            </Button>
                        </Can>

                        <Can permission="worktime.create">
                            <Link href="/worktime/employee-events/create" class="w-full sm:w-auto">
                                <Button class="w-full sm:w-auto">Novo evento</Button>
                            </Link>
                        </Can>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border bg-card/50 p-4 shadow-sm">
                <form
                    class="flex flex-col gap-3 sm:flex-row"
                    @submit.prevent="submitSearch"
                >
                    <Input
                        v-model="search"
                        type="text"
                        placeholder="Buscar por funcionário"
                        class="w-full"
                    />

                    <Button type="submit" variant="outline" class="w-full sm:w-auto">
                        Buscar
                    </Button>
                </form>
            </div>

            <div class="rounded-xl border bg-card/50 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-[720px] w-full text-sm">
                        <thead class="bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Funcionário</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Tipo</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Modo</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Início</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Fim</th>
                                <th class="px-4 py-3 text-right whitespace-nowrap">Ações</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="event in events.data"
                                :key="event.id"
                                class="border-t"
                            >
                                <td class="px-4 py-3 align-top">
                                    <div class="font-medium">{{ event.employee_name }}</div>
                                </td>

                                <td class="px-4 py-3 align-top">
                                    {{ event.event_type_label }}
                                </td>

                                <td class="px-4 py-3 align-top">
                                    {{ event.time_mode_label }}
                                </td>

                                <td class="px-4 py-3 align-top">
                                    {{ event.starts_at }}
                                </td>

                                <td class="px-4 py-3 align-top">
                                    {{ event.ends_at }}
                                </td>

                                <td class="px-4 py-3 text-right align-top">
                                    <Can permission="worktime.update">
                                        <Link
                                            :href="`/worktime/employee-events/${event.id}/edit`"
                                            class="inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-medium text-foreground transition hover:bg-muted"
                                        >
                                            <Pencil class="h-4 w-4" />
                                            <span class="hidden sm:inline">Editar</span>
                                        </Link>
                                    </Can>
                                </td>
                            </tr>

                            <tr v-if="events.data.length === 0">
                                <td
                                    colspan="6"
                                    class="px-4 py-8 text-center text-muted-foreground"
                                >
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
