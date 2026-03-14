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
import { ArrowLeft, Headset } from 'lucide-vue-next'

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Chamados',
        href: '/support-tickets',
    },
    {
        title: 'Novo chamado',
        href: '/support-tickets/create',
    },
]

const form = useForm({
    subject: '',
    description: '',
})

function submit() {
    form.post('/support-tickets')
}
</script>

<template>
    <Head title="Novo chamado" />

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
                        title="Novo chamado"
                        description="Abra um novo chamado de suporte."
                        :icon="Headset"
                    />

                    <Link
                        href="/support-tickets"
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
                        <h2 class="text-sm font-semibold tracking-tight">Dados do chamado</h2>
                        <p class="text-sm text-muted-foreground">
                            Informe o assunto e descreva a solicitação com o máximo de detalhes.
                        </p>
                    </div>

                    <div class="grid gap-4">
                        <div class="grid gap-2">
                            <Label for="subject">Assunto</Label>
                            <Input id="subject" v-model="form.subject" />
                            <InputError :message="form.errors.subject" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="description">Descrição</Label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="8"
                                class="flex min-h-[180px] w-full rounded-md border bg-background px-3 py-2 text-sm outline-none ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring"
                            />
                            <InputError :message="form.errors.description" />
                        </div>
                    </div>
                </section>

                <div class="flex flex-col-reverse gap-3 border-t pt-6 sm:flex-row sm:items-center sm:justify-end">
                    <Link
                        href="/support-tickets"
                        class="inline-flex items-center justify-center rounded-lg border px-4 py-2 text-sm font-medium transition hover:bg-muted"
                    >
                        Cancelar
                    </Link>

                    <Can permission="support.create">
                        <Button :disabled="form.processing" class="sm:min-w-[140px]">
                            {{ form.processing ? 'Abrindo...' : 'Abrir chamado' }}
                        </Button>
                    </Can>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
