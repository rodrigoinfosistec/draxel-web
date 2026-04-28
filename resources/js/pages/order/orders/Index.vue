<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, router } from '@inertiajs/vue3'
import { Download, Eye, FileText, Pencil, Plus, Search, ShoppingCart } from 'lucide-vue-next'
import { computed, ref } from 'vue'

type OrderItem = {
    id: number
    number: string
    issued_at: string | null
    warehouse_name: string | null
    client_name: string | null
    client_document: string | null
    status: string
    status_label: string | null
    type_label: string | null
    items_count: number
    products_total: number
    destination_name: string | null
}

type PaginationLink = {
    url: string | null
    label: string
    active: boolean
}

type Option = {
    id?: number
    name?: string
    document?: string | null
    value?: string
    label?: string
}

const props = defineProps<{
    orders: {
        data: OrderItem[]
        links: PaginationLink[]
    }
    filters: {
        warehouse_id: string
        client_id: string
        start_date: string
        end_date: string
        status: string
        type: string
        search: string
    }
    warehouses: Option[]
    clients: Option[]
    types: Option[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Pedidos',
        href: '/order',
    },
    {
        title: 'Lista de pedidos',
        href: '/order/orders',
    },
]

const warehouseId = ref(props.filters.warehouse_id ?? '')
const clientId = ref(props.filters.client_id ?? '')
const startDate = ref(props.filters.start_date ?? '')
const endDate = ref(props.filters.end_date ?? '')
const status = ref(props.filters.status ?? '')
const type = ref(props.filters.type ?? '')
const search = ref(props.filters.search ?? '')

const queryParams = computed(() => ({
    warehouse_id: warehouseId.value || undefined,
    client_id: clientId.value || undefined,
    start_date: startDate.value || undefined,
    end_date: endDate.value || undefined,
    status: status.value || undefined,
    type: type.value || undefined,
    search: search.value || undefined,
}))

function submitSearch() {
    router.get('/order/orders', queryParams.value, {
        preserveState: true,
        replace: true,
    })
}

function exportCsv() {
    const params = new URLSearchParams()

    Object.entries(queryParams.value).forEach(([key, value]) => {
        if (value) {
            params.set(key, String(value))
        }
    })

    window.open(`/order/orders/export/csv?${params.toString()}`, '_blank')
}

function exportPdf() {
    const params = new URLSearchParams()

    Object.entries(queryParams.value).forEach(([key, value]) => {
        if (value) {
            params.set(key, String(value))
        }
    })

    window.open(`/order/orders/export/pdf?${params.toString()}`, '_blank')
}

function statusClass(statusValue: string) {
    if (statusValue === 'confirmed') {
        return 'bg-green-100 text-green-700'
    }

    if (statusValue === 'cancelled') {
        return 'bg-red-100 text-red-700'
    }

    return 'bg-amber-100 text-amber-700'
}

function clientLabel(client: Option): string {
    return client.document ? `${client.name} — ${client.document}` : String(client.name)
}
</script>

