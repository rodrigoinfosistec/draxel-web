<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, router } from '@inertiajs/vue3'
import { Download, FileText, Headset, Search } from 'lucide-vue-next'
import { computed, ref } from 'vue'

type UserItem = {
    id: number
    name: string
    email: string
} | null

type TicketItem = {
    id: number
    code: string
    subject: string
    status: string
    status_label: string
    is_open: boolean
    created_at: string | null
    last_interaction_at: string | null
    creator: UserItem
    assignee: UserItem
}

type PaginationLink = {
    url: string | null
    label: string
    active: boolean
}

type StatusOption = {
    value: string
    label: string
}

const props = defineProps<{
    tickets: {
        data: TicketItem[]
        links: PaginationLink[]
    }
    filters: {
        search: string
        status: string
        date_from: string
        date_to: string
    }
    statuses: StatusOption[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Chamados',
        href: '/support-tickets',
    },
]

const search = ref(props.filters.search ?? '')
const status = ref(props.filters.status ?? '')
const dateFrom = ref(props.filters.date_from ?? '')
const dateTo = ref(props.filters.date_to ?? '')

function submitFilters() {
    router.get(
        '/support-tickets',
        {
            search: search.value || undefined,
            status: status.value || undefined,
            date_from: dateFrom.value || undefined,
            date_to: dateTo.value || undefined,
        },
        {
            preserveState: true,
            replace: true,
        },
    )
}

function clearFilters() {
    search.value = ''
    status.value = ''
    dateFrom.value = ''
    dateTo.value = ''

    router.get(
        '/support-tickets',
        {},
        {
            preserveState: true,
            replace: true,
        },
    )
}

function statusBadgeClass(statusValue: string) {
    switch (statusValue) {
        case 'open':
            return 'bg-blue-100 text-blue-700'
        case 'in_progress':
            return 'bg-amber-100 text-amber-700'
        case 'waiting_customer':
            return 'bg-orange-100 text-orange-700'
        case 'waiting_support':
            return 'bg-violet-100 text-violet-700'
        case 'resolved':
            return 'bg-emerald-100 text-emerald-700'
        case 'closed':
            return 'bg-zinc-200 text-zinc-700'
        default:
            return 'bg-muted text-foreground'
    }
}

const exportParams = computed(() => {
    const params = new URLSearchParams()

    if (search.value) params.set('search', search.value)
    if (status.value) params.set('status', status.value)
    if (dateFrom.value) params.set('date_from', dateFrom.value)
    if (dateTo.value) params.set('date_to', dateTo.value)

    const query = params.toString()

    return {
        csv: query ? `/support-tickets/export/csv?${query}` : '/support-tickets/export/csv',
        pdf: query ? `/support-tickets/export/pdf?${query}` : '/support-tickets/export/pdf',
    }
})
</script>

<template>
    <Head title="Chamados" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <Heading
                    title="Chamados"
                    description="Gerencie os chamados de suporte."
                    :icon="Headset"
                />

                <div class="flex flex-col gap-3 sm:flex-row">
                    <Can permission="support.viewAny">
                        <Button as-child variant="outline" class="w-full sm:w-auto">
                            <a :href="exportParams.csv">
                                <Download class="mr-2 h-4 w-4" />
                                Exportar CSV
                            </a>
                        </Button>
                    </Can>

                    <Can permission="support.viewAny">
                        <Button as-child variant="outline" class="w-full sm:w-auto">
                            <a :href="exportParams.pdf">
                                <FileText class="mr-2 h-4 w-4" />
                                Exportar PDF
                            </a>
                        </Button>
                    </Can>

                    <Can permission="support.create">
                        <Link href="/support-tickets/create" class="w-full sm:w-auto">
                            <Button class="w-full sm:w-auto">Novo chamado</Button>
                        </Link>
                    </Can>
                </div>
            </div>

            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <form class="grid gap-3 xl:grid-cols-5" @submit.prevent="submitFilters">
                    <Input
                        v-model="search"
                        type="text"
                        placeholder="Buscar por código ou assunto"
                        class="w-full xl:col-span-2"
                    />

                    <select
                        v-model="status"
                        class="flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm"
                    >
                        <option value="">Todos os status</option>
                        <option
                            v-for="item in statuses"
                            :key="item.value"
                            :value="item.value"
                        >
                            {{ item.label }}
                        </option>
                    </select>

                    <Input v-model="dateFrom" type="date" />
                    <Input v-model="dateTo" type="date" />

                    <div class="flex flex-col gap-3 sm:flex-row xl:col-span-5 xl:justify-end">
                        <Button type="button" variant="outline" @click="clearFilters">
                            Limpar
                        </Button>

                        <Button type="submit">
                            <Search class="mr-2 h-4 w-4" />
                            Filtrar
                        </Button>
                    </div>
                </form>
            </div>

            <div class="rounded-xl border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-[760px] w-full text-sm">
                        <thead class="bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Código</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Assunto</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Solicitante</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Status</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Última interação</th>
                                <th class="px-4 py-3 text-right whitespace-nowrap">Ações</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="ticket in tickets.data"
                                :key="ticket.id"
                                class="border-t"
                            >
                                <td class="px-4 py-3 align-top">
                                    <div class="font-medium">{{ ticket.code }}</div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ ticket.created_at ?? '—' }}
                                    </div>
                                </td>

                                <td class="px-4 py-3 align-top">
                                    <div class="font-medium">{{ ticket.subject }}</div>
                                </td>

                                <td class="px-4 py-3 align-top">
                                    <div class="font-medium">{{ ticket.creator?.name ?? '—' }}</div>
                                    <div class="text-xs text-muted-foreground break-all">
                                        {{ ticket.creator?.email ?? '—' }}
                                    </div>
                                </td>

                                <td class="px-4 py-3 align-top">
                                    <span
                                        class="inline-flex rounded-md px-2 py-1 text-xs whitespace-nowrap"
                                        :class="statusBadgeClass(ticket.status)"
                                    >
                                        {{ ticket.status_label }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 align-top whitespace-nowrap">
                                    {{ ticket.last_interaction_at ?? '—' }}
                                </td>

                                <td class="px-4 py-3 text-right align-top">
                                    <Can permission="support.view">
                                        <Link
                                            :href="`/support-tickets/${ticket.id}`"
                                            class="inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-medium text-foreground transition hover:bg-muted"
                                        >
                                            <span>Abrir</span>
                                        </Link>
                                    </Can>
                                </td>
                            </tr>

                            <tr v-if="tickets.data.length === 0">
                                <td
                                    colspan="6"
                                    class="px-4 py-8 text-center text-muted-foreground"
                                >
                                    Nenhum chamado encontrado.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <Link
                    v-for="link in tickets.links"
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
