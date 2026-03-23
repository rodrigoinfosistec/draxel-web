<script setup lang="ts">
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft, WalletCards } from 'lucide-vue-next'

type EntryTypeOption = {
    value: string
    label: string
}

const props = defineProps<{
    entry: {
        id: number
        employee_name: string | null
        entry_type: string
        hours: string
        occurred_on: string
        description: string | null
    }
    entryTypes: EntryTypeOption[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Ponto', href: '/worktime' },
    { title: 'Banco de horas', href: '/worktime/bank-hours' },
    { title: 'Editar lançamento', href: `/worktime/bank-hours/${props.entry.id}/edit` },
]

const form = useForm({
    entry_type: props.entry.entry_type,
    hours: props.entry.hours,
    occurred_on: props.entry.occurred_on,
    description: props.entry.description ?? '',
})

function submit() {
    form.put(`/worktime/bank-hours/${props.entry.id}`)
}
</script>

<template>
    <Head title="Editar lançamento de banco de horas" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <div class="relative overflow-hidden rounded-2xl border bg-card/40 p-5 shadow-sm backdrop-blur-[1px] sm:p-6">
                <div
                    class="absolute inset-0"
                    style="background: linear-gradient(to bottom right, var(--company-color-soft), transparent, transparent);"
                />

                <div class="relative flex items-start justify-between gap-4">
                    <Heading
                        title="Editar lançamento de banco de horas"
                        :description="`Ajuste o lançamento manual de ${props.entry.employee_name ?? 'funcionário'}.`"
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
                            <input
                                :value="props.entry.employee_name || 'Funcionário'"
                                type="text"
                                disabled
                                class="flex h-10 w-full rounded-md border bg-muted px-3 py-2 text-sm"
                            >
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
                            Salvar alterações
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
