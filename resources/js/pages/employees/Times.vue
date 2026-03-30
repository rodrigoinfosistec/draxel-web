<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft, Clock3 } from 'lucide-vue-next'
import { computed } from 'vue'

type EmployeeItem = {
    id: number
    name: string
    cpf: string
    registration: string
}

type TimeItem = {
    weekday: string
    weekday_label: string
    start_time: string | null
    end_time: string | null
    break_duration: string | null
}

const props = defineProps<{
    employee: EmployeeItem
    times: TimeItem[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Funcionários',
        href: '/employees',
    },
    {
        title: 'Horários',
        href: `/employees/${props.employee.id}/times`,
    },
]

const weekdayOrder: Record<string, number> = {
    sunday: 0,
    monday: 1,
    tuesday: 2,
    wednesday: 3,
    thursday: 4,
    friday: 5,
    saturday: 6,
}

const sortedTimes = [...props.times].sort((a, b) => {
    const orderA = weekdayOrder[a.weekday] ?? 99
    const orderB = weekdayOrder[b.weekday] ?? 99

    return orderA - orderB
})

const form = useForm({
    times: sortedTimes.map((time) => ({
        weekday: time.weekday,
        weekday_label: time.weekday_label,
        start_time: time.start_time,
        end_time: time.end_time,
        break_duration: time.break_duration,
    })),
})

const weeklyWorkloadLabel = computed(() => {
    const totalMinutes = form.times.reduce((total, time) => {
        if (!time.start_time || !time.end_time) {
            return total
        }

        const startMinutes = timeToMinutes(time.start_time)
        const endMinutes = timeToMinutes(time.end_time)
        const breakMinutes = time.break_duration ? timeToMinutes(time.break_duration) : 0

        if (endMinutes <= startMinutes) {
            return total
        }

        return total + Math.max(0, (endMinutes - startMinutes) - breakMinutes)
    }, 0)

    return formatMinutesToHuman(totalMinutes)
})

function submit() {
    form.patch(`/employees/${props.employee.id}/times`)
}

function setDayOff(index: number) {
    form.times[index].start_time = null
    form.times[index].end_time = null
    form.times[index].break_duration = null
}

function applyStandardDay(index: number) {
    if (form.times[index].weekday === 'saturday') {
        form.times[index].start_time = '08:00'
        form.times[index].end_time = '17:00'
        form.times[index].break_duration = '00:00'
        return
    }

    if (form.times[index].weekday === 'sunday') {
        form.times[index].start_time = null
        form.times[index].end_time = null
        form.times[index].break_duration = null
        return
    }

    form.times[index].start_time = '08:00'
    form.times[index].end_time = '17:00'
    form.times[index].break_duration = '01:00'
}

function timeToMinutes(time: string): number {
    const [hours = '0', minutes = '0'] = time.split(':')

    return (Number(hours) * 60) + Number(minutes)
}

function formatMinutesToHuman(minutes: number): string {
    const hours = Math.floor(minutes / 60)
    const remainingMinutes = minutes % 60

    if (remainingMinutes === 0) {
        return `${hours}h`
    }

    return `${hours}h${String(remainingMinutes).padStart(2, '0')}min`
}
</script>

<template>
    <Head :title="`Horários - ${employee.name}`" />

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
                        title="Horários do funcionário"
                        :description="`Gerencie os horários de ${employee.name}.`"
                        :icon="Clock3"
                    />

                    <Link
                        href="/employees"
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
                        <h2 class="text-sm font-semibold tracking-tight">
                            {{ employee.name }}
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            Ajuste os horários individuais do funcionário.
                        </p>
                    </div>

                    <div class="rounded-xl border bg-background p-4">
                        <div class="text-sm text-muted-foreground">Carga horária semanal definida</div>
                        <div class="mt-1 text-lg font-semibold text-foreground">
                            {{ weeklyWorkloadLabel }}
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div
                            v-for="(time, index) in form.times"
                            :key="time.weekday"
                            class="rounded-xl border bg-background p-4"
                        >
                            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <h3 class="font-medium">{{ time.weekday_label }}</h3>
                                    <p class="text-sm text-muted-foreground">
                                        Configure início, fim e intervalo do dia.
                                    </p>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        @click="applyStandardDay(index)"
                                    >
                                        Aplicar padrão
                                    </Button>

                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        @click="setDayOff(index)"
                                    >
                                        Marcar folga
                                    </Button>
                                </div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-3">
                                <div class="grid gap-2">
                                    <Label :for="`start_time_${time.weekday}`">Início</Label>
                                    <input
                                        :id="`start_time_${time.weekday}`"
                                        v-model="form.times[index].start_time"
                                        type="time"
                                        class="flex h-10 w-full rounded-md border bg-card px-3 py-2 text-sm"
                                    >
                                </div>

                                <div class="grid gap-2">
                                    <Label :for="`end_time_${time.weekday}`">Fim</Label>
                                    <input
                                        :id="`end_time_${time.weekday}`"
                                        v-model="form.times[index].end_time"
                                        type="time"
                                        class="flex h-10 w-full rounded-md border bg-card px-3 py-2 text-sm"
                                    >
                                </div>

                                <div class="grid gap-2">
                                    <Label :for="`break_duration_${time.weekday}`">Intervalo</Label>
                                    <input
                                        :id="`break_duration_${time.weekday}`"
                                        v-model="form.times[index].break_duration"
                                        type="time"
                                        class="flex h-10 w-full rounded-md border bg-card px-3 py-2 text-sm"
                                    >
                                </div>
                            </div>
                        </div>
                    </div>

                    <InputError :message="form.errors.times" />
                </section>

                <div class="flex flex-col-reverse gap-3 border-t pt-6 sm:flex-row sm:items-center sm:justify-end">
                    <Link
                        href="/employees"
                        class="inline-flex items-center justify-center rounded-lg border px-4 py-2 text-sm font-medium transition hover:bg-muted"
                    >
                        Cancelar
                    </Link>

                    <Can permission="employees.update">
                        <Button :disabled="form.processing" class="sm:min-w-[160px]">
                            {{ form.processing ? 'Salvando...' : 'Salvar horários' }}
                        </Button>
                    </Can>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
