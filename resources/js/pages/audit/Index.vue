<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, router } from '@inertiajs/vue3'
import { ChevronDown, ChevronRight, Download, FileText, Inspect, Search } from 'lucide-vue-next'
import { computed, ref } from 'vue'

type AuditUser = {
    id: number
    name: string
    email: string
} | null

type AuditCompany = {
    id: number
    name: string
} | null

type AuditItem = {
    id: number
    event: string
    subject_type: string | null
    subject_id: string | null
    route: string | null
    method: string | null
    ip: string | null
    user_agent: string | null
    created_at: string | null
    created_at_iso: string | null
    user: AuditUser
    company: AuditCompany
    properties: Record<string, unknown> | null
}

type PaginationLink = {
    url: string | null
    label: string
    active: boolean
}

type SelectOption = {
    id: number
    name: string
}

const props = defineProps<{
    audits: {
        data: AuditItem[]
        links: PaginationLink[]
    }
    filters: {
        search: string
        event: string
        user_id: number | null
        date_from: string
        date_to: string
    }
    events: string[]
    users: SelectOption[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Auditoria',
        href: '/audit',
    },
]

const search = ref(props.filters.search ?? '')
const event = ref(props.filters.event ?? '')
const userId = ref(props.filters.user_id ? String(props.filters.user_id) : '')
const dateFrom = ref(props.filters.date_from ?? '')
const dateTo = ref(props.filters.date_to ?? '')
const expandedIds = ref<number[]>([])

