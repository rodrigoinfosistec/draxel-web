<script setup lang="ts">
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import AppLayout from '@/layouts/AppLayout.vue'
import { dashboard } from '@/routes'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, router } from '@inertiajs/vue3'
import { ArrowLeft, FileSearch, ChevronDown, ChevronUp, Search, AlertCircle, CheckCircle2, Calendar } from 'lucide-vue-next'
import { computed, reactive, ref } from 'vue'

type EmployeeOption = {
    id: number
    name: string
    registration: string | null
    label: string
}

type ImportItemGroupItem = {
    id: number
    line_number: number
    line_number_label: string
    employee_code: string | null
    employee_name: string | null
    recorded_at: string | null
    time: string | null
    status: string | null
    status_label: string | null
    divergence_reason: string | null
    raw_line: string
    original_raw_line: string | null
    is_manual_adjustment: boolean
    can_ignore: boolean
    can_resolve_employee: boolean
}

type ImportItemGroup = {
    employee_name: string
    date_label: string
    employee_id: number | null
    date_key: string | null
    times: string[]
    can_adjust_times: boolean
    items: ImportItemGroupItem[]
}

type ImportData = {
    id: number
    original_filename: string
    status: string | null
    status_label: string | null
    device_name: string | null
    total_items: number
    valid_items: number
    invalid_items: number
    can_launch: boolean
}

const props = defineProps<{
    import: ImportData
    groups: ImportItemGroup[]
    employees: EmployeeOption[]
}>()

const selectedEmployees = reactive<Record<number, string>>({})

const adjustModalOpen = ref(false)
const adjustModalEmployeeId = ref<number | null>(null)
const adjustModalEmployeeName = ref('')
const adjustModalDateLabel = ref('')
const adjustModalDateKey = ref<string | null>(null)
const adjustModalTimes = ref<string[]>([])

// Filtros Visuais
const showOnlyInconsistencies = ref(false)
const searchEmployeeQuery = ref('')

// Controle de abertura dos Accordions de Funcionários
const expandedEmployees = ref<Record<string, boolean>>({})

const toggleEmployee = (employeeName: string) => {
    expandedEmployees.value[employeeName] = !expandedEmployees.value[employeeName]
}

const isEmployeeExpanded = (employeeName: string) => {
    return !!expandedEmployees.value[employeeName]
}

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Dashboard', href: dashboard() },
    { title: 'Importações de ponto', href: '/worktime/clock-record-imports' },
    { title: `Importação #${props.import.id}`, href: `/worktime/clock-record-imports/${props.import.id}` },
])

// Lógica para obter o dia da semana formatado em português baseado no YYYY-MM-DD
const getDayOfWeekLabel = (dateKey: string | null) => {
    if (!dateKey) return ''

    // Adiciona o T00:00:00 para evitar problemas de fuso horário local na conversão da string
    const date = new Date(`${dateKey}T00:00:00`)
    if (isNaN(date.getTime())) return ''

    const label = new Intl.DateTimeFormat('pt-BR', { weekday: 'long' }).format(date)

    // Capitaliza a primeira letra (ex: "segunda-feira" -> "Segunda-feira")
    return label.charAt(0).toUpperCase() + label.slice(1)
}

