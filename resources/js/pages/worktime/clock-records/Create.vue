<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft, Clock3, Plus, Trash2 } from 'lucide-vue-next'

type Option = {
    id: number
    name: string
}

defineProps<{
    employees: Option[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Ponto', href: '/worktime' },
    { title: 'Registros de ponto', href: '/worktime/clock-records' },
    { title: 'Novo registro manual', href: '/worktime/clock-records/create' },
]

const form = useForm({
    employee_id: '' as number | '',
    date: '',
    rows: [
        {
            time: '',
            notes: '',
        },
    ],
})

function addRow() {
    form.rows.push({
        time: '',
        notes: '',
    })
}

function removeRow(index: number) {
    if (form.rows.length === 1) {
        return
    }

    form.rows.splice(index, 1)
}

function submit() {
    form.post('/worktime/clock-records')
}
</script>

<template>
    <Head title="Novo registro manual" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <div class="relative overflow-hidden rounded-2xl border bg-card/40 p-4 shadow-sm backdrop-blur-[1px] sm:p-5">
                <div
                    class="absolute inset-0"
                    style="background: linear-gradient(to bottom right, var(--company-color-soft), transparent, transparent);"
                />

                <div class="relative flex items-start justify-between gap-4">
                    <Heading
                        title="Novo registro manual"
                        description="Cadastre um ou mais registros do mesmo funcionário no mesmo dia."
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
                    </div>
                </section>

                <section class="space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-sm font-semibold tracking-tight">Horários</h2>
                            <p class="text-sm text-muted-foreground">
                                Adicione quantos registros forem necessários para o mesmo dia.
                            </p>
                        </div>

                        <Button type="button" variant="outline" @click="addRow">
                            <Plus class="mr-2 h-4 w-4" />
                            Adicionar horário
                        </Button>
                    </div>

                    <div class="space-y-4">
                        <div
                            v-for="(row, index) in form.rows"
                            :key="index"
                            class="rounded-xl border bg-background p-4"
                        >
                            <div class="mb-4 flex items-center justify-between gap-4">
                                <div class="text-sm font-medium">Registro {{ index + 1 }}</div>

                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    :disabled="form.rows.length === 1"
                                    @click="removeRow(index)"
                                >
                                    <Trash2 class="mr-2 h-4 w-4" />
                                    Remover
                                </Button>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label :for="`time_${index}`">Hora</Label>
                                    <Input
                                        :id="`time_${index}`"
                                        v-model="form.rows[index].time"
                                        type="time"
                                    />
                                    <InputError :message="form.errors[`rows.${index}.time`]" />
                                </div>

                                <div class="grid gap-2">
                                    <Label :for="`notes_${index}`">Observações</Label>
                                    <Input
                                        :id="`notes_${index}`"
                                        v-model="form.rows[index].notes"
                                        type="text"
                                    />
                                    <InputError :message="form.errors[`rows.${index}.notes`]" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <InputError :message="form.errors.rows" />
                </section>

                <div class="flex flex-col-reverse gap-3 border-t pt-6 sm:flex-row sm:items-center sm:justify-end">
                    <Link
                        href="/worktime/clock-records"
                        class="inline-flex items-center justify-center rounded-lg border px-4 py-2 text-sm font-medium transition hover:bg-muted"
                    >
                        Cancelar
                    </Link>

                    <Can permission="worktime.createClockRecord">
                        <Button :disabled="form.processing" class="sm:min-w-[170px]">
                            {{ form.processing ? 'Salvando...' : 'Salvar registros' }}
                        </Button>
                    </Can>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