<template>
    <Head title="Lista de pedidos" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <div class="relative overflow-hidden rounded-2xl border bg-card/40 p-4 shadow-sm backdrop-blur-[1px] sm:p-5">
                <div
                    class="absolute inset-0"
                    style="background: linear-gradient(to bottom right, var(--company-color-soft), transparent, transparent);"
                />

                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Lista de pedidos"
                        description="Gerencie os pedidos da empresa em contexto."
                        :icon="ShoppingCart"
                    />

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Can permission="order.exportOrder">
                            <Button variant="outline" class="w-full sm:w-auto" @click="exportCsv">
                                <Download class="mr-2 h-4 w-4" />
                                CSV
                            </Button>
                        </Can>

                        <Can permission="order.exportOrder">
                            <Button variant="outline" class="w-full sm:w-auto" @click="exportPdf">
                                <FileText class="mr-2 h-4 w-4" />
                                PDF
                            </Button>
                        </Can>

                        <Can permission="order.createOrder">
                            <Link href="/order/orders/create" class="w-full sm:w-auto">
                                <Button class="w-full sm:w-auto">
                                    <Plus class="mr-2 h-4 w-4" />
                                    Novo pedido
                                </Button>
                            </Link>
                        </Can>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border bg-card/50 p-4 shadow-sm">
                <form class="grid gap-3 lg:grid-cols-7" @submit.prevent="submitSearch">
                    <select
                        v-model="warehouseId"
                        class="flex h-10 w-full rounded-md border bg-card px-3 py-2 text-sm"
                    >
                        <option value="">Todos os depósitos</option>
                        <option
                            v-for="warehouse in warehouses"
                            :key="warehouse.id"
                            :value="warehouse.id"
                        >
                            {{ warehouse.name }}
                        </option>
                    </select>

                    <select
                        v-model="clientId"
                        class="flex h-10 w-full rounded-md border bg-card px-3 py-2 text-sm"
                    >
                        <option value="">Todos os clientes</option>
                        <option
                            v-for="client in clients"
                            :key="client.id"
                            :value="client.id"
                        >
                            {{ clientLabel(client) }}
                        </option>
                    </select>

                    <select
                        v-model="status"
                        class="flex h-10 w-full rounded-md border bg-card px-3 py-2 text-sm"
                    >
                        <option value="">Todos os status</option>
                        <option value="draft">Rascunho</option>
                        <option value="confirmed">Confirmado</option>
                        <option value="cancelled">Cancelado</option>
                    </select>

                    <select
                        v-model="type"
                        class="flex h-10 w-full rounded-md border bg-card px-3 py-2 text-sm"
                    >
                        <option value="">Todos os tipos</option>
                        <option
                            v-for="item in types"
                            :key="item.value"
                            :value="item.value"
                        >
                            {{ item.label }}
                        </option>
                    </select>

                    <input
                        v-model="startDate"
                        type="date"
                        class="flex h-10 w-full rounded-md border bg-card px-3 py-2 text-sm"
                    >

                    <input
                        v-model="endDate"
                        type="date"
                        class="flex h-10 w-full rounded-md border bg-card px-3 py-2 text-sm"
                    >

                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Buscar"
                            class="flex h-10 w-full rounded-md border bg-card py-2 pl-9 pr-3 text-sm"
                        >
                    </div>

                    <div class="lg:col-span-7">
                        <Button type="submit" variant="outline" class="w-full sm:w-auto">
                            Filtrar
                        </Button>
                    </div>
                </form>
            </div>

            <div class="rounded-xl border bg-card/50 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-295 w-full text-sm">
                        <thead class="bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left">Número</th>
                                <th class="px-4 py-3 text-left">Data</th>
                                <th class="px-4 py-3 text-left">Cliente</th>
                                <th class="px-4 py-3 text-left">Depósito</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-left">Tipo</th>
                                <th class="px-4 py-3 text-left">Itens</th>
                                <th class="px-4 py-3 text-left">Quantidade total</th>
                                <th class="px-4 py-3 text-right">Ações</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="order in orders.data"
                                :key="order.id"
                                class="border-t"
                            >
                                <td class="px-4 py-3">{{ order.number }}</td>
                                <td class="px-4 py-3">{{ order.issued_at || '—' }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-medium">{{ order.client_name || '—' }}</div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ order.client_document || 'Sem documento' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">{{ order.warehouse_name || '—' }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex rounded-md px-2 py-1 text-xs whitespace-nowrap"
                                        :class="statusClass(order.status)"
                                    >
                                        {{ order.status_label || '—' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">{{ order.type_label || '—' }}</td>
                                <td class="px-4 py-3">{{ order.items_count }}</td>
                                <td class="px-4 py-3">{{ order.products_total }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <Can permission="order.viewOrder">
                                            <Link
                                                :href="`/order/orders/${order.id}`"
                                                class="inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-medium transition hover:bg-muted"
                                            >
                                                <Eye class="h-4 w-4" />
                                                Visualizar
                                            </Link>
                                        </Can>

                                        <Can permission="order.updateOrder">
                                            <Link
                                                v-if="order.status === 'draft'"
                                                :href="`/order/orders/${order.id}/edit`"
                                                class="inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-medium transition hover:bg-muted"
                                            >
                                                <Pencil class="h-4 w-4" />
                                                Editar
                                            </Link>
                                        </Can>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="orders.data.length === 0">
                                <td colspan="9" class="px-4 py-8 text-center text-muted-foreground">
                                    Nenhum pedido encontrado.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <Link
                    v-for="link in orders.links"
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
