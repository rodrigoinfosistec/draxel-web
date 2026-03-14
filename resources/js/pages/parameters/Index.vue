<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft, Settings2 } from 'lucide-vue-next'

type TabItem = {
    key: string
    label: string
}

type CompanyItem = {
    id: number
    name: string
}

type DefaultTimeItem = {
    weekday: string
    weekday_label: string
    start_time: string | null
    end_time: string | null
    break_duration: string | null
}

const props = defineProps<{
    company: CompanyItem
    tabs: TabItem[]
    defaultTimes: DefaultTimeItem[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Parâmetros',
        href: '/parameters',
    },
]

const activeTab = 'company-default-times'

const form = useForm({
    times: props.defaultTimes.map((time) => ({
        weekday: time.weekday,
        weekday_label: time.weekday_label,
        start_time: time.start_time,
        end_time: time.end_time,
        break_duration: time.break_duration,
    })),
})

function submitCompanyDefaultTimes() {
    form.patch('/parameters/company-default-times')
}

function setDayOff(index: number) {
    form.times[index].start_time = null
    form.times[index].end_time = null
    form.times[index].break_duration = null
}

function applyDefaultDay(index: number) {
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
</script>

<template>
    <Head title="Parâmetros" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <div class="flex items-start justify-between gap-4">
                <Heading
                    title="Parâmetros"
                    :description="`Gerencie os parâmetros da empresa ${company.name}.`"
                    :icon="Settings2"
                />

                <Link
                    href="/dashboard"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border bg-background px-4 py-2 text-sm font-medium transition hover:bg-muted"
                >
                    <ArrowLeft class="h-4 w-4" />
                    <span>Voltar</span>
                </Link>
            </div>

            <form
                class="space-y-8 rounded-2xl border bg-card p-5 shadow-sm sm:p-6"
                @submit.prevent="submitCompanyDefaultTimes"
            >
                <section class="space-y-4">
                    <div class="flex flex-wrap gap-2 border-b pb-4">
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            type="button"
                            class="inline-flex items-center rounded-lg border px-4 py-2 text-sm font-medium transition"
                            :class="
                                activeTab === tab.key
                                    ? 'border-primary bg-primary/10 text-primary'
                                    : 'border-border bg-background text-foreground hover:bg-muted'
                            "
                        >
                            {{ tab.label }}
                        </button>
                    </div>
                </section>

                <section
                    v-if="activeTab === 'company-default-times'"
                    class="space-y-6"
                >
                    <div>
                        <h2 class="text-sm font-semibold tracking-tight">Horários padrão da empresa</h2>
                        <p class="text-sm text-muted-foreground">
                            Defina os horários padrão de funcionamento da empresa em contexto.
                        </p>
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
                                        Configure início, fim e intervalo padrão do dia.
                                    </p>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        @click="applyDefaultDay(index)"
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
                        href="/dashboard"
                        class="inline-flex items-center justify-center rounded-lg border px-4 py-2 text-sm font-medium transition hover:bg-muted"
                    >
                        Cancelar
                    </Link>

                    <Can permission="parameters.update">
                        <Button :disabled="form.processing" class="sm:min-w-[160px]">
                            {{ form.processing ? 'Salvando...' : 'Salvar horários' }}
                        </Button>
                    </Can>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
