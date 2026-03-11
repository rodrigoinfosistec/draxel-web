<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { useConfirm } from '@/composables/useConfirm'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, useForm } from '@inertiajs/vue3'

type Option = {
    id: number
    name: string
}

type UserFormData = {
    id: number
    name: string
    email: string
    company_ids: number[]
    role_ids: number[]
    module_ids: number[]
    is_active: boolean
}

const props = defineProps<{
    user: UserFormData
    companies: Option[]
    roles: Option[]
    modules: Option[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Usuários',
        href: '/users',
    },
    {
        title: 'Editar usuário',
        href: `/users/${props.user.id}/edit`,
    },
]

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '',
    password_confirmation: '',
    company_ids: props.user.company_ids,
    role_ids: props.user.role_ids,
    module_ids: props.user.module_ids,
    is_active: props.user.is_active,
})

function submit() {
    form.put(`/users/${props.user.id}`)
}

async function destroy() {
    const confirmed = await useConfirm({
        title: 'Excluir usuário?',
        text: 'Essa ação removerá o usuário permanentemente.',
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar',
        icon: 'warning',
    })

    if (!confirmed) {
        return
    }

    form.delete(`/users/${props.user.id}`)
}
</script>

<template>
    <Head title="Editar usuário" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <Heading
                title="Editar usuário"
                description="Atualize os dados do usuário."
            />

            <form
                class="space-y-8 rounded-2xl border bg-card p-5 shadow-sm sm:p-6"
                @submit.prevent="submit"
            >
                <section class="space-y-4">
                    <div>
                        <h2 class="text-sm font-semibold tracking-tight">Dados principais</h2>
                        <p class="text-sm text-muted-foreground">
                            Atualize as informações básicas do usuário.
                        </p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="name">Nome</Label>
                            <Input id="name" v-model="form.name" />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="email">Email</Label>
                            <Input id="email" v-model="form.email" type="email" />
                            <InputError :message="form.errors.email" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="password">Nova senha</Label>
                            <Input id="password" v-model="form.password" type="password" />
                            <InputError :message="form.errors.password" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="password_confirmation">Confirmar nova senha</Label>
                            <Input
                                id="password_confirmation"
                                v-model="form.password_confirmation"
                                type="password"
                            />
                        </div>
                    </div>
                </section>

                <section class="space-y-4">
                    <div>
                        <h2 class="text-sm font-semibold tracking-tight">Vínculos e acessos</h2>
                        <p class="text-sm text-muted-foreground">
                            Defina empresas, funções e módulos disponíveis para este usuário.
                        </p>
                    </div>

                    <div class="grid gap-4 xl:grid-cols-3">
                        <div class="grid gap-2">
                            <Label>Empresas</Label>
                            <div class="rounded-xl border bg-background p-4">
                                <div class="space-y-3">
                                    <label
                                        v-for="company in companies"
                                        :key="company.id"
                                        class="flex items-center gap-3 rounded-md px-2 py-1.5 text-sm transition hover:bg-muted/60"
                                    >
                                        <input
                                            v-model="form.company_ids"
                                            :value="company.id"
                                            type="checkbox"
                                            class="h-4 w-4 rounded border-border"
                                        />
                                        <span class="leading-none">{{ company.name }}</span>
                                    </label>
                                </div>
                            </div>
                            <InputError :message="form.errors.company_ids" />
                        </div>

                        <div class="grid gap-2">
                            <Label>Funções</Label>
                            <div class="rounded-xl border bg-background p-4">
                                <div class="space-y-3">
                                    <label
                                        v-for="role in roles"
                                        :key="role.id"
                                        class="flex items-center gap-3 rounded-md px-2 py-1.5 text-sm transition hover:bg-muted/60"
                                    >
                                        <input
                                            v-model="form.role_ids"
                                            :value="role.id"
                                            type="checkbox"
                                            class="h-4 w-4 rounded border-border"
                                        />
                                        <span class="leading-none">{{ role.name }}</span>
                                    </label>
                                </div>
                            </div>
                            <InputError :message="form.errors.role_ids" />
                        </div>

                        <div class="grid gap-2">
                            <Label>Módulos</Label>
                            <div class="rounded-xl border bg-background p-4">
                                <div class="space-y-3">
                                    <label
                                        v-for="module in modules"
                                        :key="module.id"
                                        class="flex items-center gap-3 rounded-md px-2 py-1.5 text-sm transition hover:bg-muted/60"
                                    >
                                        <input
                                            v-model="form.module_ids"
                                            :value="module.id"
                                            type="checkbox"
                                            class="h-4 w-4 rounded border-border"
                                        />
                                        <span class="leading-none">{{ module.name }}</span>
                                    </label>
                                </div>
                            </div>
                            <InputError :message="form.errors.module_ids" />
                        </div>
                    </div>
                </section>

                <section class="space-y-4">
                    <div>
                        <h2 class="text-sm font-semibold tracking-tight">Status</h2>
                        <p class="text-sm text-muted-foreground">
                            Controle se o usuário permanece ativo no sistema.
                        </p>
                    </div>

                    <div class="rounded-xl border bg-background p-4">
                        <label
                            class="flex cursor-pointer items-center justify-between gap-4 rounded-lg border bg-card px-4 py-3 transition hover:bg-muted/40"
                        >
                            <div class="space-y-1">
                                <div class="text-sm font-medium">Usuário ativo</div>
                                <div class="text-xs text-muted-foreground">
                                    {{
                                        form.is_active
                                            ? 'O usuário poderá acessar normalmente o sistema.'
                                            : 'O usuário ficará impedido de acessar o sistema.'
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
                    </div>
                </section>

                <div
                    class="flex flex-col-reverse gap-3 border-t pt-6 lg:flex-row lg:items-center lg:justify-between"
                >
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Can permission="users.delete">
                            <Button
                                type="button"
                                variant="destructive"
                                :disabled="form.processing"
                                class="sm:min-w-[140px]"
                                @click="destroy"
                            >
                                Excluir
                            </Button>
                        </Can>
                    </div>

                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center">
                        <Link
                            href="/users"
                            class="inline-flex items-center justify-center rounded-lg border px-4 py-2 text-sm font-medium transition hover:bg-muted"
                        >
                            Cancelar
                        </Link>

                        <Can permission="users.update">
                            <Button :disabled="form.processing" class="sm:min-w-[140px]">
                                {{ form.processing ? 'Salvando...' : 'Salvar' }}
                            </Button>
                        </Can>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
