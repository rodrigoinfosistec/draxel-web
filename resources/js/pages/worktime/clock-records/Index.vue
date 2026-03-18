<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, router } from '@inertiajs/vue3'
import { Clock3, Download, FileText, Pencil } from 'lucide-vue-next'
import { computed, ref } from 'vue'

type ClockRecordItem = {
    id: number
    employee_id: number
    employee_name: string
    source_type: string
    source_type_label: string
    recorded_at: string
    date: string
    date_label: string
    time: string
    notes: string | null
}

type PaginationLink = {
    url: string | null
    label: string
    active: boolean
}

const props = defineProps<{
    records: {
        data: ClockRecordItem[]
        links: PaginationLink[]
    }
    filters: {
        search: string
        date: string
    }
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Ponto', href: '/worktime' },
    { title: 'Registros de ponto', href: '/worktime/clock-records' },
]

const search = ref(props.filters.search ?? '')
const date = ref(props.filters.date ?? '')

function submitSearch() {
    router.get(
        '/worktime/clock-records',
        {
            search: search.value || undefined,
            date: date.value || undefined,
        },
        {
            preserveState: true,
            replace: true,
        },
    )
}

const exportParams = computed(() => {
    const params = new URLSearchParams()

    if (search.value) params.set('search', search.value)
    if (date.value) params.set('date', date.value)

    const query = params.toString()

    return {
        csv: query ? `/worktime/clock-records/export/csv?${query}` : '/worktime/clock-records/export/csv',
        pdf: query ? `/worktime/clock-records/export/pdf?${query}` : '/worktime/clock-records/export/pdf',
    }
})

const groups = computed(() => {
    const map = new Map<string, { employee_name: string; date_label: string; items: ClockRecordItem[] }>()

    for (const record of props.records.data) {
        const key = `${record.employee_id}-${record.date}`

        if (!map.has(key)) {
            map.set(key, {
                employee_name: record.employee_name,
                date_label: record.date_label,
                items: [],
            })
        }

        map.get(key)?.items.push(record)
    }

    return Array.from(map.values())
})
</script>

<template>
    <Head title="Registros de ponto" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <div class="relative overflow-hidden rounded-2xl border bg-card/40 p-4 shadow-sm backdrop-blur-[1px] sm:p-5">
                <div
                    class="absolute inset-0"
                    style="background: linear-gradient(to bottom right, var(--company-color-soft), transparent, transparent);"
                />

                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Registros de ponto"
                        description="Gerencie os registros de ponto manuais."
                        :icon="Clock3"
                    />

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Can permission="worktime.viewAnyClockRecord">
                            <Button as-child variant="outline" class="w-full sm:w-auto">
                                <a :href="exportParams.csv">
                                    <Download class="mr-2 h-4 w-4" />
                                    Exportar CSV
                                </a>
                            </Button>
                        </Can>

                        <Can permission="worktime.viewAnyClockRecord">
                            <Button as-child variant="outline" class="w-full sm:w-auto">
                                <a :href="exportParams.pdf">
                                    <FileText class="mr-2 h-4 w-4" />
                                    Exportar PDF
                                </a>
                            </Button>
                        </Can>

                        <Can permission="worktime.createClockRecord">
                            <Link href="/worktime/clock-records/create" class="w-full sm:w-auto">
                                <Button class="w-full sm:w-auto">Novo registro manual</Button>
                            </Link>
                        </Can>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border bg-card/50 p-4 shadow-sm">
                <form class="grid gap-3 md:grid-cols-[1fr_180px_auto]" @submit.prevent="submitSearch">
                    <Input
                        v-model="search"
                        type="text"
                        placeholder="Buscar por funcionário"
                        class="w-full"
                    />

                    <Input
                        v-model="date"
                        type="date"
                        class="w-full"
                    />

                    <Button type="submit" variant="outline">
                        Buscar
                    </Button>
                </form>
            </div>

            <div class="space-y-4">
                <div
                    v-for="group in groups"
                    :key="`${group.employee_name}-${group.date_label}`"
                    class="rounded-xl border bg-card/50 shadow-sm"
                >
                    <div class="border-b px-4 py-3">
                        <div class="font-semibold">{{ group.employee_name }}</div>
                        <div class="text-sm text-muted-foreground">{{ group.date_label }}</div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-[640px] w-full text-sm">
                            <thead class="bg-muted/50">
                                <tr>
                                    <th class="px-4 py-3 text-left">Hora</th>
                                    <th class="px-4 py-3 text-left">Origem</th>
                                    <th class="px-4 py-3 text-left">Observações</th>
                                    <th class="px-4 py-3 text-right">Ações</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr v-for="record in group.items" :key="record.id" class="border-t">
                                    <td class="px-4 py-3">{{ record.time }}</td>
                                    <td class="px-4 py-3">{{ record.source_type_label }}</td>
                                    <td class="px-4 py-3">{{ record.notes || '—' }}</td>
                                    <td class="px-4 py-3 text-right">
                                        <Can permission="worktime.updateClockRecord">
                                            <Link
                                                :href="`/worktime/clock-records/${record.id}/edit`"
                                                class="inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-medium transition hover:bg-muted"
                                            >
                                                <Pencil class="h-4 w-4" />
                                                <span class="hidden sm:inline">Editar</span>
                                            </Link>
                                        </Can>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div
                    v-if="records.data.length === 0"
                    class="rounded-xl border bg-card/50 px-4 py-8 text-center text-muted-foreground shadow-sm"
                >
                    Nenhum registro encontrado.
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <Link
                    v-for="link in records.links"
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
