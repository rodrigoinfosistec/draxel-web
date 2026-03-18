<script setup lang="ts">
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft, FileClock } from 'lucide-vue-next'

type DeviceItem = {
    id: number
    name: string | null
}

defineProps<{
    devices: DeviceItem[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Ponto', href: '/worktime' },
    { title: 'Importações', href: '/worktime/clock-record-imports' },
    { title: 'Nova importação', href: '/worktime/clock-record-imports/create' },
]

const form = useForm({
    tenant_clock_device_id: '' as number | '',
    file: null as File | null,
})

function submit() {
    form.post('/worktime/clock-record-imports')
}
</script>

<template>
    <Head title="Nova importação de ponto" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <div class="relative overflow-hidden rounded-2xl border bg-card/40 p-4 shadow-sm backdrop-blur-[1px] sm:p-5">
                <div
                    class="absolute inset-0"
                    style="background: linear-gradient(to bottom right, var(--company-color-soft), transparent, transparent);"
                />

                <div class="relative flex items-start justify-between gap-4">
                    <Heading
                        title="Nova importação de ponto"
                        description="Escolha o device e envie o arquivo TXT."
                        :icon="FileClock"
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

            <form class="space-y-8 rounded-2xl border bg-card/50 p-5 shadow-sm sm:p-6" @submit.prevent="submit">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="tenant_clock_device_id">Device</Label>
                        <select
                            id="tenant_clock_device_id"
                            v-model="form.tenant_clock_device_id"
                            class="flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm"
                        >
                            <option value="">Selecione</option>
                            <option v-for="device in devices" :key="device.id" :value="device.id">
                                {{ device.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.tenant_clock_device_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="file">Arquivo TXT</Label>
                        <input
                            id="file"
                            type="file"
                            accept=".txt,text/plain"
                            class="flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm"
                            @change="form.file = ($event.target as HTMLInputElement).files?.[0] ?? null"
                        >
                        <InputError :message="form.errors.file" />
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t pt-6 sm:flex-row sm:items-center sm:justify-end">
                    <Link
                        href="/worktime/clock-record-imports"
                        class="inline-flex items-center justify-center rounded-lg border px-4 py-2 text-sm font-medium transition hover:bg-muted"
                    >
                        Cancelar
                    </Link>

                    <Button :disabled="form.processing" class="sm:min-w-[170px]">
                        {{ form.processing ? 'Enviando...' : 'Enviar arquivo' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
