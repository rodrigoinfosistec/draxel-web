<script setup lang="ts">
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft, FileClock } from 'lucide-vue-next'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Ponto', href: '/worktime' },
    { title: 'Fechamentos', href: '/worktime/hour-bank-snapshots' },
    { title: 'Novo fechamento', href: '/worktime/hour-bank-snapshots/create' },
]

const form = useForm({
    name: '',
    period_start: '',
    period_end: '',
    notes: '',
})

function submit() {
    form.post('/worktime/hour-bank-snapshots')
}
</script>

<template>
    <Head title="Novo fechamento" />

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
                        title="Novo fechamento"
                        description="Crie um novo fechamento para revisar, ajustar e consolidar o banco de horas."
                        :icon="FileClock"
                    />

                    <Link
                        href="/worktime/hour-bank-snapshots"
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
                        <h2 class="text-sm font-semibold tracking-tight">Dados do fechamento</h2>
                        <p class="text-sm text-muted-foreground">
                            Informe o nome e o período que será usado como base para o snapshot.
                        </p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="grid gap-2 md:col-span-2">
                            <Label for="name">Nome</Label>
                            <input
                                id="name"
                                v-model="form.name"
                                type="text"
                                class="flex h-10 w-full rounded-md border bg-card px-3 py-2 text-sm"
                                placeholder="Ex.: Fechamento março/2026"
                            >
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="period_start">Data inicial</Label>
                            <input
                                id="period_start"
                                v-model="form.period_start"
                                type="date"
                                class="flex h-10 w-full rounded-md border bg-card px-3 py-2 text-sm"
                            >
                            <InputError :message="form.errors.period_start" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="period_end">Data final</Label>
                            <input
                                id="period_end"
                                v-model="form.period_end"
                                type="date"
                                class="flex h-10 w-full rounded-md border bg-card px-3 py-2 text-sm"
                            >
                            <InputError :message="form.errors.period_end" />
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <Label for="notes">Observações</Label>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="4"
                                class="w-full rounded-md border bg-card px-3 py-2 text-sm"
                                placeholder="Observações internas do fechamento"
                            />
                            <InputError :message="form.errors.notes" />
                        </div>
                    </div>
                </section>

                <div class="flex flex-col-reverse gap-3 border-t pt-6 sm:flex-row sm:items-center sm:justify-end">
                    <Link
                        href="/worktime/hour-bank-snapshots"
                        class="inline-flex items-center justify-center rounded-lg border px-4 py-2 text-sm font-medium transition hover:bg-muted"
                    >
                        Cancelar
                    </Link>

                    <Button :disabled="form.processing" class="sm:min-w-[160px]">
                        {{ form.processing ? 'Criando...' : 'Criar fechamento' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
