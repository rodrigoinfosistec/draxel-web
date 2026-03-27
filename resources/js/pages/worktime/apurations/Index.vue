<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, router } from '@inertiajs/vue3'
import { Calculator, ChevronDown, ChevronRight, Download, FileText } from 'lucide-vue-next'
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

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-6">
                <div class="rounded-xl border bg-card/50 p-4 shadow-sm">
                    <div class="text-sm text-muted-foreground">Funcionários</div>
                    <div class="mt-1 text-2xl font-semibold">{{ apuration.totals.employees_count }}</div>
                </div>

                <div class="rounded-xl border bg-card/50 p-4 shadow-sm">
                    <div class="text-sm text-muted-foreground">Dias apurados</div>
                    <div class="mt-1 text-2xl font-semibold">{{ apuration.totals.days_count }}</div>
                </div>

                <div class="rounded-xl border bg-card/50 p-4 shadow-sm">
                    <div class="text-sm text-muted-foreground">Previsto</div>
                    <div class="mt-1 text-2xl font-semibold">{{ apuration.totals.expected_hours }}</div>
                </div>

                <div class="rounded-xl border bg-card/50 p-4 shadow-sm">
                    <div class="text-sm text-muted-foreground">Trabalhado</div>
                    <div class="mt-1 text-2xl font-semibold">{{ apuration.totals.worked_hours }}</div>
                </div>

                <div class="rounded-xl border bg-card/50 p-4 shadow-sm">
                    <div class="text-sm text-muted-foreground">Atrasos</div>
                    <div class="mt-1 text-2xl font-semibold">{{ apuration.totals.delay_hours }}</div>
                </div>

                <div class="rounded-xl border bg-card/50 p-4 shadow-sm">
                    <div class="text-sm text-muted-foreground">Dispensas</div>
                    <div class="mt-1 text-2xl font-semibold">{{ apuration.totals.dispensation_hours }}</div>
                </div>
            </div>

            <div
                v-if="apuration.employees.length > 0"
                class="flex flex-wrap justify-end gap-2"
            >
                <Button type="button" variant="outline" @click="expandAll">
                    Expandir todos
                </Button>

                <Button type="button" variant="outline" @click="collapseAll">
                    Recolher todos
                </Button>
            </div>

            <div class="space-y-6">
                <div
                    v-for="employee in apuration.employees"
                    :key="employee.id"
                    class="rounded-xl border bg-card/50 shadow-sm"
                >
                    <button
                        type="button"
                        class="flex w-full items-start justify-between gap-4 border-b px-4 py-4 text-left transition hover:bg-muted/30"
                        @click="toggleCollapse(employee.id)"
                    >
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="mt-0.5 text-muted-foreground">
                                <ChevronRight v-if="isCollapsed(employee.id)" class="h-5 w-5" />
                                <ChevronDown v-else class="h-5 w-5" />
                            </span>

                            <div>
                                <div class="text-base font-semibold">{{ employee.name }}</div>

                                <div class="mt-3 grid gap-3 text-sm md:grid-cols-3 xl:grid-cols-5">
                                    <div><span class="text-muted-foreground">Previsto:</span> {{ employee.summary.expected_hours }}</div>
                                    <div><span class="text-muted-foreground">Trabalhado:</span> {{ employee.summary.worked_hours }}</div>
                                    <div><span class="text-muted-foreground">Atrasos:</span> {{ employee.summary.delay_hours }}</div>
                                    <div><span class="text-muted-foreground">Dispensas:</span> {{ employee.summary.dispensation_hours }}</div>
                                    <div><span class="text-muted-foreground">Extras:</span> {{ employee.summary.overtime_hours }}</div>
                                    <div><span class="text-muted-foreground">Dias trabalhados:</span> {{ employee.summary.worked_days }}</div>
                                    <div><span class="text-muted-foreground">Inconsistentes:</span> {{ employee.summary.inconsistent_days }}</div>
                                    <div><span class="text-muted-foreground">Atenção:</span> {{ employee.summary.warning_days }}</div>
                                </div>
                            </div>
                        </div>
                    </button>

                    <div v-show="!isCollapsed(employee.id)" class="overflow-x-auto">
                        <table class="min-w-[1460px] w-full text-sm">
                            <thead class="bg-muted/50">
                                <tr>
                                    <th class="px-4 py-3 text-left">Data</th>
                                    <th class="px-4 py-3 text-left">Dia</th>
                                    <th class="px-4 py-3 text-left">Previsto</th>
                                    <th class="px-4 py-3 text-left">Trabalhado</th>
                                    <th class="px-4 py-3 text-left">Atrasos</th>
                                    <th class="px-4 py-3 text-left">Dispensa</th>
                                    <th class="px-4 py-3 text-left">Extra</th>
                                    <th class="px-4 py-3 text-left">Registros</th>
                                    <th class="px-4 py-3 text-left">Horários</th>
                                    <th class="px-4 py-3 text-left">Status</th>
                                    <th class="px-4 py-3 text-left">Observações</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr
                                    v-for="day in employee.days"
                                    :key="`${employee.id}-${day.date}`"
                                    class="border-t align-top"
                                    :class="{
                                        'bg-red-50/40': day.status === 'inconsistent',
                                    }"
                                >
                                    <td class="px-4 py-3">{{ day.date_label }}</td>
                                    <td class="px-4 py-3">{{ day.weekday_label }}</td>
                                    <td class="px-4 py-3">{{ day.expected_hours }}</td>
                                    <td class="px-4 py-3">{{ day.worked_hours }}</td>
                                    <td class="px-4 py-3">{{ day.delay_hours }}</td>
                                    <td class="px-4 py-3">{{ day.dispensation_hours }}</td>
                                    <td class="px-4 py-3">{{ day.overtime_hours }}</td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex rounded-md px-2 py-1 text-xs font-medium"
                                            :class="day.status === 'inconsistent'
                                                ? 'bg-red-100 text-red-700'
                                                : 'bg-zinc-100 text-zinc-700'"
                                        >
                                            {{ day.records_count }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div v-if="day.record_times?.length" class="flex flex-wrap gap-1">
                                            <span
                                                v-for="time in day.record_times"
                                                :key="time"
                                                class="inline-flex rounded-md bg-zinc-100 px-2 py-1 text-xs text-zinc-700"
                                            >
                                                {{ time }}
                                            </span>
                                        </div>
                                        <span v-else>—</span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            class="inline-flex rounded-md px-2 py-1 text-xs font-medium"
                                            :class="statusClass(day.status)"
                                        >
                                            {{ day.status_label }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div v-if="day.notes.length" class="space-y-1">
                                            <div
                                                v-for="note in day.notes"
                                                :key="note"
                                                class="rounded-md px-2 py-1 text-xs"
                                                :class="day.status === 'inconsistent'
                                                    ? 'bg-red-100 text-red-700'
                                                    : 'bg-muted text-muted-foreground'"
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
