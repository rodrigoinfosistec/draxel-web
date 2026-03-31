<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { useConfirm } from '@/composables/useConfirm'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ArrowLeft, ChevronDown, ChevronUp, Download, FileClock, RefreshCw, Trash2 } from 'lucide-vue-next'
import { computed, ref } from 'vue'

type AvailableEmployee = {
    id: number
    name: string
    registration: string | null
    label: string
}

type SnapshotDay = {
    id: number
    work_date: string
    weekday_label: string
    expected_schedule: string
    records_label: string
    records_variant: 'default' | 'info' | 'muted' | 'danger'
    justified_minutes: number
    justified_hours: string
    late_minutes: number
    late_hours: string
    extra_minutes: number
    extra_hours: string
    suspension_minutes: number
    suspension_hours: string
    dsr_worked_minutes: number
    dsr_worked_hours: string
    balance_minutes: number
    balance_hours: string
    has_divergence: boolean
    divergence_reason: string | null
    notes: string | null
}

type SnapshotEmployee = {
    id: number
    employee_id: number
    employee_name: string
    employee_registration: string | null
    justified_minutes: number
    justified_hours: string
    late_minutes: number
    late_hours: string
    extra_minutes: number
    extra_hours: string
    suspension_minutes: number
    suspension_hours: string
    dsr_worked_minutes: number
    dsr_worked_hours: string
    balance_minutes: number
    balance_hours: string
    has_divergence: boolean
    divergence_summary: string | null
    can_generate_individual_report: boolean
    days: SnapshotDay[]
}

type Snapshot = {
    id: number
    name: string
    period_start: string
    period_end: string
    period_label: string
    status: string
    status_label: string
    notes: string | null
    is_editable: boolean
    is_consolidated: boolean
    can_generate_preview_general: boolean
    can_consolidate: boolean
    validation_errors: string[]
    duplicate_dates: Record<string, string[]>
    consolidated_at: string | null
    reversed_at: string | null
    reversal_reason: string | null
}

const props = defineProps<{
    snapshot: Snapshot
    employees: SnapshotEmployee[]
    availableEmployees: AvailableEmployee[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Ponto', href: '/worktime' },
    { title: 'Fechamentos', href: '/worktime/hour-bank-snapshots' },
    { title: props.snapshot.name, href: `/worktime/hour-bank-snapshots/${props.snapshot.id}` },
]

const addForm = useForm({
    employee_ids: [] as number[],
})

const duplicateEmployees = computed(() => Object.keys(props.snapshot.duplicate_dates))
const selectableEmployees = computed(() => props.availableEmployees)
const collapsedEmployees = ref<number[]>([])

function refreshPage() {
    router.get(
        `/worktime/hour-bank-snapshots/${props.snapshot.id}`,
        {},
        {
            preserveScroll: true,
            preserveState: false,
            replace: true,
        },
    )
}

function submitAddEmployees() {
    addForm.post(`/worktime/hour-bank-snapshots/${props.snapshot.id}/employees`, {
        preserveScroll: true,
        onSuccess: () => {
            addForm.reset()
            refreshPage()
        },
    })
}

async function removeEmployee(snapshotEmployeeId: number) {
    const confirmed = await useConfirm({
        title: 'Remover funcionário?',
        text: 'O funcionário será retirado deste fechamento. Você poderá incluí-lo novamente depois, capturando o estado atual.',
        confirmButtonText: 'Sim, remover',
        cancelButtonText: 'Cancelar',
        icon: 'warning',
    })

    if (!confirmed) {
        return
    }

    router.delete(`/worktime/hour-bank-snapshots/${props.snapshot.id}/employees/${snapshotEmployeeId}`, {
        preserveScroll: true,
        onSuccess: () => {
            refreshPage()
        },
    })
}

function recaptureEmployee(employeeId: number) {
    router.post(
        `/worktime/hour-bank-snapshots/${props.snapshot.id}/employees`,
        {
            employee_ids: [employeeId],
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                refreshPage()
            },
        },
    )
}

