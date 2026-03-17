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
import { ArrowLeft, CalendarClock } from 'lucide-vue-next'
import { computed, watch } from 'vue'

type Option = {
    id: number
    name: string
}

type EventTypeOption = {
    value: string
    label: string
    time_mode: string
}

const props = defineProps<{
    employees: Option[]
    dayEventTypes: EventTypeOption[]
    partialEventTypes: EventTypeOption[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Ponto',
        href: '/worktime/employee-events',
    },
    {
        title: 'Novo evento',
        href: '/worktime/employee-events/create',
    },
]

const form = useForm({
    employee_id: '' as number | '',
    is_partial: false,
    event_type: '',
    date: '',
    starts_at: '',
    ends_at: '',
    notes: '',
})

const eventTypes = computed(() =>
    form.is_partial ? props.partialEventTypes : props.dayEventTypes,
)

watch(
    () => form.is_partial,
    () => {
        form.event_type = ''
        form.starts_at = ''
        form.ends_at = ''
    },
)

function submit() {
    form.post('/worktime/employee-events')
}
</script>

<template>
    <Head title="Novo evento" />

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
                        title="Novo evento"
                        description="Cadastre um novo evento."
                        :icon="CalendarClock"
                    />

                    <Link
                        href="/worktime/employee-events"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border bg-background px-4 py-2 text-sm font-medium transition hover:bg-muted"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        <span>Voltar</span>
                    </Link>
                </div>
            </div>

            <form
                class="space-y-8 rounded-2xl border bg-card/50 p-5 shadow-sm sm:p-6"
                @submit.prevent="submit"
            >
                <section class="space-y-4">
                    <div>
                        <h2 class="text-sm font-semibold tracking-tight">Dados do evento</h2>
                        <p class="text-sm text-muted-foreground">
                            Preencha as informações do evento do funcionário.
                        </p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="employee_id">Funcionário</Label>
                            <select
                                id="employee_id"
                                v-model="form.employee_id"
                                class="flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm"
                            >
                                <option value="">Selecione</option>
                                <option
                                    v-for="employee in employees"
                                    :key="employee.id"
                                    :value="employee.id"
                                >
                                    {{ employee.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.employee_id" />
                        </div>

                        <div class="flex items-end">
                            <label
                                class="flex w-full cursor-pointer items-center justify-between gap-4 rounded-xl border bg-background px-4 py-3"
                            >
                                <div class="space-y-1">
                                    <div class="text-sm font-medium">Evento parcial</div>
                                    <div class="text-xs text-muted-foreground">
                                        Marque para lançar um evento com horário inicial e final.
                                    </div>
                                </div>

                                <div class="relative inline-flex items-center">
                                    <input
                                        v-model="form.is_partial"
                                        type="checkbox"
                                        class="peer sr-only"
                                    />

                                    <div
                                        class="h-6 w-11 rounded-full bg-muted transition peer-checked:bg-green-600"
                                    />

                                    <div
                                        class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"
                                    />
                                </div>
                            </label>
                            <InputError :message="form.errors.is_partial" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="event_type">Tipo</Label>
                            <select
                                id="event_type"
                                v-model="form.event_type"
                                class="flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm"
                            >
                                <option value="">Selecione</option>
                                <option
                                    v-for="eventType in eventTypes"
                                    :key="eventType.value"
                                    :value="eventType.value"
                                >
                                    {{ eventType.label }}
                                </option>
                            </select>
                            <InputError :message="form.errors.event_type" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="date">Data</Label>
                            <Input
                                id="date"
                                v-model="form.date"
                                type="date"
                            />
                            <InputError :message="form.errors.date" />
                        </div>

                        <div v-if="form.is_partial" class="grid gap-2">
                            <Label for="starts_at">Hora inicial</Label>
                            <Input
                                id="starts_at"
                                v-model="form.starts_at"
                                type="time"
                            />
                            <InputError :message="form.errors.starts_at" />
                        </div>

                        <div v-if="form.is_partial" class="grid gap-2">
                            <Label for="ends_at">Hora final</Label>
                            <Input
                                id="ends_at"
                                v-model="form.ends_at"
                                type="time"
                            />
                            <InputError :message="form.errors.ends_at" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="notes">Observações</Label>
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            rows="4"
                            class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                        />
                        <InputError :message="form.errors.notes" />
                    </div>
                </section>

                <div class="flex flex-col-reverse gap-3 border-t pt-6 sm:flex-row sm:items-center sm:justify-end">
                    <Link
                        href="/worktime/employee-events"
                        class="inline-flex items-center justify-center rounded-lg border px-4 py-2 text-sm font-medium transition hover:bg-muted"
                    >
                        Cancelar
                    </Link>

                    <Can permission="worktime.create">
                        <Button :disabled="form.processing" class="sm:min-w-[140px]">
                            {{ form.processing ? 'Salvando...' : 'Salvar' }}
                        </Button>
                    </Can>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
