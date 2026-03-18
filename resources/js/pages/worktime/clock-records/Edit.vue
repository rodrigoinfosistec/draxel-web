<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { useConfirm } from '@/composables/useConfirm'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft, Clock3 } from 'lucide-vue-next'

type Option = {
    id: number
    name: string
}

type RecordFormData = {
    id: number
    employee_id: number
    date: string
    time: string
    notes: string | null
}

const props = defineProps<{
    record: RecordFormData
    employees: Option[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Ponto', href: '/worktime' },
    { title: 'Registros de ponto', href: '/worktime/clock-records' },
    { title: 'Editar registro', href: `/worktime/clock-records/${props.record.id}/edit` },
]

const form = useForm({
    employee_id: props.record.employee_id,
    date: props.record.date,
    time: props.record.time,
    notes: props.record.notes ?? '',
})

function submit() {
    form.put(`/worktime/clock-records/${props.record.id}`)
}

async function destroy() {
    const confirmed = await useConfirm({
        title: 'Excluir registro?',
        text: 'Essa ação removerá o registro permanentemente.',
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar',
        icon: 'warning',
    })

    if (!confirmed) {
        return
    }

    form.delete(`/worktime/clock-records/${props.record.id}`)
}
</script>

<template>
    <Head title="Editar registro" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <div class="relative overflow-hidden rounded-2xl border bg-card/40 p-4 shadow-sm backdrop-blur-[1px] sm:p-5">
                <div
                    class="absolute inset-0"
                    style="background: linear-gradient(to bottom right, var(--company-color-soft), transparent, transparent);"
                />

                <div class="relative flex items-start justify-between gap-4">
                    <Heading
                        title="Editar registro"
                        description="Atualize um registro manual de ponto."
                        :icon="Clock3"
                    />

                    <Link
                        href="/worktime/clock-records"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border bg-background px-4 py-2 text-sm font-medium transition hover:bg-muted"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        <span>Voltar</span>
                    </Link>
                </div>
            </div>

            <form class="space-y-8 rounded-2xl border bg-card/50 p-5 shadow-sm sm:p-6" @submit.prevent="submit">
                <section class="space-y-4">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="employee_id">Funcionário</Label>
                            <select
                                id="employee_id"
                                v-model="form.employee_id"
                                class="flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm"
                            >
                                <option value="">Selecione</option>
                                <option v-for="employee in employees" :key="employee.id" :value="employee.id">
                                    {{ employee.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.employee_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="date">Data</Label>
                            <Input id="date" v-model="form.date" type="date" />
                            <InputError :message="form.errors.date" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="time">Hora</Label>
                            <Input id="time" v-model="form.time" type="time" />
                            <InputError :message="form.errors.time" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="notes">Observações</Label>
                            <Input id="notes" v-model="form.notes" type="text" />
                            <InputError :message="form.errors.notes" />
                        </div>
                    </div>
                </section>

                <div class="flex flex-col-reverse gap-3 border-t pt-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Can permission="worktime.deleteClockRecord">
                            <Button
                                type="button"
                                variant="destructive"
                                :disabled="form.processing"
                                class="sm:min-w-[140px]"
                                @click="destroy"
                            >
                                Excluir
                            </Button>
                        </Can>
                    </div>

                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center">
                        <Link
                            href="/worktime/clock-records"
                            class="inline-flex items-center justify-center rounded-lg border px-4 py-2 text-sm font-medium transition hover:bg-muted"
                        >
                            Cancelar
                        </Link>

                        <Can permission="worktime.updateClockRecord">
                            <Button :disabled="form.processing" class="sm:min-w-[140px]">
                                {{ form.processing ? 'Salvando...' : 'Salvar' }}
                            </Button>
                        </Can>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
