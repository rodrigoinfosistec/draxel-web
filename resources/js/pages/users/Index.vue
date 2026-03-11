<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'

type Role = {
    id: number
    name: string
}

type Company = {
    id: number
    name: string
}

type UserItem = {
    id: number
    name: string
    email: string
    is_active: boolean
    default_company: string | null
    companies: Company[]
    roles: Role[]
}

type PaginationLink = {
    url: string | null
    label: string
    active: boolean
}

const props = defineProps<{
    users: {
        data: UserItem[]
        links: PaginationLink[]
    }
    filters: {
        search: string
    }
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
]

const search = ref(props.filters.search ?? '')

function submitSearch() {
    router.get(
        '/users',
        { search: search.value },
        {
            preserveState: true,
            replace: true,
        },
    )
}
</script>

<template>
    <Head title="Usuários" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <Heading
                    title="Usuários"
                    description="Gerencie os usuários."
                />

                <Can permission="users.create">
                    <Link href="/users/create" class="w-full sm:w-auto">
                        <Button class="w-full sm:w-auto">Novo usuário</Button>
                    </Link>
                </Can>
            </div>

            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <form
                    class="flex flex-col gap-3 sm:flex-row"
                    @submit.prevent="submitSearch"
                >
                    <Input
                        v-model="search"
                        type="text"
                        placeholder="Buscar por nome ou email"
                        class="w-full"
                    />

                    <Button type="submit" variant="outline" class="w-full sm:w-auto">
                        Buscar
                    </Button>
                </form>
            </div>

            <div class="rounded-xl border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-[760px] w-full text-sm">
                        <thead class="bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Nome</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Empresa padrão</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Funções</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Status</th>
                                <th class="px-4 py-3 text-right whitespace-nowrap">Ações</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="user in users.data"
                                :key="user.id"
                                class="border-t"
                            >
                                <td class="px-4 py-3 align-top">
                                    <div class="font-medium">{{ user.name }}</div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ user.email }}
                                    </div>
                                </td>

                                <td class="px-4 py-3 align-top">
                                    {{ user.default_company ?? '—' }}
                                </td>

                                <td class="px-4 py-3 align-top">
                                    <div class="flex flex-wrap gap-2">
                                        <span
                                            v-for="role in user.roles"
                                            :key="role.id"
                                            class="rounded-md border px-2 py-1 text-xs"
                                        >
                                            {{ role.name }}
                                        </span>
                                    </div>
                                </td>

                                <td class="px-4 py-3 align-top">
                                    <span
                                        class="inline-flex rounded-md px-2 py-1 text-xs whitespace-nowrap"
                                        :class="
                                            user.is_active
                                                ? 'bg-green-100 text-green-700'
                                                : 'bg-red-100 text-red-700'
                                        "
                                    >
                                        {{ user.is_active ? 'Ativo' : 'Inativo' }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 text-right align-top">
                                    <Can permission="users.update">
                                        <Link
                                            :href="`/users/${user.id}/edit`"
                                            class="text-sm font-medium underline whitespace-nowrap"
                                        >
                                            Editar
                                        </Link>
                                    </Can>
                                </td>
                            </tr>

                            <tr v-if="users.data.length === 0">
                                <td
                                    colspan="6"
                                    class="px-4 py-8 text-center text-muted-foreground"
                                >
                                    Nenhum usuário encontrado.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <Link
                    v-for="link in users.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    v-html="link.label"
                    class="rounded-md border px-3 py-2 text-sm"
                    :class="{
                        'bg-muted': link.active,
                        'pointer-events-none opacity-50': !link.url,
                    }"
                />
            </div>
        </div>
    </AppLayout>
</template>
