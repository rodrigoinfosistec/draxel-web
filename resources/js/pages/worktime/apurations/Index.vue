<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { Calculator, Download, FileText } from 'lucide-vue-next'
import { computed, ref } from 'vue'

type EmployeeOption = {
    id: number
    name: string
}

type DayItem = {
    date: string
    date_label: string
    expected_minutes: number
    worked_minutes: number
    delay_minutes: number
    early_exit_minutes: number
    overtime_minutes: number
    absence_minutes: number
    records_count: number
    status: string
    notes: string[]
}

type EmployeeItem = {
    id: number
    name: string
    summary: {
        expected_minutes: number
        worked_minutes: number
        delay_minutes: number
        early_exit_minutes: number
        overtime_minutes: number
        absence_minutes: number
        inconsistent_days: number
        worked_days: number
    }
    days: DayItem[]
}

const props = defineProps<{
    filters: {
        start_date: string
        end_date: string
        employee_ids: number[]
    }
    employees: EmployeeOption[]
    apuration: {
        employees: EmployeeItem[]
        totals: {
            employees_count: number
            days_count: number
            expected_minutes: number
            worked_minutes: number
            delay_minutes: number
            early_exit_minutes: number
            overtime_minutes: number
            absence_minutes: number
            inconsistent_days: number
        }
    }
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Ponto', href: '/worktime' },
    { title: 'Apuração', href: '/worktime/apurations' },
]

const startDate = ref(props.filters.start_date)
const endDate = ref(props.filters.end_date)
const employeeIds = ref<number[]>(props.filters.employee_ids ?? [])

function submitSearch() {
    router.get(
        '/worktime/apurations',
        {
            start_date: startDate.value,
            end_date: endDate.value,
            employee_ids: employeeIds.value.length ? employeeIds.value : undefined,
        },
        {
            preserveState: true,
            replace: true,
        },
    )
}

const exportParams = computed(() => {
    const params = new URLSearchParams()

    params.set('start_date', startDate.value)
    params.set('end_date', endDate.value)

    for (const employeeId of employeeIds.value) {
        params.append('employee_ids[]', String(employeeId))
    }

    const query = params.toString()

    return {
        csv: `/worktime/apurations/export/csv?${query}`,
        pdf: `/worktime/apurations/export/pdf?${query}`,
    }
})

function toggleEmployee(employeeId: number) {
    if (employeeIds.value.includes(employeeId)) {
        employeeIds.value = employeeIds.value.filter((id) => id !== employeeId)
        return
    }

    employeeIds.value.push(employeeId)
}
</script>

