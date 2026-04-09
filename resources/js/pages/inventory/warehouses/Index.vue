<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, router } from '@inertiajs/vue3'
import { Building2, Download, FileText, Pencil, Plus, Search } from 'lucide-vue-next'
import { ref } from 'vue'

type WarehouseItem = {
    id: number
    name: string
    code: string | null
    description: string | null
    is_active: boolean
}

type PaginationLink = {
    url: string | null
    label: string
    active: boolean
}

const props = defineProps<{
    warehouses: {
        data: WarehouseItem[]
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
        title: 'Estoque',
        href: '/inventory',
    },
    {
        title: 'Depósitos',
        href: '/inventory/warehouses',
    },
]

const search = ref(props.filters.search ?? '')

function submitSearch() {
    router.get(
        '/inventory/warehouses',
        {
            search: search.value || undefined,
        },
        {
            preserveState: true,
            replace: true,
        },
    )
}
</script>

<template>
    <Head title="Depósitos" />

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
                        title="Depósitos"
                        description="Gerencie os depósitos da empresa em contexto."
                        :icon="Building2"
                    />

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Can permission="inventory.exportWarehouse">
                            <Button as-child variant="outline" class="w-full sm:w-auto">
                                <a :href="`/inventory/warehouses/export/csv?search=${encodeURIComponent(search)}`">
                                    <Download class="mr-2 h-4 w-4" />
                                    Exportar CSV
                                </a>
                            </Button>
                        </Can>

                        <Can permission="inventory.exportWarehouse">
                            <Button as-child variant="outline" class="w-full sm:w-auto">
                                <a :href="`/inventory/warehouses/export/pdf?search=${encodeURIComponent(search)}`">
                                    <FileText class="mr-2 h-4 w-4" />
                                    Exportar PDF
                                </a>
                            </Button>
                        </Can>

                        <Can permission="inventory.createWarehouse">
                            <Link href="/inventory/warehouses/create" class="w-full sm:w-auto">
                                <Button class="w-full sm:w-auto">
                                    <Plus class="mr-2 h-4 w-4" />
                                    Novo depósito
                                </Button>
                            </Link>
                        </Can>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border bg-card/50 p-4 shadow-sm">
                <form class="flex flex-col gap-3 sm:flex-row" @submit.prevent="submitSearch">
                    <div class="relative flex-1">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Buscar por nome, código ou descrição"
                            class="flex h-10 w-full rounded-md border bg-card py-2 pl-9 pr-3 text-sm"
                        >
                    </div>

                    <Button type="submit" variant="outline" class="w-full sm:w-auto">
                        Filtrar
                    </Button>
                </form>
            </div>

            <div class="rounded-xl border bg-card/50 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-[980px] w-full text-sm">
                        <thead class="bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left">Nome</th>
                                <th class="px-4 py-3 text-left">Código</th>
                                <th class="px-4 py-3 text-left">Descrição</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-right">Ações</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="warehouse in warehouses.data"
                                :key="warehouse.id"
                                class="border-t"
                            >
                                <td class="px-4 py-3">{{ warehouse.name || '—' }}</td>
                                <td class="px-4 py-3">{{ warehouse.code || '—' }}</td>
                                <td class="px-4 py-3">{{ warehouse.description || '—' }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex rounded-md px-2 py-1 text-xs whitespace-nowrap"
                                        :class="
                                            warehouse.is_active
                                                ? 'bg-green-100 text-green-700'
                                                : 'bg-red-100 text-red-700'
                                        "
                                    >
                                        {{ warehouse.is_active ? 'Ativo' : 'Inativo' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <Can permission="inventory.updateWarehouse">
                                            <Link
                                                :href="`/inventory/warehouses/${warehouse.id}/edit`"
                                                class="inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-medium transition hover:bg-muted"
                                            >
                                                <Pencil class="h-4 w-4" />
                                                Editar
                                            </Link>
                                        </Can>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="warehouses.data.length === 0">
                                <td colspan="5" class="px-4 py-8 text-center text-muted-foreground">
                                    Nenhum depósito encontrado.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <Link
                    v-for="link in warehouses.links"
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