async function consolidate() {
    const confirmed = await useConfirm({
        title: 'Consolidar fechamento?',
        text: 'Essa ação lançará o saldo do período no histórico do banco de horas.',
        confirmButtonText: 'Sim, consolidar',
        cancelButtonText: 'Cancelar',
        icon: 'warning',
    })

    if (!confirmed) {
        return
    }

    router.post(`/worktime/hour-bank-snapshots/${props.snapshot.id}/consolidate`)
}

function recordsCellClass(variant: SnapshotDay['records_variant']) {
    if (variant === 'danger') {
        return 'font-medium text-red-600'
    }

    if (variant === 'info') {
        return 'text-amber-700'
    }

    if (variant === 'muted') {
        return 'text-muted-foreground'
    }

    return ''
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
    collapsedEmployees.value = props.employees.map((employee) => employee.id)
}
</script>

<template>
    <Head :title="snapshot.name" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <div
                class="relative overflow-hidden rounded-2xl border bg-card/40 p-4 shadow-sm backdrop-blur-[1px] sm:p-5"
            >
                <div
                    class="absolute inset-0"
                    style="background: linear-gradient(to bottom right, var(--company-color-soft), transparent, transparent);"
                />

                <div class="relative flex items-start justify-between gap-4">
                    <Heading
                        :title="snapshot.name"
                        :description="`Período: ${snapshot.period_label}`"
                        :icon="FileClock"
                    />

                    <Link
                        href="/worktime/hour-bank-snapshots"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border bg-background px-4 py-2 text-sm font-medium transition hover:bg-muted"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        <span>Voltar</span>
                    </Link>
                </div>
            </div>

            <div class="rounded-2xl border bg-card/50 p-5 shadow-sm">
                <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr),320px]">
                    <div class="space-y-2 text-sm">
                        <p><span class="font-medium">Status:</span> {{ snapshot.status_label }}</p>
                        <p><span class="font-medium">Período:</span> {{ snapshot.period_label }}</p>
                        <p><span class="font-medium">Observações:</span> {{ snapshot.notes || '—' }}</p>
                        <p v-if="snapshot.consolidated_at"><span class="font-medium">Consolidado em:</span> {{ snapshot.consolidated_at }}</p>
                        <p v-if="snapshot.reversed_at"><span class="font-medium">Revertido em:</span> {{ snapshot.reversed_at }}</p>
                        <p v-if="snapshot.reversal_reason"><span class="font-medium">Motivo da reversão:</span> {{ snapshot.reversal_reason }}</p>
                    </div>

                    <div class="flex flex-col gap-3">
                        <Can permission="worktime.exportHourBankSnapshot">
                            <a
                                v-if="snapshot.can_generate_preview_general"
                                :href="`/worktime/hour-bank-snapshots/reports/${snapshot.id}/general-preview`"
                                class="inline-flex"
                            >
                                <Button variant="outline" class="w-full" type="button">
                                    <Download class="mr-2 h-4 w-4" />
                                    Relatório geral prévio
                                </Button>
                            </a>
                        </Can>

                        <Can permission="worktime.exportHourBankSnapshot">
                            <a
                                v-if="snapshot.is_consolidated"
                                :href="`/worktime/hour-bank-snapshots/reports/${snapshot.id}/general-consolidated`"
                                class="inline-flex"
                            >
                                <Button variant="outline" class="w-full" type="button">
                                    <Download class="mr-2 h-4 w-4" />
                                    Relatório geral consolidado
                                </Button>
                            </a>
                        </Can>

                        <Can permission="worktime.consolidateHourBankSnapshot">
                            <Button
                                v-if="snapshot.is_editable"
                                :disabled="!snapshot.can_consolidate"
                                type="button"
                                @click="consolidate"
                            >
                                Consolidar fechamento
                            </Button>
                        </Can>
                    </div>
                </div>

                <div v-if="snapshot.validation_errors.length > 0" class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                    <div class="font-medium">Bloqueios do fechamento</div>
                    <ul class="mt-2 space-y-1">
                        <li v-for="error in snapshot.validation_errors" :key="error">• {{ error }}</li>
                    </ul>
                </div>

                <div v-if="duplicateEmployees.length > 0" class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                    <div class="font-medium">Datas já consolidadas encontradas</div>
                    <div class="mt-2 space-y-2">
                        <div v-for="employeeId in duplicateEmployees" :key="employeeId">
                            Funcionário #{{ employeeId }}: {{ snapshot.duplicate_dates[employeeId].join(', ') }}
                        </div>
                    </div>
                </div>
            </div>

            <div
                v-if="snapshot.is_editable"
                class="space-y-4 rounded-2xl border bg-card/50 p-5 shadow-sm"
            >
                <div>
                    <h2 class="text-sm font-semibold tracking-tight">Incluir funcionários</h2>
                    <p class="text-sm text-muted-foreground">
                        O sistema captura o estado atual do funcionário no período informado.
                    </p>
                </div>

                <form
                    v-if="selectableEmployees.length > 0"
                    class="space-y-4"
                    @submit.prevent="submitAddEmployees"
                >
                    <div class="grid gap-2">
                        <select
                            v-model="addForm.employee_ids"
                            multiple
                            class="min-h-[180px] rounded-md border bg-card px-3 py-2 text-sm"
                        >
                            <option
                                v-for="employee in selectableEmployees"
                                :key="employee.id"
                                :value="employee.id"
                            >
                                {{ employee.label }}
                            </option>
                        </select>
                        <InputError :message="addForm.errors.employee_ids" />
                    </div>

                    <div class="flex justify-end">
                        <Button :disabled="addForm.processing">
                            {{ addForm.processing ? 'Incluindo...' : 'Incluir funcionários' }}
                        </Button>
                    </div>
                </form>

                <div
                    v-else
                    class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
                >
                    Todos os funcionários disponíveis para este período já foram incluídos no fechamento.
                </div>
            </div>

            <div class="flex items-center justify-end gap-3">
                <Button type="button" variant="outline" @click="expandAll">Expandir tudo</Button>
                <Button type="button" variant="outline" @click="collapseAll">Recolher tudo</Button>
            </div>

            <div class="space-y-6">
                <div
                    v-for="employee in employees"
                    :key="employee.id"
                    class="overflow-hidden rounded-2xl border bg-card/50 shadow-sm"
                >
                    <button
                        type="button"
                        class="flex w-full items-center justify-between gap-4 border-b px-5 py-4 text-left"
                        @click="toggleCollapse(employee.id)"
                    >
                        <div class="min-w-0">
                            <div class="text-base font-semibold">
                                {{ employee.employee_name }}
                            </div>
                            <div class="text-sm text-muted-foreground">
                                Matrícula: {{ employee.employee_registration || '—' }}
                            </div>

                            <div
                                v-if="employee.has_divergence"
                                class="mt-2 text-sm text-red-600"
                            >
                                {{ employee.divergence_summary }}
                            </div>

                            <div class="mt-2 flex flex-wrap gap-4 text-sm text-muted-foreground">
                                <span>Justificadas: {{ employee.justified_hours }}</span>
                                <span>Atrasos: {{ employee.late_hours }}</span>
                                <span>Dispensa: {{ employee.suspension_hours }}</span>
                                <span>Extras: {{ employee.extra_hours }}</span>
                                <span>DSR/Feriado: {{ employee.dsr_worked_hours }}</span>
                                <span class="font-semibold text-foreground">Saldo: {{ employee.balance_hours }}</span>
                            </div>
                        </div>

                        <component :is="isCollapsed(employee.id) ? ChevronDown : ChevronUp" class="h-5 w-5 shrink-0" />
                    </button>

                    <div v-if="!isCollapsed(employee.id)">
                        <div class="border-b px-5 py-4">
                            <div v-if="snapshot.is_editable" class="flex flex-wrap items-end gap-2">
                                <Button type="button" variant="outline" @click="recaptureEmployee(employee.employee_id)">
                                    <RefreshCw class="mr-2 h-4 w-4" />
                                    Recapturar
                                </Button>

                                <Button
                                    type="button"
                                    variant="outline"
                                    class="border-red-200 text-red-600 hover:bg-red-50 hover:text-red-700"
                                    @click="removeEmployee(employee.id)"
                                >
                                    <Trash2 class="mr-2 h-4 w-4" />
                                    Remover
                                </Button>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-2">
                                <Can permission="worktime.exportHourBankSnapshot">
                                    <a
                                        v-if="employee.can_generate_individual_report"
                                        :href="`/worktime/hour-bank-snapshots/reports/${snapshot.id}/employees/${employee.id}`"
                                        class="inline-flex"
                                    >
                                        <Button variant="outline" type="button">
                                            <Download class="mr-2 h-4 w-4" />
                                            Relatório individual
                                        </Button>
                                    </a>
                                </Can>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-[1000px] w-full text-sm">
                                <thead class="bg-muted/50">
                                    <tr>
                                        <th class="px-2 py-3 text-left">Data</th>
                                        <th class="px-2 py-3 text-left">Jornada esperada</th>
                                        <th class="px-2 py-3 text-left">Registros</th>
                                        <th class="px-2 py-3 text-left">Justif.</th>
                                        <th class="px-2 py-3 text-left">Atrasos</th>
                                        <th class="px-2 py-3 text-left text-xs">Dispensa</th>
                                        <th class="px-2 py-3 text-left">Extras</th>
                                        <th class="px-2 py-3 text-left text-xs">
                                            DSR<br/>Feriado
                                        </th>
                                        <th class="px-2 py-3 text-left">Saldo</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr
                                        v-for="day in employee.days"
                                        :key="day.id"
                                        class="border-t"
                                    >
                                        <td class="px-2 py-3">
                                            <div class="text-xs">{{ day.work_date }}</div>
                                            <div class="text-muted-foreground">{{ day.weekday_label }}</div>
                                        </td>
                                        <td class="px-2 py-3 text-xs">{{ day.expected_schedule }}</td>
                                        <td class="px-2 py-3 max-w-[80px] break-words whitespace-normal align-middle text-xs"
    :class="recordsCellClass(day.records_variant)">
    {{ day.records_label }}