// Lógica de agrupamento inteligente com limpeza em tempo real
const aggregatedEmployees = computed(() => {
    let baseGroups = props.groups

    // 1. Filtra as datas contendo erros se a flag estiver ativa antes de agrupar por funcionário
    if (showOnlyInconsistencies.value) {
        baseGroups = baseGroups.filter(group =>
            group.items.some(item => item.status === 'invalid')
        )
    }

    // 2. Aplica filtro de texto por nome de funcionário
    if (searchEmployeeQuery.value.trim() !== '') {
        const query = searchEmployeeQuery.value.toLowerCase()
        baseGroups = baseGroups.filter(g => g.employee_name.toLowerCase().includes(query))
    }

    // 3. Agrupa as datas filtradas por Funcionário
    const map: Record<string, { employee_name: string; has_inconsistency: boolean; total_inconsistencies: number; total_records: number; dates: ImportItemGroup[] }> = {}

    baseGroups.forEach(group => {
        const name = group.employee_name
        const groupInconsistencies = group.items.filter(item => item.status === 'invalid').length

        if (!map[name]) {
            map[name] = {
                employee_name: name,
                has_inconsistency: false,
                total_inconsistencies: 0,
                total_records: 0,
                dates: []
            }
        }

        map[name].dates.push(group)
        map[name].total_records += group.items.length
        map[name].total_inconsistencies += groupInconsistencies
        if (groupInconsistencies > 0) {
            map[name].has_inconsistency = true
        }
    })

    // 4. Transforma o mapa em Array e ordena por nome
    return Object.values(map).sort((a, b) => a.employee_name.localeCompare(b.employee_name))
})

const totalInconsistentGroupsCount = computed(() => {
    return props.groups.filter(group => group.items.some(item => item.status === 'invalid')).length
})

const launch = () => {
    if (!props.import.can_launch) return
    router.post(`/worktime/clock-record-imports/${props.import.id}/launch`)
}

const ignoreItem = (itemId: number) => {
    router.post(`/worktime/clock-record-imports/${props.import.id}/items/${itemId}/ignore`, {}, {
        preserveScroll: true
    })
}

const resolveEmployee = (itemId: number) => {
    const employeeId = selectedEmployees[itemId]
    if (!employeeId) return

    router.post(`/worktime/clock-record-imports/${props.import.id}/items/${itemId}/resolve-employee`, {
        employee_id: employeeId,
    }, {
        preserveScroll: true
    })
}

const openAdjustModal = (group: ImportItemGroup) => {
    if (!group.employee_id || !group.date_key) return

    adjustModalEmployeeId.value = group.employee_id
    adjustModalEmployeeName.value = group.employee_name
    adjustModalDateLabel.value = group.date_label
    adjustModalDateKey.value = group.date_key
    adjustModalTimes.value = group.times.length > 0 ? [...group.times] : ['']
    adjustModalOpen.value = true
}

const closeAdjustModal = () => {
    adjustModalOpen.value = false
    adjustModalEmployeeId.value = null
    adjustModalEmployeeName.value = ''
    adjustModalDateLabel.value = ''
    adjustModalDateKey.value = null
    adjustModalTimes.value = []
}

const addAdjustTime = () => {
    adjustModalTimes.value.push('')
}

const removeAdjustTime = (index: number) => {
    adjustModalTimes.value.splice(index, 1)
    if (adjustModalTimes.value.length === 0) {
        adjustModalTimes.value.push('')
    }
}

const saveAdjustTimes = () => {
    if (!adjustModalEmployeeId.value || !adjustModalDateKey.value) return

    router.post(`/worktime/clock-record-imports/${props.import.id}/adjust-times`, {
        employee_id: adjustModalEmployeeId.value,
        date: adjustModalDateKey.value,
        times: adjustModalTimes.value,
    }, {
        preserveScroll: true,
        onSuccess: () => closeAdjustModal(),
    })
}

const statusBadgeClass = (status: string | null) => {
    if (status === 'launched') return 'bg-emerald-100 text-emerald-700 ring-1 ring-inset ring-emerald-200'
    if (status === 'ready_to_launch') return 'bg-sky-100 text-sky-700 ring-1 ring-inset ring-sky-200'
    if (status === 'awaiting_review') return 'bg-amber-100 text-amber-700 ring-1 ring-inset ring-amber-200'
    if (status === 'invalid') return 'bg-red-100 text-red-700 ring-1 ring-inset ring-red-200'
    if (status === 'valid') return 'bg-emerald-100 text-emerald-700 ring-1 ring-inset ring-emerald-200'
    if (status === 'resolved') return 'bg-blue-100 text-blue-700 ring-1 ring-inset ring-blue-200'
    if (status === 'ignored') return 'bg-zinc-100 text-zinc-700 ring-1 ring-inset ring-zinc-200'
    return 'bg-zinc-100 text-zinc-700 ring-1 ring-inset ring-zinc-200'
}
</script>

