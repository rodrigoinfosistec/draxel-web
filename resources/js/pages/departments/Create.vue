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
import { ArrowLeft, Building2 } from 'lucide-vue-next'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Departamentos', href: '/departments' },
    { title: 'Novo departamento', href: '/departments/create' },
]

const form = useForm({
    name: '',
    slug: '',
    description: '',
})

function submit() {
    form.post('/departments')
}
</script>

<template>
    <Head title="Novo departamento" />

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
                        title="Novo departamento"
                        description="Cadastre um novo departamento."
                        :icon="Building2"
                    />

                    <Link
                        href="/departments"
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
                        <h2 class="text-sm font-semibold tracking-tight">Dados principais</h2>
                        <p class="text-sm text-muted-foreground">
                            Preencha os dados básicos do departamento.
                        </p>
                    </div>

                    <div class="grid gap-4">
                        <div class="grid gap-2">
                            <Label for="name">Nome</Label>
                            <Input id="name" v-model="form.name" />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="slug">Slug</Label>
                            <Input id="slug" v-model="form.slug" />
                            <InputError :message="form.errors.slug" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="description">Descrição</Label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="4"
                                class="flex w-full rounded-md border bg-background px-3 py-2 text-sm"
                            />
                            <InputError :message="form.errors.description" />
                        </div>
                    </div>
                </section>

                <div class="flex flex-col-reverse gap-3 border-t pt-6 sm:flex-row sm:items-center sm:justify-end">
                    <Link
                        href="/departments"
                        class="inline-flex items-center justify-center rounded-lg border px-4 py-2 text-sm font-medium transition hover:bg-muted"
                    >
                        Cancelar
                    </Link>

                    <Can permission="departments.create">
                        <Button :disabled="form.processing" class="sm:min-w-[140px]">
                            {{ form.processing ? 'Salvando...' : 'Salvar' }}
                        </Button>
                    </Can>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
