<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, router } from '@inertiajs/vue3'
import { ClipboardList, Download, FileText, Pencil, Plus, Truck, XCircle } from 'lucide-vue-next'
import { computed, ref } from 'vue'

type Supplier = {
    id: number
    name: string
}

type Warehouse = {
    id: number
    name: string
}

type StatusOption = {
    value: string
    label: string
}

type PurchaseReceiptItem = {
    id: number
    number: string | null
    invoice_number: string | null
    supplier_name: string | null
    warehouse_name: string | null
    receipt_date: string | null
    receipt_date_label: string | null
    status: string
    status_label: string
    total_amount: number | string
}

type PaginationLink = {
    url: string | null
    label: string
    active: boolean
}

const props = defineProps<{
    purchaseReceipts: {
        data: PurchaseReceiptItem[]
        links: PaginationLink[]
    }
    filters: {
        search: string
        status: string
        supplier_id: number | null
        warehouse_id: number | null
        start_date: string
        end_date: string
    }
    suppliers: Supplier[]
    warehouses: Warehouse[]
    statuses: StatusOption[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Recebimento de compras', href: '/purchase-receipts' },
    { title: 'Recebimentos', href: '/purchase-receipts/receipts' },
]

const search = ref(props.filters.search ?? '')
const status = ref(props.filters.status ?? '')
const supplierId = ref(props.filters.supplier_id ? String(props.filters.supplier_id) : '')
const warehouseId = ref(props.filters.warehouse_id ? String(props.filters.warehouse_id) : '')
const startDate = ref(props.filters.start_date ?? '')
const endDate = ref(props.filters.end_date ?? '')

function submitSearch() {
    router.get(
        '/purchase-receipts/receipts',
        {
            search: search.value || undefined,
            status: status.value || undefined,
            supplier_id: supplierId.value || undefined,
            warehouse_id: warehouseId.value || undefined,
            start_date: startDate.value || undefined,
            end_date: endDate.value || undefined,
        },
        {
            preserveState: true,
            replace: true,
        },
    )
}

const exportParams = computed(() => {
    const params = new URLSearchParams()

    if (search.value) params.set('search', search.value)
    if (status.value) params.set('status', status.value)
    if (supplierId.value) params.set('supplier_id', supplierId.value)
    if (warehouseId.value) params.set('warehouse_id', warehouseId.value)
    if (startDate.value) params.set('start_date', startDate.value)
    if (endDate.value) params.set('end_date', endDate.value)

    const query = params.toString()

    return {
        csv: query ? `/purchase-receipts/receipts/export/csv?${query}` : '/purchase-receipts/receipts/export/csv',
        pdf: query ? `/purchase-receipts/receipts/export/pdf?${query}` : '/purchase-receipts/receipts/export/pdf',
    }
})

function postReceipt(id: number) {
    router.post(`/purchase-receipts/receipts/${id}/post`)
}

function cancelReceipt(id: number) {
    router.post(`/purchase-receipts/receipts/${id}/cancel`)
}

function formatCurrency(value: number | string) {
    return Number(value || 0).toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    })
}

function statusBadgeClass(status: string) {
    if (status === 'received') {
        return 'bg-emerald-100 text-emerald-700 ring-1 ring-inset ring-emerald-200'
    }

    if (status === 'draft') {
        return 'bg-amber-100 text-amber-700 ring-1 ring-inset ring-amber-200'
    }

    if (status === 'canceled') {
        return 'bg-red-100 text-red-700 ring-1 ring-inset ring-red-200'
    }

    return 'bg-zinc-100 text-zinc-700 ring-1 ring-inset ring-zinc-200'
}
</script>