<template>
    <Head :title="`Importação #${props.import.id}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <!-- Header Card -->
            <div class="relative overflow-hidden rounded-2xl border bg-card/40 p-5 shadow-sm backdrop-blur-[1px] sm:p-6">
                <div class="absolute inset-0" style="background: linear-gradient(to bottom right, var(--company-color-soft), transparent, transparent);" />
                <div class="relative flex items-start justify-between gap-4">
                    <Heading
                        :title="`Importação #${props.import.id}`"
                        description="Analise os registros agrupados por funcionário, trate as inconsistências e ajuste os horários."
                        :icon="FileSearch"
                    />
                    <Link
                        href="/worktime/clock-record-imports"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border bg-background px-4 py-2 text-sm font-medium transition hover:bg-muted"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        <span>Voltar</span>
                    </Link>
                </div>
            </div>

            <!-- Meta Informações -->
            <div class="rounded-xl border bg-card/50 p-5 shadow-sm">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div class="space-y-2">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium" :class="statusBadgeClass(props.import.status)">
                                {{ props.import.status_label ?? 'Sem status' }}
                            </span>
                        </div>
                        <div class="space-y-1 text-sm text-zinc-600 dark:text-zinc-300">
                            <p><span class="font-medium text-zinc-800 dark:text-zinc-100">Arquivo:</span> {{ props.import.original_filename }}</p>
                            <p><span class="font-medium text-zinc-800 dark:text-zinc-100">Device:</span> {{ props.import.device_name ?? '—' }}</p>
                            <p><span class="font-medium text-zinc-800 dark:text-zinc-100">Total de batidas:</span> {{ props.import.total_items }}</p>
                            <p><span class="font-medium text-zinc-800 dark:text-zinc-100">Válidas:</span> {{ props.import.valid_items }}</p>
                            <p><span class="font-medium text-zinc-800 dark:text-zinc-100">Divergentes:</span> {{ props.import.invalid_items }}</p>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-3">
                        <Button v-if="props.import.can_launch" type="button" @click="launch">
                            Lançar importação
                        </Button>
                    </div>
                </div>
            </div>

            <!-- Barra de Filtros -->
            <div class="flex flex-col gap-4 rounded-2xl border border-zinc-200 bg-card/50 p-4 shadow-sm lg:flex-row lg:items-center lg:justify-between dark:border-zinc-800 dark:bg-zinc-950">
                <div class="flex flex-wrap items-center gap-4 flex-1">
                    <div class="relative w-full max-w-xs">
                        <Search class="absolute left-3 top-2.5 h-4 w-4 text-zinc-400" />
                        <input
                            v-model="searchEmployeeQuery"
                            type="text"
                            placeholder="Buscar funcionário..."
                            class="w-full rounded-lg border border-zinc-300 bg-white pl-9 pr-4 py-2 text-sm text-zinc-700 placeholder-zinc-400 focus:border-zinc-400 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                        />
                    </div>
                    <div class="text-sm text-zinc-600 dark:text-zinc-300">
                        <span class="font-medium text-zinc-800 dark:text-zinc-100">Dias pendentes no total:</span>
                        {{ totalInconsistentGroupsCount }}
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <label class="inline-flex cursor-pointer items-center gap-3">
                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Mostrar apenas quem tem pendências e dias com erros</span>
                        <button
                            type="button"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition"
                            :class="showOnlyInconsistencies ? 'bg-red-600' : 'bg-zinc-300 dark:bg-zinc-700'"
                            @click="showOnlyInconsistencies = !showOnlyInconsistencies"
                        >
                            <span class="inline-block h-5 w-5 transform rounded-full bg-white transition" :class="showOnlyInconsistencies ? 'translate-x-5' : 'translate-x-1'" />
                        </button>
                    </label>
                </div>
            </div>

            <!-- Estado Vazio -->
            <div v-if="aggregatedEmployees.length === 0" class="rounded-2xl border border-dashed border-zinc-300 bg-card/50 p-8 text-center text-sm text-zinc-500 dark:border-zinc-800 dark:bg-zinc-950 dark:text-zinc-400">
                Nenhum registro localizado para os filtros selecionados.
            </div>

            <!-- LOOP 1: Funcionários -->
            <div
                v-for="emp in aggregatedEmployees"
                :key="emp.employee_name"
                class="overflow-hidden rounded-2xl border border-zinc-200 bg-card/50 shadow-sm dark:border-zinc-800 dark:bg-zinc-950"
            >
                <!-- Cabeçalho do Funcionário -->
                <div
                    class="border-b px-5 py-4 cursor-pointer select-none transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-900/40"
                    :class="emp.has_inconsistency ? 'border-red-100 bg-red-50/20 dark:border-red-950/10' : 'bg-zinc-50/50 dark:bg-zinc-900/20'"
                    @click="toggleEmployee(emp.employee_name)"
                >
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div>
                                <ChevronUp v-if="isEmployeeExpanded(emp.employee_name)" class="h-5 w-5 text-zinc-500" />
                                <ChevronDown v-else class="h-5 w-5 text-zinc-500" />
                            </div>
                            <div>
                                <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ emp.employee_name }}
                                </h2>
                                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">
                                    Exibindo {{ emp.dates.length }} dia(s)
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <span
                                v-if="emp.has_inconsistency"
                                class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-200"
                            >
                                <AlertCircle class="h-3 w-3" />
                                {{ emp.total_inconsistencies }} erro(s)
                            </span>
                            <span v-else class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                <CheckCircle2 class="h-3 w-3" />
                                Tudo ok
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Sub-Nível Interno -->
                <div v-show="isEmployeeExpanded(emp.employee_name)" class="bg-zinc-50/30 p-4 space-y-6 dark:bg-zinc-900/10">

                    <!-- LOOP 2: Datas do Funcionário -->
                    <div
                        v-for="group in emp.dates"
                        :key="group.date_label"
                        class="rounded-xl border border-zinc-200 bg-white shadow-xs overflow-hidden dark:border-zinc-800 dark:bg-zinc-950"
                    >
                        <div class="flex flex-wrap items-center justify-between border-b bg-zinc-50/70 px-4 py-3 gap-2 dark:bg-zinc-900/50">
                            <!-- Inserido o Dia da Semana ao lado da Data de forma pontual -->
                            <div class="flex flex-wrap items-center gap-2 text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                <Calendar class="h-4 w-4 text-zinc-400" />
                                <span>{{ group.date_label }}</span>
                                <span
                                    v-if="group.date_key"
                                    class="inline-flex items-center rounded-md bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400"
                                >
                                    {{ getDayOfWeekLabel(group.date_key) }}
                                </span>
                            </div>

                            <button
                                v-if="group.can_adjust_times"
                                type="button"
                                class="inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1.5 text-xs font-medium text-blue-700 transition hover:bg-blue-100 dark:border-blue-900/50 dark:bg-blue-950/40 dark:text-blue-300"
                                @click="openAdjustModal(group)"
                            >
                                Ajustar horários
                            </button>
                        </div>

                        <!-- Tabela de Batidas -->
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-800">
                                <thead class="bg-zinc-50/30 dark:bg-zinc-950">
                                    <tr>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Linha</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Matrícula</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Horário</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Status</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Divergência</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Linha bruta</th>
                                        <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Ações</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 bg-white dark:divide-zinc-900 dark:bg-zinc-950">
                                    <tr v-for="item in group.items" :key="item.id" class="align-top" :class="item.status === 'invalid' ? 'bg-red-50/20 dark:bg-red-950/10' : ''">
                                        <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-200">{{ item.line_number_label }}</td>
                                        <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-200">{{ item.employee_code ?? '—' }}</td>
                                        <td class="px-4 py-3 text-sm text-zinc-700 dark:text-zinc-200">{{ item.time ?? '—' }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium" :class="statusBadgeClass(item.status)">
                                                {{ item.status_label ?? '—' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-red-600 dark:text-red-400">{{ item.divergence_reason ?? '—' }}</td>
                                        <td class="max-w-xs px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400 break-all">
                                            {{ item.raw_line }}
                                            <div v-if="item.is_manual_adjustment && item.original_raw_line" class="text-[10px] text-zinc-400 mt-0.5">
                                                Original: {{ item.original_raw_line }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div v-if="item.can_resolve_employee" class="space-y-2">
                                                <select
                                                    v-model="selectedEmployees[item.id]"
                                                    class="w-56 rounded-lg border border-zinc-300 bg-white px-2 py-1.5 text-sm text-zinc-700 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100"
                                                >
                                                    <option value="">Selecione o funcionário</option>
                                                    <option v-for="employee in props.employees" :key="employee.id" :value="String(employee.id)">
                                                        {{ employee.label }}
                                                    </option>
                                                </select>
                                                <div class="flex gap-2">
                                                    <button type="button" class="bg-blue-600 text-white text-xs px-2.5 py-1.5 rounded-lg font-medium hover:bg-blue-700 disabled:opacity-50" :disabled="!selectedEmployees[item.id]" @click="resolveEmployee(item.id)">
                                                        Vincular
                                                    </button>
                                                    <button v-if="item.can_ignore" type="button" class="border border-red-200 bg-red-50 text-red-700 text-xs px-2.5 py-1.5 rounded-lg font-medium hover:bg-red-100" @click="ignoreItem(item.id)">
                                                        Ignorar
                                                    </button>
                                                </div>
                                            </div>
                                            <div v-else-if="item.can_ignore">
                                                <button type="button" class="border border-red-200 bg-red-50 text-red-700 text-xs px-2.5 py-1.5 rounded-lg font-medium hover:bg-red-100" @click="ignoreItem(item.id)">
                                                    Desconsiderar
                                                </button>
                                            </div>
                                            <span v-else class="text-xs text-zinc-400">—</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Ajuste de Horários -->
        <div v-if="adjustModalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="w-full max-w-2xl rounded-2xl bg-white shadow-xl dark:bg-zinc-950">
                <div class="border-b border-zinc-200 px-6 py-4 dark:border-zinc-800">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-zinc-100">Ajustar horários</h3>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ adjustModalEmployeeName }} — {{ adjustModalDateLabel }}</p>
                        </div>
                        <button type="button" class="rounded-lg px-3 py-2 text-sm text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-900" @click="closeAdjustModal">Fechar</button>
                    </div>
                </div>
                <div class="space-y-4 px-6 py-5">
                    <div v-for="(time, index) in adjustModalTimes" :key="index" class="flex items-center gap-3">
                        <input v-model="adjustModalTimes[index]" type="time" class="w-48 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900" />
                        <button type="button" class="border border-red-200 bg-red-50 text-red-700 text-xs px-3 py-2 rounded-lg font-medium hover:bg-red-100" @click="removeAdjustTime(index)">Remover</button>
                    </div>
                    <button type="button" class="border border-zinc-300 px-3 py-2 text-sm font-medium rounded-lg hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-900" @click="addAdjustTime">Adicionar horário</button>
                </div>
                <div class="flex justify-end gap-3 border-t border-zinc-200 px-6 py-4 dark:border-zinc-800">
                    <button type="button" class="border border-zinc-300 px-4 py-2 text-sm font-medium rounded-lg hover:bg-zinc-50 dark:border-zinc-700" @click="closeAdjustModal">Cancelar</button>
                    <button type="button" class="bg-zinc-900 text-white px-4 py-2 text-sm font-medium rounded-lg hover:bg-zinc-800 dark:bg-zinc-100 dark:text-zinc-900" @click="saveAdjustTimes">Salvar ajuste</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
