<script setup lang="ts">
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft, FolderTree } from 'lucide-vue-next'

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Categorias de produto', href: '/product-categories' },
    { title: 'Nova categoria', href: '/product-categories/create' },
]

const form = useForm({
    name: '',
    description: '',
    is_active: true,
})

function submit() {
    form.post('/product-categories')
}
</script>

<template>
    <Head title="Nova categoria de produto" />

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
                        title="Nova categoria de produto"
                        description="Cadastre uma nova categoria compartilhada."
                        :icon="FolderTree"
                    />

                    <Link
                        href="/product-categories"
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
                            Preencha os dados básicos da categoria.
                        </p>
                    </div>

                    <div class="grid gap-4">
                        <div class="grid gap-2">
                            <Label for="name">Nome</Label>
                            <Input id="name" v-model="form.name" />
                            <InputError :message="form.errors.name" />
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

                <section class="space-y-4">
                    <div>
                        <h2 class="text-sm font-semibold tracking-tight">Status</h2>
                        <p class="text-sm text-muted-foreground">
                            Controle se a categoria permanece ativa.
                        </p>
                    </div>

                    <div class="rounded-xl border bg-background p-4">
                        <label
                            class="flex cursor-pointer items-center justify-between gap-4 rounded-lg border bg-card px-4 py-3 transition hover:bg-muted/40"
                        >
                            <div class="space-y-1">
                                <div class="text-sm font-medium">Categoria ativa</div>
                                <div class="text-xs text-muted-foreground">
                                    {{
                                        form.is_active
                                            ? 'A categoria ficará disponível normalmente no sistema.'
                                            : 'A categoria ficará inativa no sistema.'
                                    }}
                                </div>
                            </div>

                            <div class="relative inline-flex items-center">
                                <input
                                    v-model="form.is_active"
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

                        <InputError :message="form.errors.is_active" class="mt-2" />
                    </div>
                </section>

                <div class="flex flex-col-reverse gap-3 border-t pt-6 sm:flex-row sm:justify-end">
                    <Link href="/product-categories" class="w-full sm:w-auto">
                        <Button type="button" variant="outline" class="w-full sm:w-auto">
                            Cancelar
                        </Button>
                    </Link>

                    <Button
                        type="submit"
                        class="w-full sm:w-auto"
                        :disabled="form.processing"
                    >
                        Salvar categoria
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
