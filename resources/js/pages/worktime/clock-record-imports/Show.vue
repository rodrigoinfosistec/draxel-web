<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { dashboard } from '@/routes'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, router } from '@inertiajs/vue3'
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
    employee_code: string | null
    employee_name: string | null
    recorded_at: string | null
    time: string | null
    status: string | null
    status_label: string | null
    divergence_reason: string | null
    raw_line: string
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

const showOnlyInconsistencies = ref(false)

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
    {
        title: 'Importações de ponto',
        href: '/worktime/clock-record-imports',
    },
    {
        title: `Importação #${props.import.id}`,
        href: `/worktime/clock-record-imports/${props.import.id}`,
    },
])

const groupHasInconsistency = (group: ImportItemGroup) => {
    return group.items.some(item => item.status === 'invalid')
}

const filteredGroups = computed(() => {
    if (!showOnlyInconsistencies.value) {
        return props.groups
    }

    return props.groups.filter(group => groupHasInconsistency(group))
})

const inconsistentGroupsCount = computed(() => {
    return props.groups.filter(group => groupHasInconsistency(group)).length
})

const launch = () => {
    if (!props.import.can_launch) return

    router.post(`/worktime/clock-record-imports/${props.import.id}/launch`)
}

const ignoreItem = (itemId: number) => {
    router.post(`/worktime/clock-record-imports/${props.import.id}/items/${itemId}/ignore`)
}

const resolveEmployee = (itemId: number) => {
    const employeeId = selectedEmployees[itemId]

    if (!employeeId) {
        return
    }

    router.post(`/worktime/clock-record-imports/${props.import.id}/items/${itemId}/resolve-employee`, {
        employee_id: employeeId,
    })
}

const openAdjustModal = (group: ImportItemGroup) => {
    if (!group.employee_id || !group.date_key) {
        return
    }

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
    if (!adjustModalEmployeeId.value || !adjustModalDateKey.value) {
        return
    }

    router.post(`/worktime/clock-record-imports/${props.import.id}/adjust-times`, {
        employee_id: adjustModalEmployeeId.value,
        date: adjustModalDateKey.value,
        times: adjustModalTimes.value,
    }, {
        onSuccess: () => closeAdjustModal(),
    })
}

const statusBadgeClass = (status: string | null) => {
    if (status === 'launched') {
        return 'bg-emerald-100 text-emerald-700 ring-1 ring-inset ring-emerald-200'
    }

    if (status === 'ready_to_launch') {
        return 'bg-sky-100 text-sky-700 ring-1 ring-inset ring-sky-200'
    }

    if (status === 'awaiting_review') {
        return 'bg-amber-100 text-amber-700 ring-1 ring-inset ring-amber-200'
    }

    if (status === 'invalid') {
        return 'bg-red-100 text-red-700 ring-1 ring-inset ring-red-200'
    }

    if (status === 'valid') {
        return 'bg-emerald-100 text-emerald-700 ring-1 ring-inset ring-emerald-200'
    }

    if (status === 'resolved') {
        return 'bg-blue-100 text-blue-700 ring-1 ring-inset ring-blue-200'
    }

    if (status === 'ignored') {
        return 'bg-zinc-100 text-zinc-700 ring-1 ring-inset ring-zinc-200'
    }

    return 'bg-zinc-100 text-zinc-700 ring-1 ring-inset ring-zinc-200'
}
</script>

