<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ArrowLeft, Download, FileClock, RefreshCw, RotateCcw, Trash2 } from 'lucide-vue-next'
import Swal from 'sweetalert2'
import { computed } from 'vue'

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
    justified_minutes: string
    late_minutes: string
    extra_minutes: string
    suspension_minutes: string
    balance_minutes: string
    has_divergence: boolean
    divergence_reason: string | null
    notes: string | null
}

type SnapshotEmployee = {
    id: number
    employee_id: number
    employee_name: string
    employee_registration: string | null
    justified_minutes: string
    late_minutes: string
    extra_minutes: string
    suspension_minutes: string
    balance_minutes: string
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

const reverseForm = useForm({
    reason: '',
})

const duplicateEmployees = computed(() => Object.keys(props.snapshot.duplicate_dates))
const selectableEmployees = computed(() => props.availableEmployees)

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
    const result = await Swal.fire({
        title: 'Remover funcionário?',
        text: 'O funcionário será retirado deste fechamento. Você poderá incluí-lo novamente depois, capturando o estado atual.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sim, remover',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
    })

    if (!result.isConfirmed) {
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

function consolidate() {
    router.post(`/worktime/hour-bank-snapshots/${props.snapshot.id}/consolidate`)
}

function reverseSnapshot() {
    reverseForm.post(`/worktime/hour-bank-snapshots/${props.snapshot.id}/reverse`)
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

            <form
                v-if="snapshot.is_editable"
                class="space-y-4 rounded-2xl border bg-card/50 p-5 shadow-sm"
                @submit.prevent="submitAddEmployees"
            >
                <div>
                    <h2 class="text-sm font-semibold tracking-tight">Incluir funcionários</h2>
                    <p class="text-sm text-muted-foreground">
                        O sistema captura o estado atual do funcionário no período informado.
                    </p>
                </div>

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
                    <Button :disabled="addForm.processing || selectableEmployees.length === 0">
                        {{ addForm.processing ? 'Incluindo...' : 'Incluir funcionários' }}
                    </Button>
                </div>
            </form>

            <div
                v-if="snapshot.is_consolidated && !snapshot.reversed_at"
                class="space-y-4 rounded-2xl border bg-card/50 p-5 shadow-sm"
            >
                <div>
                    <h2 class="text-sm font-semibold tracking-tight">Reversão do fechamento</h2>
                    <p class="text-sm text-muted-foreground">
                        A reversão deve ser usada apenas em situações excepcionais e exige justificativa.
                    </p>
                </div>

                <Can permission="worktime.reverseHourBankSnapshot">
                    <form class="space-y-4" @submit.prevent="reverseSnapshot">
                        <div class="grid gap-2">
                            <label class="text-sm font-medium">Motivo da reversão</label>
                            <textarea
                                v-model="reverseForm.reason"
                                rows="4"
                                class="w-full rounded-md border bg-card px-3 py-2 text-sm"
                            />
                            <InputError :message="reverseForm.errors.reason" />
                        </div>

                        <div class="flex justify-end">
                            <Button :disabled="reverseForm.processing" variant="outline" type="submit">
                                <RotateCcw class="mr-2 h-4 w-4" />
                                {{ reverseForm.processing ? 'Revertendo...' : 'Reverter fechamento' }}
                            </Button>
                        </div>
                    </form>
                </Can>
            </div>

            <div class="space-y-6">
                <div
                    v-for="employee in employees"
                    :key="employee.id"
                    class="rounded-2xl border bg-card/50 shadow-sm"
                >
                    <div class="border-b px-5 py-4">
                        <div class="flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
                            <div>
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
                            </div>

                            <div class="grid gap-2 text-sm xl:text-right">
                                <div>Justificadas: {{ employee.justified_minutes }}</div>
                                <div>Atrasos: {{ employee.late_minutes }}</div>
                                <div>Extras: {{ employee.extra_minutes }}</div>
                                <div>Suspensões: {{ employee.suspension_minutes }}</div>
                                <div class="font-semibold">Saldo: {{ employee.balance_minutes }}</div>
                            </div>
                        </div>

                        <div v-if="snapshot.is_editable" class="mt-4 flex flex-wrap items-end gap-2">
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
                        <table class="min-w-[1200px] w-full text-sm">
                            <thead class="bg-muted/50">
                                <tr>
                                    <th class="px-4 py-3 text-left">Data</th>
                                    <th class="px-4 py-3 text-left">Dia</th>
                                    <th class="px-4 py-3 text-left">Jornada esperada</th>
                                    <th class="px-4 py-3 text-left">Registros</th>
                                    <th class="px-4 py-3 text-left">Justificadas</th>
                                    <th class="px-4 py-3 text-left">Atrasos</th>
                                    <th class="px-4 py-3 text-left">Extras</th>
                                    <th class="px-4 py-3 text-left">Suspensões</th>
                                    <th class="px-4 py-3 text-left">Saldo</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr
                                    v-for="day in employee.days"
                                    :key="day.id"
                                    class="border-t"
                                >
                                    <td class="px-4 py-3">{{ day.work_date }}</td>
                                    <td class="px-4 py-3">{{ day.weekday_label }}</td>
                                    <td class="px-4 py-3">{{ day.expected_schedule }}</td>
                                    <td class="px-4 py-3" :class="recordsCellClass(day.records_variant)">
                                        {{ day.records_label }}
                                    </td>
                                    <td class="px-4 py-3">{{ day.justified_minutes }}</td>
                                    <td class="px-4 py-3">{{ day.late_minutes }}</td>
                                    <td class="px-4 py-3">{{ day.extra_minutes }}</td>
                                    <td class="px-4 py-3">{{ day.suspension_minutes }}</td>
                                    <td class="px-4 py-3">{{ day.balance_minutes }}</td>
                                </tr>

                                <tr class="border-t bg-muted/30 font-semibold">
                                    <td colspan="4" class="px-4 py-3 text-right">Totais</td>
                                    <td class="px-4 py-3">{{ employee.justified_minutes }}</td>
                                    <td class="px-4 py-3">{{ employee.late_minutes }}</td>
                                    <td class="px-4 py-3">{{ employee.extra_minutes }}</td>
                                    <td class="px-4 py-3">{{ employee.suspension_minutes }}</td>
                                    <td class="px-4 py-3">{{ employee.balance_minutes }}</td>
                                </tr>
                            </tbody>
                        </table>
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
