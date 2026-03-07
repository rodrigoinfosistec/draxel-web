<script setup lang="ts">
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
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
</script>

<template>
    <Head title="Editar usuário" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <Heading
                title="Editar usuário"
                description="Atualize os dados do usuário."
            />

            <form class="space-y-6 rounded-xl border bg-card p-6 shadow-sm" @submit.prevent="submit">
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

                <div class="grid gap-6 md:grid-cols-3">
                    <div class="grid gap-2">
                        <Label>Empresas</Label>
                        <div class="space-y-2 rounded-lg border p-3">
                            <label
                                v-for="company in companies"
                                :key="company.id"
                                class="flex items-center gap-2 text-sm"
                            >
                                <input
                                    v-model="form.company_ids"
                                    :value="company.id"
                                    type="checkbox"
                                />
                                {{ company.name }}
                            </label>
                        </div>
                        <InputError :message="form.errors.company_ids" />
                    </div>

                    <div class="grid gap-2">
                        <Label>Funções</Label>
                        <div class="space-y-2 rounded-lg border p-3">
                            <label
                                v-for="role in roles"
                                :key="role.id"
                                class="flex items-center gap-2 text-sm"
                            >
                                <input
                                    v-model="form.role_ids"
                                    :value="role.id"
                                    type="checkbox"
                                />
                                {{ role.name }}
                            </label>
                        </div>
                        <InputError :message="form.errors.role_ids" />
                    </div>

                    <div class="grid gap-2">
                        <Label>Módulos</Label>
                        <div class="space-y-2 rounded-lg border p-3">
                            <label
                                v-for="module in modules"
                                :key="module.id"
                                class="flex items-center gap-2 text-sm"
                            >
                                <input
                                    v-model="form.module_ids"
                                    :value="module.id"
                                    type="checkbox"
                                />
                                {{ module.name }}
                            </label>
                        </div>
                        <InputError :message="form.errors.module_ids" />
                    </div>
                </div>

                <div class="flex gap-6">
                    <label class="flex items-center gap-2 text-sm">
                        <input v-model="form.is_active" type="checkbox" />
                        Ativo
                    </label>
                </div>

                <div class="flex items-center gap-4">
                    <Button :disabled="form.processing">
                        Salvar
                    </Button>

                    <Link href="/users" class="text-sm underline">
                        Cancelar
                    </Link>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
