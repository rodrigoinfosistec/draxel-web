<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, router } from '@inertiajs/vue3'
import { Download, FileText, Pencil, UsersRound } from 'lucide-vue-next'
import { computed, ref } from 'vue'

type ClientItem = {
    id: number
    name: string
    document: string | null
    email: string | null
    phone: string | null
    address: string | null
    notes: string | null
    created_at: string | null
}

type PaginationLink = {
    url: string | null
    label: string
    active: boolean
}

const props = defineProps<{
    clients: {
        data: ClientItem[]
        links: PaginationLink[]
    }
    filters: {
        search: string
    }
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Clientes', href: '/clients' },
]

const search = ref(props.filters.search ?? '')

function submitSearch() {
    router.get(
        '/clients',
        { search: search.value || undefined },
        {
            preserveState: true,
            replace: true,
        },
    )
}

const exportParams = computed(() => {
    const params = new URLSearchParams()

    if (search.value) params.set('search', search.value)

    const query = params.toString()

    return {
        csv: query ? `/clients/export/csv?${query}` : '/clients/export/csv',
        pdf: query ? `/clients/export/pdf?${query}` : '/clients/export/pdf',
    }
})
</script>

<template>
    <Head title="Clientes" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <div
                class="relative overflow-hidden rounded-2xl border bg-card/40 p-4 shadow-sm backdrop-blur-[1px] sm:p-5"
            >
                <div
                    class="absolute inset-0"
                    style="background: linear-gradient(to bottom right, var(--company-color-soft), transparent, transparent);"
                />

                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Clientes"
                        description="Gerencie os clientes do grupo."
                        :icon="UsersRound"
                    />

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Can permission="clients.viewAny">
                            <Button as-child variant="outline" class="w-full sm:w-auto">
                                <a :href="exportParams.csv">
                                    <Download class="mr-2 h-4 w-4" />
                                    Exportar CSV
                                </a>
                            </Button>
                        </Can>

                        <Can permission="clients.viewAny">
                            <Button as-child variant="outline" class="w-full sm:w-auto">
                                <a :href="exportParams.pdf">
                                    <FileText class="mr-2 h-4 w-4" />
                                    Exportar PDF
                                </a>
                            </Button>
                        </Can>

                        <Can permission="clients.create">
                            <Link href="/clients/create" class="w-full sm:w-auto">
                                <Button class="w-full sm:w-auto">Novo cliente</Button>
                            </Link>
                        </Can>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border bg-card/50 p-4 shadow-sm">
                <form class="flex flex-col gap-3 sm:flex-row" @submit.prevent="submitSearch">
                    <Input
                        v-model="search"
                        type="text"
                        placeholder="Buscar por nome, documento, e-mail, telefone, endereço ou observações"
                        class="w-full"
                    />
                    <Button type="submit" variant="outline" class="w-full sm:w-auto">
                        Buscar
                    </Button>
                </form>
            </div>

            <div class="rounded-xl border bg-card/50 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-245 w-full text-sm">
                        <thead class="bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Nome</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Documento</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">E-mail</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Telefone</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Criado em</th>
                                <th class="px-4 py-3 text-right whitespace-nowrap">Ações</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="client in clients.data"
                                :key="client.id"
                                class="border-t"
                            >
                                <td class="px-4 py-3 align-top">
                                    <div class="font-medium">{{ client.name }}</div>
                                    <div class="text-sm text-muted-foreground">
                                        {{ client.address || '—' }}
                                    </div>
                                </td>

                                <td class="px-4 py-3 align-top">
                                    {{ client.document || '—' }}
                                </td>

                                <td class="px-4 py-3 align-top">
                                    {{ client.email || '—' }}
                                </td>

                                <td class="px-4 py-3 align-top">
                                    {{ client.phone || '—' }}
                                </td>

                                <td class="px-4 py-3 align-top">
                                    {{ client.created_at || '—' }}
                                </td>

                                <td class="px-4 py-3 text-right align-top">
                                    <Can permission="clients.update">
                                        <Link
                                            :href="`/clients/${client.id}/edit`"
                                            class="inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-medium text-foreground transition hover:bg-muted"
                                        >
                                            <Pencil class="h-4 w-4" />
                                            <span class="hidden sm:inline">Editar</span>
                                        </Link>
                                    </Can>
                                </td>
                            </tr>

                            <tr v-if="clients.data.length === 0">
                                <td colspan="6" class="px-4 py-8 text-center text-muted-foreground">
                                    Nenhum cliente encontrado.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <Link
                    v-for="link in clients.links"
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
