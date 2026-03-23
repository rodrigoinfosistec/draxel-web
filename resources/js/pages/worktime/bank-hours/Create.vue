<script setup lang="ts">
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft, WalletCards } from 'lucide-vue-next'

type EmployeeOption = {
    id: number
    name: string
}

type EntryTypeOption = {
    value: string
    label: string
}

const props = defineProps<{
    employees: EmployeeOption[]
    entryTypes: EntryTypeOption[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Ponto', href: '/worktime' },
    { title: 'Banco de horas', href: '/worktime/bank-hours' },
    { title: 'Novo lançamento', href: '/worktime/bank-hours/create' },
]

const form = useForm({
    employee_id: '',
    entry_type: 'manual_credit',
    hours: '00:30',
    occurred_on: new Date().toISOString().slice(0, 10),
    description: '',
})

function submit() {
    form.post('/worktime/bank-hours')
}
</script>

<template>
    <Head title="Novo lançamento de banco de horas" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <div class="relative overflow-hidden rounded-2xl border bg-card/40 p-5 shadow-sm backdrop-blur-[1px] sm:p-6">
                <div
                    class="absolute inset-0"
                    style="background: linear-gradient(to bottom right, var(--company-color-soft), transparent, transparent);"
                />

                <div class="relative flex items-start justify-between gap-4">
                    <Heading
                        title="Novo lançamento de banco de horas"
                        description="Registre um crédito ou débito manual diretamente no banco de horas."
                        :icon="WalletCards"
                    />

                    <Link
                        href="/worktime/bank-hours"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border bg-background px-4 py-2 text-sm font-medium transition hover:bg-muted"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        <span>Voltar</span>
                    </Link>
                </div>
            </div>

            <div class="rounded-xl border bg-card/50 p-5 shadow-sm">
                <form class="space-y-5" @submit.prevent="submit">
                    <div class="grid gap-5 md:grid-cols-2">
                        <div class="grid gap-2">
                            <label class="text-sm font-medium">Funcionário</label>
                            <select
                                v-model="form.employee_id"
                                class="flex h-10 w-full rounded-md border bg-card px-3 py-2 text-sm"
                            >
                                <option value="">Selecione</option>
                                <option v-for="employee in props.employees" :key="employee.id" :value="employee.id">
                                    {{ employee.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.employee_id" />
                        </div>

                        <div class="grid gap-2">
                            <label class="text-sm font-medium">Tipo</label>
                            <select
                                v-model="form.entry_type"
                                class="flex h-10 w-full rounded-md border bg-card px-3 py-2 text-sm"
                            >
                                <option v-for="entryType in props.entryTypes" :key="entryType.value" :value="entryType.value">
                                    {{ entryType.label }}
                                </option>
                            </select>
                            <InputError :message="form.errors.entry_type" />
                        </div>

                        <div class="grid gap-2">
                            <label class="text-sm font-medium">Horas</label>
                            <input
                                v-model="form.hours"
                                type="text"
                                placeholder="Ex.: 03:30"
                                class="flex h-10 w-full rounded-md border bg-card px-3 py-2 text-sm"
                            >
                            <InputError :message="form.errors.hours" />
                        </div>

                        <div class="grid gap-2">
                            <label class="text-sm font-medium">Data</label>
                            <input
                                v-model="form.occurred_on"
                                type="date"
                                class="flex h-10 w-full rounded-md border bg-card px-3 py-2 text-sm"
                            >
                            <InputError :message="form.errors.occurred_on" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <label class="text-sm font-medium">Descrição</label>
                        <textarea
                            v-model="form.description"
                            rows="4"
                            class="w-full rounded-md border bg-card px-3 py-2 text-sm"
                        />
                        <InputError :message="form.errors.description" />
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Button type="submit" :disabled="form.processing">
                            Salvar
                        </Button>

                        <Link href="/worktime/bank-hours">
                            <Button type="button" variant="outline">
                                Cancelar
                            </Button>
                        </Link>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