<template>
    <Head title="Recebimentos de compra" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <div class="relative overflow-hidden rounded-2xl border bg-card/40 p-4 shadow-sm backdrop-blur-[1px] sm:p-5">
                <div
                    class="absolute inset-0"
                    style="background: linear-gradient(to bottom right, var(--company-color-soft), transparent, transparent);"
                />

                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Recebimentos de compra"
                        description="Gerencie os recebimentos de compra, acompanhe seus status e exporte os relatórios."
                        :icon="ClipboardList"
                    />

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Can permission="purchaseReceipt.exportPurchaseReceipt">
                            <Button as-child variant="outline" class="w-full sm:w-auto">
                                <a :href="exportParams.csv">
                                    <Download class="mr-2 h-4 w-4" />
                                    Exportar CSV
                                </a>
                            </Button>
                        </Can>

                        <Can permission="purchaseReceipt.exportPurchaseReceipt">
                            <Button as-child variant="outline" class="w-full sm:w-auto">
                                <a :href="exportParams.pdf">
                                    <FileText class="mr-2 h-4 w-4" />
                                    Exportar PDF
                                </a>
                            </Button>
                        </Can>

                        <Can permission="purchaseReceipt.createPurchaseReceipt">
                            <Link href="/purchase-receipts/receipts/create" class="w-full sm:w-auto">
                                <Button class="w-full sm:w-auto">
                                    <Plus class="mr-2 h-4 w-4" />
                                    Novo recebimento
                                </Button>
                            </Link>
                        </Can>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border bg-card/50 p-4 shadow-sm">
                <form class="grid gap-3 md:grid-cols-6" @submit.prevent="submitSearch">
                    <Input
                        v-model="search"
                        type="text"
                        placeholder="Buscar por número ou nota fiscal"
                        class="md:col-span-2 w-full"
                    />

                    <select
                        v-model="status"
                        class="flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm"
                    >
                        <option value="">Todos os status</option>
                        <option
                            v-for="item in statuses"
                            :key="item.value"
                            :value="item.value"
                        >
                            {{ item.label }}
                        </option>
                    </select>

                    <select
                        v-model="supplierId"
                        class="flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm"
                    >
                        <option value="">Todos os fornecedores</option>
                        <option
                            v-for="item in suppliers"
                            :key="item.id"
                            :value="String(item.id)"
                        >
                            {{ item.name }}
                        </option>
                    </select>

                    <select
                        v-model="warehouseId"
                        class="flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm"
                    >
                        <option value="">Todos os depósitos</option>
                        <option
                            v-for="item in warehouses"
                            :key="item.id"
                            :value="String(item.id)"
                        >
                            {{ item.name }}
                        </option>
                    </select>

                    <div class="grid grid-cols-2 gap-3 md:col-span-2">
                        <Input v-model="startDate" type="date" class="w-full" />
                        <Input v-model="endDate" type="date" class="w-full" />
                    </div>

                    <Button type="submit" variant="outline" class="md:col-span-1">
                        Buscar
                    </Button>
                </form>
            </div>

            <div class="overflow-hidden rounded-xl border bg-card/50 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-[980px] w-full text-sm">
                        <thead class="bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left">Número</th>
                                <th class="px-4 py-3 text-left">NF</th>
                                <th class="px-4 py-3 text-left">Fornecedor</th>
                                <th class="px-4 py-3 text-left">Depósito</th>
                                <th class="px-4 py-3 text-left">Recebimento</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-right">Valor total</th>
                                <th class="px-4 py-3 text-right">Ações</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="receipt in purchaseReceipts.data"
                                :key="receipt.id"
                                class="border-t"
                            >
                                <td class="px-4 py-3">{{ receipt.number || '—' }}</td>
                                <td class="px-4 py-3">{{ receipt.invoice_number || '—' }}</td>
                                <td class="px-4 py-3">{{ receipt.supplier_name || '—' }}</td>
                                <td class="px-4 py-3">{{ receipt.warehouse_name || '—' }}</td>
                                <td class="px-4 py-3">{{ receipt.receipt_date_label || '—' }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                                        :class="statusBadgeClass(receipt.status)"
                                    >
                                        {{ receipt.status_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">{{ formatCurrency(receipt.total_amount) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <div class="inline-flex items-center gap-2">
                                        <Can permission="purchaseReceipt.viewPurchaseReceipt">
                                            <Link
                                                :href="`/purchase-receipts/receipts/${receipt.id}`"
                                                class="inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-medium transition hover:bg-muted"
                                            >
                                                Ver
                                            </Link>
                                        </Can>

                                        <Can permission="purchaseReceipt.updatePurchaseReceipt">
                                            <Link
                                                v-if="receipt.status === 'draft'"
                                                :href="`/purchase-receipts/receipts/${receipt.id}/edit`"
                                                class="inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-medium transition hover:bg-muted"
                                            >
                                                <Pencil class="h-4 w-4" />
                                                <span class="hidden sm:inline">Editar</span>
                                            </Link>
                                        </Can>

                                        <Can permission="purchaseReceipt.postPurchaseReceipt">
                                            <button
                                                v-if="receipt.status === 'draft'"
                                                type="button"
                                                class="inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-medium transition hover:bg-muted"
                                                @click="postReceipt(receipt.id)"
                                            >
                                                <Truck class="h-4 w-4" />
                                                <span class="hidden sm:inline">Lançar</span>
                                            </button>
                                        </Can>

                                        <Can permission="purchaseReceipt.cancelPurchaseReceipt">
                                            <button
                                                v-if="receipt.status !== 'canceled'"
                                                type="button"
                                                class="inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-medium transition hover:bg-muted"
                                                @click="cancelReceipt(receipt.id)"
                                            >
                                                <XCircle class="h-4 w-4" />
                                                <span class="hidden sm:inline">Cancelar</span>
                                            </button>
                                        </Can>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="purchaseReceipts.data.length === 0"
                    class="px-4 py-8 text-center text-muted-foreground"
                >
                    Nenhum recebimento encontrado.
                </div>

                <div class="flex flex-wrap gap-2 border-t p-4">
                    <Link
                        v-for="link in purchaseReceipts.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        class="rounded-lg border px-3 py-2 text-sm transition hover:bg-muted"
                        :class="{ 'pointer-events-none opacity-50': !link.url, 'bg-muted': link.active }"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
