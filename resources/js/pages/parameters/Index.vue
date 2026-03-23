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
import { ref, watch } from 'vue'

type TabItem = {
    key: string
    label: string
}

type CompanyItem = {
    id: number
    name: string
    uses_hour_bank: boolean
    hour_bank_starts_at: string | null
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

const activeTab = ref('company-default-times')

const defaultTimesForm = useForm({
    times: props.defaultTimes.map((time) => ({
        weekday: time.weekday,
        weekday_label: time.weekday_label,
        start_time: time.start_time,
        end_time: time.end_time,
        break_duration: time.break_duration,
    })),
})

const hourBankForm = useForm({
    uses_hour_bank: props.company.uses_hour_bank,
    hour_bank_starts_at: props.company.hour_bank_starts_at,
})

watch(
    () => hourBankForm.uses_hour_bank,
    (value) => {
        if (!value) {
            hourBankForm.hour_bank_starts_at = null
        }
    },
)

function submitCompanyDefaultTimes() {
    defaultTimesForm.patch('/parameters/company-default-times')
}

function submitCompanyHourBank() {
    hourBankForm.patch('/parameters/company-hour-bank')
}

function setDayOff(index: number) {
    defaultTimesForm.times[index].start_time = null
    defaultTimesForm.times[index].end_time = null
    defaultTimesForm.times[index].break_duration = null
}

function applyDefaultDay(index: number) {
    if (defaultTimesForm.times[index].weekday === 'saturday') {
        defaultTimesForm.times[index].start_time = '08:00'
        defaultTimesForm.times[index].end_time = '17:00'
        defaultTimesForm.times[index].break_duration = '00:00'
        return
    }

    if (defaultTimesForm.times[index].weekday === 'sunday') {
        defaultTimesForm.times[index].start_time = null
        defaultTimesForm.times[index].end_time = null
        defaultTimesForm.times[index].break_duration = null
        return
    }

    defaultTimesForm.times[index].start_time = '08:00'
    defaultTimesForm.times[index].end_time = '17:00'
    defaultTimesForm.times[index].break_duration = '01:00'
}
</script>

<template>
    <Head title="Parâmetros" />

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
            </div>

            <div class="rounded-2xl border bg-card/50 p-5 shadow-sm sm:p-6">
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
                            @click="activeTab = tab.key"
                        >
                            {{ tab.label }}
                        </button>
                    </div>
                </section>

                <form
                    v-if="activeTab === 'company-default-times'"
                    class="mt-8 space-y-8"
                    @submit.prevent="submitCompanyDefaultTimes"
                >
                    <section class="space-y-6">
                        <div>
                            <h2 class="text-sm font-semibold tracking-tight">Horários padrão</h2>
                            <p class="text-sm text-muted-foreground">
                                Defina os horários padrão por dia da semana para a empresa em contexto.
                            </p>
                        </div>

                        <div class="space-y-4">
                            <div
                                v-for="(time, index) in defaultTimesForm.times"
                                :key="time.weekday"
                                class="rounded-xl border bg-background p-4"
                            >
                                <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                    <div>
                                        <h3 class="text-sm font-semibold">{{ time.weekday_label }}</h3>
                                        <p class="text-xs text-muted-foreground">
                                            Configure os horários padrão deste dia.
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
                                            Folga
                                        </Button>
                                    </div>
                                </div>

                                <div class="grid gap-4 md:grid-cols-3">
                                    <div class="grid gap-2">
                                        <Label :for="`start_time_${time.weekday}`">Início</Label>
                                        <input
                                            :id="`start_time_${time.weekday}`"
                                            v-model="defaultTimesForm.times[index].start_time"
                                            type="time"
                                            class="flex h-10 w-full rounded-md border bg-card px-3 py-2 text-sm"
                                        >
                                    </div>

                                    <div class="grid gap-2">
                                        <Label :for="`end_time_${time.weekday}`">Fim</Label>
                                        <input
                                            :id="`end_time_${time.weekday}`"
                                            v-model="defaultTimesForm.times[index].end_time"
                                            type="time"
                                            class="flex h-10 w-full rounded-md border bg-card px-3 py-2 text-sm"
                                        >
                                    </div>

                                    <div class="grid gap-2">
                                        <Label :for="`break_duration_${time.weekday}`">Intervalo</Label>
                                        <input
                                            :id="`break_duration_${time.weekday}`"
                                            v-model="defaultTimesForm.times[index].break_duration"
                                            type="time"
                                            class="flex h-10 w-full rounded-md border bg-card px-3 py-2 text-sm"
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>

                        <InputError :message="defaultTimesForm.errors.times" />
                    </section>

                    <div class="flex flex-col-reverse gap-3 border-t pt-6 sm:flex-row sm:items-center sm:justify-end">
                        <Link
                            href="/dashboard"
                            class="inline-flex items-center justify-center rounded-lg border px-4 py-2 text-sm font-medium transition hover:bg-muted"
                        >
                            Cancelar
                        </Link>

                        <Can permission="parameters.update">
                            <Button :disabled="defaultTimesForm.processing" class="sm:min-w-[160px]">
                                {{ defaultTimesForm.processing ? 'Salvando...' : 'Salvar horários' }}
                            </Button>
                        </Can>
                    </div>
                </form>

                <form
                    v-if="activeTab === 'company-hour-bank'"
                    class="mt-8 space-y-8"
                    @submit.prevent="submitCompanyHourBank"
                >
                    <section class="space-y-6">
                        <div>
                            <h2 class="text-sm font-semibold tracking-tight">Banco de horas</h2>
                            <p class="text-sm text-muted-foreground">
                                Defina se a empresa em contexto utiliza banco de horas e a data inicial de contagem.
                            </p>
                        </div>

                        <div class="rounded-xl border bg-background p-4">
                            <label
                                class="flex cursor-pointer items-center justify-between gap-4 rounded-lg border bg-card px-4 py-3 transition hover:bg-muted/40"
                            >
                                <div class="space-y-1">
                                    <div class="text-sm font-medium">Usa banco de horas</div>
                                    <div class="text-xs text-muted-foreground">
                                        {{
                                            hourBankForm.uses_hour_bank
                                                ? 'A empresa está configurada para utilizar banco de horas.'
                                                : 'A empresa não utiliza banco de horas.'
                                        }}
                                    </div>
                                </div>

                                <div class="relative inline-flex items-center">
                                    <input
                                        v-model="hourBankForm.uses_hour_bank"
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
                        </div>

                        <InputError :message="hourBankForm.errors.uses_hour_bank" />

                        <div
                            v-if="hourBankForm.uses_hour_bank"
                            class="grid gap-2 md:max-w-sm"
                        >
                            <Label for="hour_bank_starts_at">Data inicial</Label>
                            <input
                                id="hour_bank_starts_at"
                                v-model="hourBankForm.hour_bank_starts_at"
                                type="date"
                                class="flex h-10 w-full rounded-md border bg-card px-3 py-2 text-sm"
                            >
                            <InputError :message="hourBankForm.errors.hour_bank_starts_at" />
                        </div>
                    </section>

                    <div class="flex flex-col-reverse gap-3 border-t pt-6 sm:flex-row sm:items-center sm:justify-end">
                        <Link
                            href="/dashboard"
                            class="inline-flex items-center justify-center rounded-lg border px-4 py-2 text-sm font-medium transition hover:bg-muted"
                        >
                            Cancelar
                        </Link>

                        <Can permission="parameters.update">
                            <Button :disabled="hourBankForm.processing" class="sm:min-w-[180px]">
                                {{ hourBankForm.processing ? 'Salvando...' : 'Salvar configurações' }}
                            </Button>
                        </Can>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