</td>
                                        <td class="px-2 py-3">{{ day.justified_hours }}</td>
                                        <td class="px-2 py-3">{{ day.late_hours }}</td>
                                        <td class="px-2 py-3">{{ day.suspension_hours }}</td>
                                        <td class="px-2 py-3">{{ day.extra_hours }}</td>
                                        <td class="px-2 py-3">{{ day.dsr_worked_hours }}</td>
                                        <td class="px-2 py-3">{{ day.balance_hours }}</td>
                                    </tr>

                                    <tr class="border-t bg-muted/30 font-semibold">
                                        <td colspan="3" class="px-4 py-3 text-right">Totais</td>
                                        <td class="px-2 py-3">{{ employee.justified_hours }}</td>
                                        <td class="px-2 py-3">{{ employee.late_hours }}</td>
                                        <td class="px-2 py-3">{{ employee.suspension_hours }}</td>
                                        <td class="px-2 py-3">{{ employee.extra_hours }}</td>
                                        <td class="px-2 py-3">{{ employee.dsr_worked_hours }}</td>
                                        <td class="px-2 py-3">{{ employee.balance_hours }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div
                    v-if="employees.length === 0"
                    class="rounded-2xl border bg-card/50 px-4 py-8 text-center text-muted-foreground shadow-sm"
                >
                    Nenhum funcionário incluído neste fechamento.
                </div>
            </div>
        </div>
    </AppLayout>
</template>