function submitFilters() {
    router.get(
        '/audit',
        {
            search: search.value || undefined,
            event: event.value || undefined,
            user_id: userId.value || undefined,
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
    event.value = ''
    userId.value = ''
    dateFrom.value = ''
    dateTo.value = ''

    router.get(
        '/audit',
        {},
        {
            preserveState: true,
            replace: true,
        },
    )
}

function toggleExpanded(id: number) {
    if (expandedIds.value.includes(id)) {
        expandedIds.value = expandedIds.value.filter((item) => item !== id)
        return
    }

    expandedIds.value.push(id)
}

function isExpanded(id: number) {
    return expandedIds.value.includes(id)
}

function subjectLabel(subjectType: string | null) {
    if (!subjectType) {
        return '—'
    }

    const parts = subjectType.split('\\')
    return parts[parts.length - 1]
}

function prettyJson(value: unknown) {
    return JSON.stringify(value ?? {}, null, 2)
}

function methodBadgeClass(method: string | null) {
    switch (method) {
        case 'POST':
            return 'bg-blue-100 text-blue-700'
        case 'PUT':
        case 'PATCH':
            return 'bg-amber-100 text-amber-700'
        case 'DELETE':
            return 'bg-red-100 text-red-700'
        default:
            return 'bg-muted text-foreground'
    }
}

const exportParams = computed(() => {
    const params = new URLSearchParams()

    if (search.value) params.set('search', search.value)
    if (event.value) params.set('event', event.value)
    if (userId.value) params.set('user_id', userId.value)
    if (dateFrom.value) params.set('date_from', dateFrom.value)
    if (dateTo.value) params.set('date_to', dateTo.value)

    const query = params.toString()

    return {
        csv: query ? `/audit/export/csv?${query}` : '/audit/export/csv',
        pdf: query ? `/audit/export/pdf?${query}` : '/audit/export/pdf',
    }
})

const hasData = computed(() => props.audits.data.length > 0)
</script>

<template>
    <Head title="Auditoria" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <Heading
                    title="Auditoria"
                    description="Visualize eventos e alterações realizadas no tenant."
                    :icon="Inspect"
                />

                <Can permission="audit.viewAny">
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Button as-child variant="outline" class="w-full sm:w-auto">
                            <a :href="exportParams.csv">
                                <Download class="mr-2 h-4 w-4" />
                                Exportar CSV
                            </a>
                        </Button>

                        <Button as-child variant="outline" class="w-full sm:w-auto">
                            <a :href="exportParams.pdf">
                                <FileText class="mr-2 h-4 w-4" />
                                Exportar PDF
                            </a>
                        </Button>
                    </div>
                </Can>
            </div>

            <div class="rounded-2xl border bg-card p-4 shadow-sm sm:p-5">
                <form class="grid gap-4 xl:grid-cols-5" @submit.prevent="submitFilters">
                    <div class="xl:col-span-2">
                        <label class="mb-2 block text-sm font-medium">Busca</label>
                        <div class="relative">
                            <Search
                                class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                v-model="search"
                                type="text"
                                placeholder="Evento, rota, IP, subject..."
                                class="pl-9"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">Evento</label>
                        <select
                            v-model="event"
                            class="flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm"
                        >
                            <option value="">Todos</option>
                            <option v-for="item in events" :key="item" :value="item">
                                {{ item }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">Usuário</label>
                        <select
                            v-model="userId"
                            class="flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm"
                        >
                            <option value="">Todos</option>
                            <option v-for="user in users" :key="user.id" :value="String(user.id)">
                                {{ user.name }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">Data inicial</label>
                        <Input v-model="dateFrom" type="date" />
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">Data final</label>
                        <Input v-model="dateTo" type="date" />
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row xl:col-span-5 xl:justify-end">
                        <Button type="button" variant="outline" @click="clearFilters">
                            Limpar
                        </Button>

                        <Button type="submit">
                            Filtrar
                        </Button>
                    </div>
                </form>
            </div>

            <div class="rounded-2xl border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-[980px] w-full text-sm">
                        <thead class="bg-muted/50">
                            <tr>
                                <th class="w-12 px-4 py-3 text-left"></th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Evento</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Usuário</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Empresa</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Subject</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Método</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Data</th>
                            </tr>
                        </thead>

                        <tbody>
                            <template v-for="audit in audits.data" :key="audit.id">
                                <tr class="border-t">
                                    <td class="px-4 py-3 align-top">
                                        <button
                                            type="button"
                                            class="inline-flex items-center justify-center rounded-md border p-1 transition hover:bg-muted"
                                            @click="toggleExpanded(audit.id)"
                                        >
                                            <ChevronDown
                                                v-if="isExpanded(audit.id)"
                                                class="h-4 w-4"
                                            />
                                            <ChevronRight
                                                v-else
                                                class="h-4 w-4"
                                            />
                                        </button>
                                    </td>

                                    <td class="px-4 py-3 align-top">
                                        <div class="font-medium">{{ audit.event }}</div>
                                        <div
                                            v-if="audit.route"
                                            class="mt-1 text-xs text-muted-foreground"
                                        >
                                            {{ audit.route }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-3 align-top">
                                        <div v-if="audit.user" class="font-medium">
                                            {{ audit.user.name }}
                                        </div>
                                        <div
                                            v-if="audit.user?.email"
                                            class="text-xs text-muted-foreground"
                                        >
                                            {{ audit.user.email }}
                                        </div>
                                        <span v-if="!audit.user" class="text-muted-foreground">—</span>
                                    </td>

                                    <td class="px-4 py-3 align-top">
                                        <span>{{ audit.company?.name ?? '—' }}</span>
                                    </td>

                                    <td class="px-4 py-3 align-top">
                                        <div class="font-medium">
                                            {{ subjectLabel(audit.subject_type) }}
                                        </div>
                                        <div class="text-xs text-muted-foreground">
                                            {{ audit.subject_id ?? '—' }}
                                        </div>
                                    </td>

                                    <td class="px-4 py-3 align-top">
                                        <span
                                            class="inline-flex rounded-md px-2 py-1 text-xs whitespace-nowrap"
                                            :class="methodBadgeClass(audit.method)"
                                        >
                                            {{ audit.method ?? '—' }}
                                        </span>
                                    </td>

                                    <td class="px-4 py-3 align-top whitespace-nowrap">
                                        {{ audit.created_at ?? '—' }}
                                    </td>
                                </tr>

                                <tr v-if="isExpanded(audit.id)" class="border-t bg-muted/20">
                                    <td colspan="7" class="px-4 py-4">
                                        <div class="grid gap-4 xl:grid-cols-2">
                                            <div class="space-y-3 rounded-xl border bg-background p-4">
                                                <h3 class="text-sm font-semibold">Metadados</h3>

                                                <div class="grid gap-3 text-sm">
                                                    <div>
                                                        <div class="font-medium">IP</div>
                                                        <div class="text-muted-foreground">
                                                            {{ audit.ip ?? '—' }}
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <div class="font-medium">Rota</div>
                                                        <div class="text-muted-foreground break-all">
                                                            {{ audit.route ?? '—' }}
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <div class="font-medium">User Agent</div>
                                                        <div class="text-muted-foreground break-all">
                                                            {{ audit.user_agent ?? '—' }}
                                                        </div>
                                                    </div>

                                                    <div>
                                                        <div class="font-medium">Tipo do subject</div>
                                                        <div class="text-muted-foreground break-all">
                                                            {{ audit.subject_type ?? '—' }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="space-y-3 rounded-xl border bg-background p-4">
                                                <h3 class="text-sm font-semibold">Properties</h3>

                                                <pre class="max-h-[360px] overflow-auto rounded-lg bg-muted p-3 text-xs leading-5">{{ prettyJson(audit.properties) }}</pre>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>

                            <tr v-if="!hasData">
                                <td colspan="7" class="px-4 py-10 text-center text-muted-foreground">
                                    Nenhum registro de auditoria encontrado.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <Link
                    v-for="link in audits.links"
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