<template>
    <Head title="Apuração de ponto" />

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
                        title="Apuração de ponto"
                        description="Apure o período por funcionário e dia antes do fechamento."
                        :icon="Calculator"
                    />

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Can permission="worktime.exportApuration">
                            <Button as-child variant="outline" class="w-full sm:w-auto">
                                <a :href="exportParams.csv">
                                    <Download class="mr-2 h-4 w-4" />
                                    Exportar CSV
                                </a>
                            </Button>
                        </Can>

                        <Can permission="worktime.exportApuration">
                            <Button as-child variant="outline" class="w-full sm:w-auto">
                                <a :href="exportParams.pdf">
                                    <FileText class="mr-2 h-4 w-4" />
                                    Exportar PDF
                                </a>
                            </Button>
                        </Can>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border bg-card/50 p-4 shadow-sm">
                <form class="space-y-4" @submit.prevent="submitSearch">
                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="grid gap-2">
                            <label class="text-sm font-medium">Data inicial</label>
                            <input
                                v-model="startDate"
                                type="date"
                                class="flex h-10 w-full rounded-md border bg-card px-3 py-2 text-sm"
                            >
                        </div>

                        <div class="grid gap-2">
                            <label class="text-sm font-medium">Data final</label>
                            <input
                                v-model="endDate"
                                type="date"
                                class="flex h-10 w-full rounded-md border bg-card px-3 py-2 text-sm"
                            >
                        </div>

                        <div class="flex items-end">
                            <Button type="submit" variant="outline" class="w-full">
                                Apurar período
                            </Button>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <div class="text-sm font-medium">Funcionários</div>

                        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                            <label
                                v-for="employee in employees"
                                :key="employee.id"
                                class="flex items-center gap-3 rounded-lg border bg-background px-4 py-3 text-sm transition hover:bg-muted/40"
                            >
                                <input
                                    :checked="employeeIds.includes(employee.id)"
                                    type="checkbox"
                                    class="h-4 w-4 rounded border-border"
                                    @change="toggleEmployee(employee.id)"
                                >
                                <span>{{ employee.name }}</span>
                            </label>
                        </div>
                    </div>
                </form>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-xl border bg-card/50 p-4 shadow-sm">
                    <div class="text-sm text-muted-foreground">Funcionários</div>
                    <div class="mt-1 text-2xl font-semibold">{{ apuration.totals.employees_count }}</div>
                </div>

                <div class="rounded-xl border bg-card/50 p-4 shadow-sm">
                    <div class="text-sm text-muted-foreground">Dias apurados</div>
                    <div class="mt-1 text-2xl font-semibold">{{ apuration.totals.days_count }}</div>
                </div>

                <div class="rounded-xl border bg-card/50 p-4 shadow-sm">
                    <div class="text-sm text-muted-foreground">Minutos trabalhados</div>
                    <div class="mt-1 text-2xl font-semibold">{{ apuration.totals.worked_minutes }}</div>
                </div>

                <div class="rounded-xl border bg-card/50 p-4 shadow-sm">
                    <div class="text-sm text-muted-foreground">Dias inconsistentes</div>
                    <div class="mt-1 text-2xl font-semibold">{{ apuration.totals.inconsistent_days }}</div>
                </div>
            </div>

            <div class="space-y-6">
                <div
                    v-for="employee in apuration.employees"
                    :key="employee.id"
                    class="rounded-xl border bg-card/50 shadow-sm"
                >
                    <div class="border-b px-4 py-4">
                        <div class="text-base font-semibold">{{ employee.name }}</div>
                        <div class="mt-2 grid gap-3 md:grid-cols-4 xl:grid-cols-8 text-sm">
                            <div><span class="text-muted-foreground">Previsto:</span> {{ employee.summary.expected_minutes }}</div>
                            <div><span class="text-muted-foreground">Trabalhado:</span> {{ employee.summary.worked_minutes }}</div>
                            <div><span class="text-muted-foreground">Atraso:</span> {{ employee.summary.delay_minutes }}</div>
                            <div><span class="text-muted-foreground">Saída antecipada:</span> {{ employee.summary.early_exit_minutes }}</div>
                            <div><span class="text-muted-foreground">Extra:</span> {{ employee.summary.overtime_minutes }}</div>
                            <div><span class="text-muted-foreground">Ausência:</span> {{ employee.summary.absence_minutes }}</div>
                            <div><span class="text-muted-foreground">Dias trabalhados:</span> {{ employee.summary.worked_days }}</div>
                            <div><span class="text-muted-foreground">Inconsistentes:</span> {{ employee.summary.inconsistent_days }}</div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-[1100px] w-full text-sm">
                            <thead class="bg-muted/50">
                                <tr>
                                    <th class="px-4 py-3 text-left">Data</th>
                                    <th class="px-4 py-3 text-left">Previsto</th>
                                    <th class="px-4 py-3 text-left">Trabalhado</th>
                                    <th class="px-4 py-3 text-left">Atraso</th>
                                    <th class="px-4 py-3 text-left">Saída antecipada</th>
                                    <th class="px-4 py-3 text-left">Extra</th>
                                    <th class="px-4 py-3 text-left">Ausência</th>
                                    <th class="px-4 py-3 text-left">Registros</th>
                                    <th class="px-4 py-3 text-left">Status</th>
                                    <th class="px-4 py-3 text-left">Observações</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr
                                    v-for="day in employee.days"
                                    :key="`${employee.id}-${day.date}`"
                                    class="border-t"
                                >
                                    <td class="px-4 py-3">{{ day.date_label }}</td>
                                    <td class="px-4 py-3">{{ day.expected_minutes }}</td>
                                    <td class="px-4 py-3">{{ day.worked_minutes }}</td>
                                    <td class="px-4 py-3">{{ day.delay_minutes }}</td>
                                    <td class="px-4 py-3">{{ day.early_exit_minutes }}</td>
                                    <td class="px-4 py-3">{{ day.overtime_minutes }}</td>
                                    <td class="px-4 py-3">{{ day.absence_minutes }}</td>
                                    <td class="px-4 py-3">{{ day.records_count }}</td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex rounded-md px-2 py-1 text-xs"
                                            :class="
                                                day.status === 'inconsistent'
                                                    ? 'bg-red-100 text-red-700'
                                                    : day.status === 'absence'
                                                        ? 'bg-amber-100 text-amber-700'
                                                        : day.status === 'neutral'
                                                            ? 'bg-slate-100 text-slate-700'
                                                            : 'bg-green-100 text-green-700'
                                            "
                                        >
                                            {{ day.status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div v-if="day.notes.length" class="space-y-1">
                                            <div
                                                v-for="note in day.notes"
                                                :key="note"
                                                class="text-xs text-muted-foreground"
                                            >
                                                {{ note }}
                                            </div>
                                        </div>
                                        <span v-else>—</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div
                    v-if="apuration.employees.length === 0"
                    class="rounded-xl border bg-card/50 px-4 py-8 text-center text-muted-foreground shadow-sm"
                >
                    Nenhum funcionário encontrado para o período informado.
                </div>
            </div>
        </div>
    </AppLayout>
</template>
