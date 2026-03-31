<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, router } from '@inertiajs/vue3'
import { ChevronDown, ChevronRight, Download, FileText, Pencil, Plus, WalletCards } from 'lucide-vue-next'
import { computed, ref } from 'vue'

type EmployeeOption = {
    id: number
    name: string
}

type EntryItem = {
    id: number
    occurred_on: string | null
    entry_type: string | null
    entry_type_label: string | null
    minutes: number
    minutes_label: string
    running_balance_minutes: number
    running_balance_label: string
    description: string | null
    can_edit: boolean
}

type AccountItem = {
    id: number
    employee_name: string | null
    current_balance_minutes: number
    current_balance_label: string
    opening_balance_minutes: number
    opening_balance_label: string
    entries: EntryItem[]
}

const props = defineProps<{
    filters: {
        employee_id: number | null
        start_date: string
        end_date: string
    }
    employees: EmployeeOption[]
    accounts: AccountItem[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Ponto', href: '/worktime' },
    { title: 'Banco de horas', href: '/worktime/bank-hours' },
]

const employeeId = ref<number | ''>(props.filters.employee_id ?? '')
const startDate = ref(props.filters.start_date)
const endDate = ref(props.filters.end_date)

const expandedAccounts = ref<number[]>([])

function submitSearch() {
    router.get(
        '/worktime/bank-hours',
        {
            employee_id: employeeId.value || undefined,
            start_date: startDate.value,
            end_date: endDate.value,
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

    if (employeeId.value) {
        params.set('employee_id', String(employeeId.value))
    }

    const query = params.toString()

    return {
        csv: `/worktime/bank-hours/export/csv?${query}`,
        pdf: `/worktime/bank-hours/export/pdf?${query}`,
    }
})

function employeePdfHref(accountId: number) {
    const params = new URLSearchParams()
    params.set('start_date', startDate.value)
    params.set('end_date', endDate.value)

    return `/worktime/bank-hours/${accountId}/export/pdf?${params.toString()}`
}

function balanceClass(balance: number) {
    if (balance < 0) {
        return 'text-red-600'
    }

    if (balance > 0) {
        return 'text-emerald-600'
    }

    return 'text-zinc-700'
}

function entryClass(minutes: number) {
    if (minutes < 0) {
        return 'bg-red-100 text-red-700'
    }

    if (minutes > 0) {
        return 'bg-emerald-100 text-emerald-700'
    }

    return 'bg-zinc-100 text-zinc-700'
}

function isExpanded(accountId: number) {
    return expandedAccounts.value.includes(accountId)
}

function toggleAccount(accountId: number) {
    if (isExpanded(accountId)) {
        expandedAccounts.value = expandedAccounts.value.filter(id => id !== accountId)
        return
    }

    expandedAccounts.value.push(accountId)
}
</script>

<template>
    <Head title="Banco de horas" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <div class="relative overflow-hidden rounded-2xl border bg-card/40 p-4 shadow-sm backdrop-blur-[1px] sm:p-5">
                <div
                    class="absolute inset-0"
                    style="background: linear-gradient(to bottom right, var(--company-color-soft), transparent, transparent);"
                />

                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Banco de horas"
                        description="Consulte saldos e movimentos de créditos e débitos por funcionário."
                        :icon="WalletCards"
                    />

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Can permission="worktime.exportBankHour">
                            <Button as-child variant="outline" class="w-full sm:w-auto">
                                <a :href="exportParams.csv">
                                    <Download class="mr-2 h-4 w-4" />
                                    Exportar CSV
                                </a>
                            </Button>
                        </Can>

                        <Can permission="worktime.exportBankHour">
                            <Button as-child variant="outline" class="w-full sm:w-auto">
                                <a :href="exportParams.pdf">
                                    <FileText class="mr-2 h-4 w-4" />
                                    Exportar PDF
                                </a>
                            </Button>
                        </Can>

                        <Can permission="worktime.createBankHourEntry">
                            <Link href="/worktime/bank-hours/create" class="w-full sm:w-auto">
                                <Button class="w-full sm:w-auto">
                                    <Plus class="mr-2 h-4 w-4" />
                                    Novo lançamento
                                </Button>
                            </Link>
                        </Can>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border bg-card/50 p-4 shadow-sm">
                <form class="grid gap-4 md:grid-cols-4" @submit.prevent="submitSearch">
                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Funcionário</label>
                        <select
                            v-model="employeeId"
                            class="flex h-10 w-full rounded-md border bg-card px-3 py-2 text-sm"
                        >
                            <option value="">Todos</option>
                            <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                                {{ employee.name }}
                            </option>
                        </select>
                    </div>

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
                            Filtrar
                        </Button>
                    </div>
                </form>
            </div>

            <div class="space-y-6">
                <div
                    v-for="account in accounts"
                    :key="account.id"
                    class="rounded-xl border bg-card/50 shadow-sm"
                >
                    <button
                        type="button"
                        class="flex w-full items-center justify-between gap-4 border-b px-4 py-4 text-left transition hover:bg-muted/40"
                        @click="toggleAccount(account.id)"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <component
                                :is="isExpanded(account.id) ? ChevronDown : ChevronRight"
                                class="h-5 w-5 shrink-0 text-muted-foreground"
                            />

                            <div class="min-w-0">
                                <div class="text-base font-semibold">
                                    {{ account.employee_name || 'Funcionário' }}
                                </div>

                                <div class="text-xs text-muted-foreground">
                                    {{ account.entries.length }} movimentação(ões)
                                </div>
                            </div>
                        </div>

                        <div class="text-right text-sm">
                            <div>
                                <span class="text-muted-foreground">Saldo atual:</span>
                                <span class="ml-2 text-lg font-semibold" :class="balanceClass(account.current_balance_minutes)">
                                    {{ account.current_balance_label }}
                                </span>
                            </div>

                            <div class="mt-1 text-xs text-muted-foreground">
                                Saldo inicial do período: {{ account.opening_balance_label }}
                            </div>
                        </div>
                    </button>

                    <div v-if="isExpanded(account.id)">
                        <div class="flex justify-end gap-3 border-b px-4 py-3">
                            <Can permission="worktime.exportBankHour">
                                <Button as-child variant="outline" size="sm">
                                    <a :href="employeePdfHref(account.id)">
                                        <FileText class="mr-2 h-4 w-4" />
                                        PDF individual
                                    </a>
                                </Button>
                            </Can>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-[980px] w-full text-sm">
                                <thead class="bg-muted/50">
                                    <tr>
                                        <th class="px-4 py-3 text-left">Data</th>
                                        <th class="px-4 py-3 text-left">Tipo</th>
                                        <th class="px-4 py-3 text-left">Movimento</th>
                                        <th class="px-4 py-3 text-left">Saldo após</th>
                                        <th class="px-4 py-3 text-left">Descrição</th>
                                        <th class="px-4 py-3 text-right">Ações</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr class="border-t bg-muted/20">
                                        <td class="px-4 py-3 font-medium" colspan="3">Saldo inicial do período</td>
                                        <td class="px-4 py-3 font-semibold" :class="balanceClass(account.opening_balance_minutes)">
                                            {{ account.opening_balance_label }}
                                        </td>
                                        <td class="px-4 py-3 text-muted-foreground" colspan="2">
                                            Acumulado anterior a {{ filters.start_date }}
                                        </td>
                                    </tr>

                                    <tr
                                        v-for="entry in account.entries"
                                        :key="entry.id"
                                        class="border-t"
                                    >
                                        <td class="px-4 py-3">{{ entry.occurred_on || '—' }}</td>
                                        <td class="px-4 py-3">{{ entry.entry_type_label || '—' }}</td>
                                        <td class="px-4 py-3">
                                            <span
                                                class="inline-flex rounded-md px-2 py-1 text-xs font-medium"
                                                :class="entryClass(entry.minutes)"
                                            >
                                                {{ entry.minutes_label }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 font-medium" :class="balanceClass(entry.running_balance_minutes)">
                                            {{ entry.running_balance_label }}
                                        </td>
                                        <td class="px-4 py-3">{{ entry.description || '—' }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <Can permission="worktime.updateBankHourEntry">
                                                <Link
                                                    v-if="entry.can_edit"
                                                    :href="`/worktime/bank-hours/${entry.id}/edit`"
                                                    class="inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-medium transition hover:bg-muted"
                                                >
                                                    <Pencil class="h-4 w-4" />
                                                    Editar
                                                </Link>
                                            </Can>
                                        </td>
                                    </tr>

                                    <tr v-if="account.entries.length === 0">
                                        <td colspan="6" class="px-4 py-6 text-center text-muted-foreground">
                                            Nenhum movimento encontrado no período.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div
                    v-if="accounts.length === 0"
                    class="rounded-xl border bg-card/50 px-4 py-8 text-center text-muted-foreground shadow-sm"
                >
                    Nenhuma conta de banco de horas encontrada.
                </div>
            </div>
        </div>
    </AppLayout>
</template>
