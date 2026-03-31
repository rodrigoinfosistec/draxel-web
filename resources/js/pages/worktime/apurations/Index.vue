<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { Calculator, ChevronDown, ChevronUp, Download, FileText, Filter, RefreshCw } from 'lucide-vue-next'
import { computed, ref } from 'vue'

type EmployeeOption = {
    id: number
    name: string
}

type DayItem = {
    date: string
    date_label: string
    weekday_label: string
    expected_minutes: number
    expected_hours: string
    worked_minutes: number
    worked_hours: string
    delay_minutes: number
    delay_hours: string
    dispensation_minutes: number
    dispensation_hours: string
    overtime_minutes: number
    overtime_hours: string
    dsr_worked_minutes: number
    dsr_worked_hours: string
    records_count: number
    record_times: string[]
    status: string
    status_label: string
    notes: string[]
}

type EmployeeItem = {
    id: number
    name: string
    summary: {
        expected_minutes: number
        expected_hours: string
        worked_minutes: number
        worked_hours: string
        delay_minutes: number
        delay_hours: string
        dispensation_minutes: number
        dispensation_hours: string
        overtime_minutes: number
        overtime_hours: string
        dsr_worked_minutes: number
        dsr_worked_hours: string
        inconsistent_days: number
        worked_days: number
        warning_days: number
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
            expected_hours: string
            worked_minutes: number
            worked_hours: string
            delay_minutes: number
            delay_hours: string
            dispensation_minutes: number
            dispensation_hours: string
            overtime_minutes: number
            overtime_hours: string
            dsr_worked_minutes: number
            dsr_worked_hours: string
            inconsistent_days: number
            warning_days: number
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
const collapsedEmployees = ref<number[]>([])

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

function clearFilters() {
    employeeIds.value = []
    startDate.value = props.filters.start_date
    endDate.value = props.filters.end_date
    submitSearch()
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

function statusClass(status: string) {
    if (status === 'inconsistent') {
        return 'bg-red-100 text-red-700 ring-1 ring-inset ring-red-200'
    }

    if (status === 'absence') {
        return 'bg-amber-100 text-amber-700 ring-1 ring-inset ring-amber-200'
    }

    if (status === 'warning') {
        return 'bg-blue-100 text-blue-700 ring-1 ring-inset ring-blue-200'
    }

    if (status === 'neutral') {
        return 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-200'
    }

    if (status === 'rest_day_worked') {
        return 'bg-violet-100 text-violet-700 ring-1 ring-inset ring-violet-200'
    }

    return 'bg-green-100 text-green-700 ring-1 ring-inset ring-green-200'
}

function isCollapsed(employeeId: number) {
    return collapsedEmployees.value.includes(employeeId)
}

function toggleCollapse(employeeId: number) {
    if (isCollapsed(employeeId)) {
        collapsedEmployees.value = collapsedEmployees.value.filter((id) => id !== employeeId)
        return
    }

    collapsedEmployees.value.push(employeeId)
}

function expandAll() {
    collapsedEmployees.value = []
}

function collapseAll() {
    collapsedEmployees.value = props.apuration.employees.map((employee) => employee.id)
}
</script>

<template>
    <Head title="Apuração de ponto" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <div class="relative overflow-hidden rounded-2xl border bg-card/40 p-4 shadow-sm backdrop-blur-[1px] sm:p-5">
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

                        <div class="grid gap-2">
                            <label class="text-sm font-medium">Funcionários</label>
                            <select
                                v-model="employeeIds"
                                multiple
                                class="min-h-[120px] w-full rounded-md border bg-card px-3 py-2 text-sm"
                            >
                                <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                                    {{ employee.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:justify-end">
                        <Button type="button" variant="outline" @click="clearFilters">
                            <RefreshCw class="mr-2 h-4 w-4" />
                            Limpar
                        </Button>

                        <Button type="submit">
                            <Filter class="mr-2 h-4 w-4" />
                            Apurar
                        </Button>
                    </div>
                </form>
            </div>

            <div class="grid gap-4 md:grid-cols-3 xl:grid-cols-6">
                <div class="rounded-xl border bg-card/50 p-4 shadow-sm">
                    <div class="text-xs font-medium uppercase text-muted-foreground">Previsto</div>
                    <div class="mt-2 text-2xl font-semibold">{{ apuration.totals.expected_hours }}</div>
                </div>
                <div class="rounded-xl border bg-card/50 p-4 shadow-sm">
                    <div class="text-xs font-medium uppercase text-muted-foreground">Trabalhado</div>
                    <div class="mt-2 text-2xl font-semibold">{{ apuration.totals.worked_hours }}</div>
                </div>
                <div class="rounded-xl border bg-card/50 p-4 shadow-sm">
                    <div class="text-xs font-medium uppercase text-muted-foreground">Atrasos</div>
                    <div class="mt-2 text-2xl font-semibold">{{ apuration.totals.delay_hours }}</div>
                </div>
                <div class="rounded-xl border bg-card/50 p-4 shadow-sm">
                    <div class="text-xs font-medium uppercase text-muted-foreground">Dispensas</div>
                    <div class="mt-2 text-2xl font-semibold">{{ apuration.totals.dispensation_hours }}</div>
                </div>
                <div class="rounded-xl border bg-card/50 p-4 shadow-sm">
                    <div class="text-xs font-medium uppercase text-muted-foreground">Extras</div>
                    <div class="mt-2 text-2xl font-semibold">{{ apuration.totals.overtime_hours }}</div>
                </div>
                <div class="rounded-xl border bg-card/50 p-4 shadow-sm">
                    <div class="text-xs font-medium uppercase text-muted-foreground">DSR/Feriado</div>
                    <div class="mt-2 text-2xl font-semibold">{{ apuration.totals.dsr_worked_hours }}</div>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <Button type="button" variant="outline" @click="expandAll">Expandir tudo</Button>
                <Button type="button" variant="outline" @click="collapseAll">Recolher tudo</Button>
            </div>

            <div class="space-y-4">
                <div
                    v-for="employee in apuration.employees"
                    :key="employee.id"
                    class="overflow-hidden rounded-2xl border bg-card/50 shadow-sm"
                >
                    <button
                        type="button"
                        class="flex w-full items-center justify-between gap-4 border-b px-4 py-4 text-left"
                        @click="toggleCollapse(employee.id)"
                    >
                        <div>
                            <div class="text-base font-semibold">{{ employee.name }}</div>
                            <div class="mt-1 flex flex-wrap gap-4 text-sm text-muted-foreground">
                                <span>Previsto: {{ employee.summary.expected_hours }}</span>
                                <span>Trabalhado: {{ employee.summary.worked_hours }}</span>
                                <span>Atrasos: {{ employee.summary.delay_hours }}</span>
                                <span>Dispensa: {{ employee.summary.dispensation_hours }}</span>
                                <span>Extra: {{ employee.summary.overtime_hours }}</span>
                                <span>DSR/Feriado: {{ employee.summary.dsr_worked_hours }}</span>
                            </div>
                        </div>

                        <component :is="isCollapsed(employee.id) ? ChevronDown : ChevronUp" class="h-5 w-5" />
                    </button>

                    <div v-if="!isCollapsed(employee.id)" class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-muted/40 text-left">
                                <tr>
                                    <th class="px-2 py-3 font-medium">Data</th>
                                    <th class="px-2 py-3 font-medium">Previsto</th>
                                    <th class="px-2 py-3 font-medium">Trabalhado</th>
                                    <th class="px-2 py-3 font-medium">Atrasos</th>
                                    <th class="px-2 py-3 font-medium">Dispensa</th>
                                    <th class="px-2 py-3 font-medium">Extra</th>
                                    <th class="px-2 py-3 font-medium">DSR/Feriado</th>
                                    <th class="px-2 py-3 font-medium w-40">
                                        Registros
                                    </th>
                                    <th class="px-2 py-3 font-medium">Status</th>
                                    <th class="px-2 py-3 font-medium">Observações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="day in employee.days" :key="`${employee.id}-${day.date}`" class="border-t align-top text-xs">
                                    <td class="px-2 py-3">
                                        <div>{{ day.date_label }}</div>
                                        <div class="text-muted-foreground">{{ day.weekday_label }}</div>
                                    </td>
                                    <td class="px-2 py-3">{{ day.expected_hours }}</td>
                                    <td class="px-2 py-3">{{ day.worked_hours }}</td>
                                    <td class="px-2 py-3">{{ day.delay_hours }}</td>
                                    <td class="px-2 py-3">{{ day.dispensation_hours }}</td>
                                    <td class="px-2 py-3">{{ day.overtime_hours }}</td>
                                    <td class="px-2 py-3">{{ day.dsr_worked_hours }}</td>
                                    <td class="px-2 py-3">
                                        <div>{{ day.records_count }}</div>
                                        <div class="mt-1 text-xs text-muted-foreground">{{ day.record_times.join(' • ') || '—' }}</div>
                                    </td>
                                    <td class="px-2 py-3">
                                        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium" :class="statusClass(day.status)">
                                            {{ day.status_label }}
                                        </span>
                                    </td>
                                    <td class="px-2 py-3">
                                        <div v-if="day.notes.length">
                                            <div v-for="(note, index) in day.notes" :key="index">
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
            </div>
        </div>
    </AppLayout>
</template>
