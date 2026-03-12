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
import { ShieldCheck } from 'lucide-vue-next'

type PermissionItem = {
    id: number
    name: string
    slug: string
}

defineProps<{
    permissions: Record<string, PermissionItem[]>
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Funções de usuário', href: '/roles' },
    { title: 'Nova função', href: '/roles/create' },
]

const form = useForm({
    slug: '',
    name: '',
    description: '',
    permission_ids: [] as number[],
    is_active: true,
})

function submit() {
    form.post('/roles')
}
</script>

<template>
    <Head title="Nova função" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <Heading
                title="Nova função"
                description="Cadastre uma nova função."
                :icon="ShieldCheck"
            />

            <form
                class="space-y-8 rounded-2xl border bg-card p-5 shadow-sm sm:p-6"
                @submit.prevent="submit"
            >
                <section class="space-y-4">
                    <div>
                        <h2 class="text-sm font-semibold tracking-tight">Dados principais</h2>
                        <p class="text-sm text-muted-foreground">
                            Defina os dados básicos da função.
                        </p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
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

                        <div class="grid gap-2 md:col-span-2">
                            <Label for="description">Descrição</Label>
                            <Input id="description" v-model="form.description" />
                            <InputError :message="form.errors.description" />
                        </div>
                    </div>
                </section>

                <section class="space-y-4">
                    <div>
                        <h2 class="text-sm font-semibold tracking-tight">Permissões</h2>
                        <p class="text-sm text-muted-foreground">
                            Apenas permissões de módulos ativos do tenant estão disponíveis.
                        </p>
                    </div>

                    <div class="grid gap-4 xl:grid-cols-2">
                        <div
                            v-for="(group, moduleName) in permissions"
                            :key="moduleName"
                            class="grid gap-2"
                        >
                            <Label>{{ moduleName }}</Label>

                            <div class="rounded-xl border bg-background p-4">
                                <div class="space-y-3">
                                    <label
                                        v-for="permission in group"
                                        :key="permission.id"
                                        class="flex items-center gap-3 rounded-md px-2 py-1.5 text-sm transition hover:bg-muted/60"
                                    >
                                        <input
                                            v-model="form.permission_ids"
                                            :value="permission.id"
                                            type="checkbox"
                                            class="h-4 w-4 rounded border-border"
                                        />
                                        <span class="leading-none">{{ permission.name }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <InputError :message="form.errors.permission_ids" />
                </section>

                <section class="space-y-4">
                    <div>
                        <h2 class="text-sm font-semibold tracking-tight">Status</h2>
                        <p class="text-sm text-muted-foreground">
                            Controle se a função permanece ativa.
                        </p>
                    </div>

                    <div class="rounded-xl border bg-background p-4">
                        <label
                            class="flex cursor-pointer items-center justify-between gap-4 rounded-lg border bg-card px-4 py-3 transition hover:bg-muted/40"
                        >
                            <div class="space-y-1">
                                <div class="text-sm font-medium">Função ativa</div>
                                <div class="text-xs text-muted-foreground">
                                    {{
                                        form.is_active
                                            ? 'A função poderá ser usada normalmente.'
                                            : 'A função ficará inativa no sistema.'
                                    }}
                                </div>
                            </div>

                            <div class="relative inline-flex items-center">
                                <input
                                    v-model="form.is_active"
                                    type="checkbox"
                                    class="peer sr-only"
                                />
                                <div class="h-6 w-11 rounded-full bg-muted transition peer-checked:bg-green-600" />
                                <div class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5" />
                            </div>
                        </label>
                    </div>
                </section>

                <div class="flex flex-col-reverse gap-3 border-t pt-6 sm:flex-row sm:items-center sm:justify-end">
                    <Link
                        href="/roles"
                        class="inline-flex items-center justify-center rounded-lg border px-4 py-2 text-sm font-medium transition hover:bg-muted"
                    >
                        Cancelar
                    </Link>

                    <Can permission="roles.create">
                        <Button :disabled="form.processing" class="sm:min-w-[140px]">
                            {{ form.processing ? 'Salvando...' : 'Salvar' }}
                        </Button>
                    </Can>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
