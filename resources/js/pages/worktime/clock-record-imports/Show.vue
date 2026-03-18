<script setup lang="ts">
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft, FileClock } from 'lucide-vue-next'

type ImportData = {
    id: number
    original_filename: string
    status: string
    status_label: string
    device_name: string | null
    total_items: number
    valid_items: number
    invalid_items: number
    can_launch: boolean
}

type GroupItem = {
    employee_name: string
    date_label: string
    items: {
        id: number
        line_number: number
        employee_code: string | null
        employee_name: string | null
        recorded_at: string | null
        time: string | null
        status: string
        status_label: string
        divergence_reason: string | null
        raw_line: string
    }[]
}

const props = defineProps<{
    importData: ImportData
    groups: GroupItem[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Ponto', href: '/worktime' },
    { title: 'Importações', href: '/worktime/clock-record-imports' },
    { title: `Importação #${props.importData.id}`, href: `/worktime/clock-record-imports/${props.importData.id}` },
]

const form = useForm({})
</script>

<template>
    <Head :title="`Importação #${props.importData.id}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <div class="relative overflow-hidden rounded-2xl border bg-card/40 p-4 shadow-sm backdrop-blur-[1px] sm:p-5">
                <div
                    class="absolute inset-0"
                    style="background: linear-gradient(to bottom right, var(--company-color-soft), transparent, transparent);"
                />

                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        :title="`Importação #${props.importData.id}`"
                        description="Revise as divergências antes de lançar os registros."
                        :icon="FileClock"
                    />

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Link
                            href="/worktime/clock-record-imports"
                            class="inline-flex items-center justify-center rounded-lg border px-4 py-2 text-sm font-medium transition hover:bg-muted"
                        >
                            <ArrowLeft class="mr-2 h-4 w-4" />
                            Voltar
                        </Link>

                        <Button
                            v-if="props.importData.can_launch"
                            :disabled="form.processing"
                            @click="form.post(`/worktime/clock-record-imports/${props.importData.id}/launch`)"
                        >
                            {{ form.processing ? 'Lançando...' : 'Lançar registros' }}
                        </Button>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-4">
                <div class="rounded-xl border bg-card/50 p-4 shadow-sm">
                    <div class="text-sm text-muted-foreground">Arquivo</div>
                    <div class="mt-1 font-medium">{{ props.importData.original_filename }}</div>
                </div>

                <div class="rounded-xl border bg-card/50 p-4 shadow-sm">
                    <div class="text-sm text-muted-foreground">Device</div>
                    <div class="mt-1 font-medium">{{ props.importData.device_name || '—' }}</div>
                </div>

                <div class="rounded-xl border bg-card/50 p-4 shadow-sm">
                    <div class="text-sm text-muted-foreground">Status</div>
                    <div class="mt-1 font-medium">{{ props.importData.status_label }}</div>
                </div>

                <div class="rounded-xl border bg-card/50 p-4 shadow-sm">
                    <div class="text-sm text-muted-foreground">Resumo</div>
                    <div class="mt-1 font-medium">
                        {{ props.importData.valid_items }}/{{ props.importData.total_items }} válidos
                        <span v-if="props.importData.invalid_items > 0"> • {{ props.importData.invalid_items }} divergentes</span>
                    </div>
                </div>
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
                        <table class="min-w-[860px] w-full text-sm">
                            <thead class="bg-muted/50">
                                <tr>
                                    <th class="px-4 py-3 text-left">Linha</th>
                                    <th class="px-4 py-3 text-left">Matrícula</th>
                                    <th class="px-4 py-3 text-left">Hora</th>
                                    <th class="px-4 py-3 text-left">Status</th>
                                    <th class="px-4 py-3 text-left">Divergência</th>
                                    <th class="px-4 py-3 text-left">Linha bruta</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr v-for="item in group.items" :key="item.id" class="border-t">
                                    <td class="px-4 py-3">{{ item.line_number }}</td>
                                    <td class="px-4 py-3">{{ item.employee_code || '—' }}</td>
                                    <td class="px-4 py-3">{{ item.time || '—' }}</td>
                                    <td class="px-4 py-3">{{ item.status_label }}</td>
                                    <td class="px-4 py-3">{{ item.divergence_reason || '—' }}</td>
                                    <td class="px-4 py-3 font-mono text-xs">{{ item.raw_line }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div
                    v-if="groups.length === 0"
                    class="rounded-xl border bg-card/50 px-4 py-8 text-center text-muted-foreground shadow-sm"
                >
                    Nenhum item encontrado.
                </div>
            </div>
        </div>
    </AppLayout>
</template>