<template>
    <Head :title="`Importação #${props.import.id}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="space-y-6 p-6">
            <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div class="space-y-2">
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-semibold text-zinc-900">
                            Importação #{{ props.import.id }}
                        </h1>

                        <span
                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium"
                            :class="statusBadgeClass(props.import.status)"
                        >
                            {{ props.import.status_label ?? 'Sem status' }}
                        </span>
                    </div>

                    <div class="space-y-1 text-sm text-zinc-600">
                        <p><span class="font-medium text-zinc-800">Arquivo:</span> {{ props.import.original_filename }}</p>
                        <p><span class="font-medium text-zinc-800">Device:</span> {{ props.import.device_name ?? '—' }}</p>
                        <p><span class="font-medium text-zinc-800">Total de itens:</span> {{ props.import.total_items }}</p>
                        <p><span class="font-medium text-zinc-800">Itens válidos:</span> {{ props.import.valid_items }}</p>
                        <p><span class="font-medium text-zinc-800">Itens divergentes:</span> {{ props.import.invalid_items }}</p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <Link
                        href="/worktime/clock-record-imports"
                        class="inline-flex items-center rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50"
                    >
                        Voltar
                    </Link>

                    <button
                        v-if="props.import.can_launch"
                        type="button"
                        class="inline-flex items-center rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-zinc-800 disabled:cursor-not-allowed disabled:opacity-50"
                        @click="launch"
                    >
                        Lançar importação
                    </button>
                </div>
            </div>

            <div class="flex flex-col gap-3 rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm md:flex-row md:items-center md:justify-between">
                <div class="text-sm text-zinc-600">
                    <span class="font-medium text-zinc-800">Grupos com inconsistência:</span>
                    {{ inconsistentGroupsCount }}
                </div>

                <label class="inline-flex cursor-pointer items-center gap-3">
                    <span class="text-sm font-medium text-zinc-700">
                        Mostrar apenas inconsistências
                    </span>

                    <button
                        type="button"
                        class="relative inline-flex h-6 w-11 items-center rounded-full transition"
                        :class="showOnlyInconsistencies ? 'bg-red-600' : 'bg-zinc-300'"
                        @click="showOnlyInconsistencies = !showOnlyInconsistencies"
                    >
                        <span
                            class="inline-block h-5 w-5 transform rounded-full bg-white transition"
                            :class="showOnlyInconsistencies ? 'translate-x-5' : 'translate-x-1'"
                        />
                    </button>
                </label>
            </div>

            <div
                v-if="filteredGroups.length === 0"
                class="rounded-2xl border border-dashed border-zinc-300 bg-white p-8 text-center text-sm text-zinc-500"
            >
                {{
                    showOnlyInconsistencies
                        ? 'Nenhum bloco com inconsistência encontrado nesta importação.'
                        : 'Nenhum item encontrado nesta importação.'
                }}
            </div>

            <div
                v-for="group in filteredGroups"
                :key="`${group.employee_name}-${group.date_label}`"
                class="overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm"
            >
                <div
                    class="border-b px-5 py-4"
                    :class="groupHasInconsistency(group) ? 'border-red-200 bg-red-50/60' : 'border-zinc-200 bg-zinc-50'"
                >
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-base font-semibold text-zinc-900">
                                    {{ group.employee_name }}
                                </h2>

                                <span
                                    v-if="groupHasInconsistency(group)"
                                    class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-200"
                                >
                                    Com inconsistência
                                </span>
                            </div>

                            <p class="text-sm text-zinc-500">
                                {{ group.date_label }}
                            </p>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <div class="text-sm text-zinc-500">
                                {{ group.items.length }} registro(s)
                            </div>

                            <button
                                v-if="group.can_adjust_times"
                                type="button"
                                class="inline-flex items-center rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-medium text-blue-700 transition hover:bg-blue-100"
                                @click="openAdjustModal(group)"
                            >
                                Ajustar horários
                            </button>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200">
                        <thead class="bg-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Linha</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Matrícula</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Funcionário</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Horário</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Divergência</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Linha bruta</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">Ações</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-zinc-100 bg-white">
                            <tr
                                v-for="item in group.items"
                                :key="item.id"
                                class="align-top"
                                :class="item.status === 'invalid' ? 'bg-red-50/40' : ''"
                            >
                                <td class="px-4 py-3 text-sm text-zinc-700">
                                    {{ item.line_number }}
                                </td>

                                <td class="px-4 py-3 text-sm text-zinc-700">
                                    {{ item.employee_code ?? '—' }}
                                </td>

                                <td class="px-4 py-3 text-sm text-zinc-700">
                                    {{ item.employee_name ?? '—' }}
                                </td>

                                <td class="px-4 py-3 text-sm text-zinc-700">
                                    {{ item.time ?? '—' }}
                                </td>

                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                                        :class="statusBadgeClass(item.status)"
                                    >
                                        {{ item.status_label ?? '—' }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 text-sm text-red-600">
                                    {{ item.divergence_reason ?? '—' }}
                                </td>

                                <td class="max-w-md px-4 py-3 text-xs text-zinc-500">
                                    <div class="break-all">
                                        {{ item.raw_line }}
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    <div
                                        v-if="item.can_resolve_employee"
                                        class="space-y-2"
                                    >
                                        <select
                                            v-model="selectedEmployees[item.id]"
                                            class="w-64 rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700 focus:border-zinc-400 focus:outline-none"
                                        >
                                            <option value="">
                                                Selecione um funcionário
                                            </option>

                                            <option
                                                v-for="employee in props.employees"
                                                :key="employee.id"
                                                :value="String(employee.id)"
                                            >
                                                {{ employee.label }}
                                            </option>
                                        </select>

                                        <div class="flex flex-wrap gap-2">
                                            <button
                                                type="button"
                                                class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-2 text-xs font-medium text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50"
                                                :disabled="!selectedEmployees[item.id]"
                                                @click="resolveEmployee(item.id)"
                                            >
                                                Vincular funcionário
                                            </button>

                                            <button
                                                v-if="item.can_ignore"
                                                type="button"
                                                class="inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-medium text-red-700 transition hover:bg-red-100"
                                                @click="ignoreItem(item.id)"
                                            >
                                                Desconsiderar
                                            </button>
                                        </div>
                                    </div>

                                    <div
                                        v-else-if="item.can_ignore"
                                        class="flex flex-wrap gap-2"
                                    >
                                        <button
                                            type="button"
                                            class="inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-medium text-red-700 transition hover:bg-red-100"
                                            @click="ignoreItem(item.id)"
                                        >
                                            Desconsiderar
                                        </button>
                                    </div>

                                    <span
                                        v-else
                                        class="text-xs text-zinc-400"
                                    >
                                        —
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div
            v-if="adjustModalOpen"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
        >
            <div class="w-full max-w-2xl rounded-2xl bg-white shadow-xl">
                <div class="border-b border-zinc-200 px-6 py-4">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-zinc-900">
                                Ajustar horários
                            </h3>
                            <p class="text-sm text-zinc-500">
                                {{ adjustModalEmployeeName }} — {{ adjustModalDateLabel }}
                            </p>
                        </div>

                        <button
                            type="button"
                            class="rounded-lg px-3 py-2 text-sm text-zinc-500 transition hover:bg-zinc-100"
                            @click="closeAdjustModal"
                        >
                            Fechar
                        </button>
                    </div>
                </div>

                <div class="space-y-4 px-6 py-5">
                    <div
                        v-for="(time, index) in adjustModalTimes"
                        :key="index"
                        class="flex items-center gap-3"
                    >
                        <input
                            v-model="adjustModalTimes[index]"
                            type="time"
                            class="w-48 rounded-lg border border-zinc-300 px-3 py-2 text-sm text-zinc-700 focus:border-zinc-400 focus:outline-none"
                        />

                        <button
                            type="button"
                            class="inline-flex items-center rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-xs font-medium text-red-700 transition hover:bg-red-100"
                            @click="removeAdjustTime(index)"
                        >
                            Remover
                        </button>
                    </div>

                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg border border-zinc-300 px-3 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50"
                        @click="addAdjustTime"
                    >
                        Adicionar horário
                    </button>
                </div>

                <div class="flex justify-end gap-3 border-t border-zinc-200 px-6 py-4">
                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg border border-zinc-300 px-4 py-2 text-sm font-medium text-zinc-700 transition hover:bg-zinc-50"
                        @click="closeAdjustModal"
                    >
                        Cancelar
                    </button>

                    <button
                        type="button"
                        class="inline-flex items-center rounded-lg bg-zinc-900 px-4 py-2 text-sm font-medium text-white transition hover:bg-zinc-800"
                        @click="saveAdjustTimes"
                    >
                        Salvar ajuste
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
