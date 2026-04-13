<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import AppLayout from '@/layouts/AppLayout.vue'
import { dashboard } from '@/routes'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, router } from '@inertiajs/vue3'
import { ArrowLeft, FileText, Pencil, Truck, XCircle } from 'lucide-vue-next'

type PurchaseReceiptItem = {
    id: number
    product_name: string | null
    quantity: number | string
    unit_cost: number | string
    subtotal: number | string
    notes: string | null
}

type PurchaseReceipt = {
    id: number
    number: string | null
    invoice_number: string | null
    supplier_name: string | null
    warehouse_name: string | null
    issue_date_label: string | null
    receipt_date_label: string | null
    status: string
    status_label: string
    total_amount: number | string
    notes: string | null
    items: PurchaseReceiptItem[]
}

const props = defineProps<{
    purchaseReceipt: PurchaseReceipt
}>()

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
    {
        title: 'Recebimento de compras',
        href: '/purchase-receipts',
    },
    {
        title: 'Recebimentos',
        href: '/purchase-receipts/receipts',
    },
    {
        title: `Recebimento #${props.purchaseReceipt.id}`,
        href: `/purchase-receipts/receipts/${props.purchaseReceipt.id}`,
    },
]

function postReceipt() {
    router.post(`/purchase-receipts/receipts/${props.purchaseReceipt.id}/post`)
}

function cancelReceipt() {
    router.post(`/purchase-receipts/receipts/${props.purchaseReceipt.id}/cancel`)
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
    <Head :title="`Recebimento #${purchaseReceipt.id}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <div class="relative overflow-hidden rounded-2xl border bg-card/40 p-4 shadow-sm backdrop-blur-[1px] sm:p-5">
                <div
                    class="absolute inset-0"
                    style="background: linear-gradient(to bottom right, var(--company-color-soft), transparent, transparent);"
                />

                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        :title="`Recebimento #${purchaseReceipt.id}`"
                        description="Visualize os dados completos do recebimento de compra."
                        :icon="FileText"
                    />

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Link
                            href="/purchase-receipts/receipts"
                            class="inline-flex items-center justify-center gap-2 rounded-lg border bg-background px-4 py-2 text-sm font-medium transition hover:bg-muted"
                        >
                            <ArrowLeft class="h-4 w-4" />
                            <span>Voltar</span>
                        </Link>

                        <Can permission="purchaseReceipt.updatePurchaseReceipt">
                            <Link
                                v-if="purchaseReceipt.status === 'draft'"
                                :href="`/purchase-receipts/receipts/${purchaseReceipt.id}/edit`"
                                class="inline-flex items-center justify-center gap-2 rounded-lg border bg-background px-4 py-2 text-sm font-medium transition hover:bg-muted"
                            >
                                <Pencil class="h-4 w-4" />
                                <span>Editar</span>
                            </Link>
                        </Can>

                        <Can permission="purchaseReceipt.postPurchaseReceipt">
                            <Button
                                v-if="purchaseReceipt.status === 'draft'"
                                class="w-full sm:w-auto"
                                @click="postReceipt"
                            >
                                <Truck class="mr-2 h-4 w-4" />
                                Lançar
                            </Button>
                        </Can>

                        <Can permission="purchaseReceipt.cancelPurchaseReceipt">
                            <Button
                                v-if="purchaseReceipt.status !== 'canceled'"
                                variant="outline"
                                class="w-full sm:w-auto"
                                @click="cancelReceipt"
                            >
                                <XCircle class="mr-2 h-4 w-4" />
                                Cancelar
                            </Button>
                        </Can>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-[360px_1fr]">
                <div class="rounded-2xl border bg-card/50 p-5 shadow-sm">
                    <div class="mb-4">
                        <span
                            class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium"
                            :class="statusBadgeClass(purchaseReceipt.status)"
                        >
                            {{ purchaseReceipt.status_label }}
                        </span>
                    </div>

                    <div class="space-y-4 text-sm">
                        <div>
                            <div class="text-muted-foreground">Número</div>
                            <div class="font-medium">{{ purchaseReceipt.number || '—' }}</div>
                        </div>

                        <div>
                            <div class="text-muted-foreground">Nota fiscal</div>
                            <div class="font-medium">{{ purchaseReceipt.invoice_number || '—' }}</div>
                        </div>

                        <div>
                            <div class="text-muted-foreground">Fornecedor</div>
                            <div class="font-medium">{{ purchaseReceipt.supplier_name || '—' }}</div>
                        </div>

                        <div>
                            <div class="text-muted-foreground">Depósito</div>
                            <div class="font-medium">{{ purchaseReceipt.warehouse_name || '—' }}</div>
                        </div>

                        <div>
                            <div class="text-muted-foreground">Data de emissão</div>
                            <div class="font-medium">{{ purchaseReceipt.issue_date_label || '—' }}</div>
                        </div>

                        <div>
                            <div class="text-muted-foreground">Data de recebimento</div>
                            <div class="font-medium">{{ purchaseReceipt.receipt_date_label || '—' }}</div>
                        </div>

                        <div>
                            <div class="text-muted-foreground">Valor total</div>
                            <div class="font-semibold">{{ formatCurrency(purchaseReceipt.total_amount) }}</div>
                        </div>

                        <div>
                            <div class="text-muted-foreground">Observações</div>
                            <div class="font-medium whitespace-pre-line">{{ purchaseReceipt.notes || '—' }}</div>
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden rounded-xl border bg-card/50 shadow-sm">
                    <div class="border-b px-4 py-3">
                        <div class="font-semibold">Itens do recebimento</div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-[760px] w-full text-sm">
                            <thead class="bg-muted/50">
                                <tr>
                                    <th class="px-4 py-3 text-left">Produto</th>
                                    <th class="px-4 py-3 text-right">Quantidade</th>
                                    <th class="px-4 py-3 text-right">Custo unitário</th>
                                    <th class="px-4 py-3 text-right">Subtotal</th>
                                    <th class="px-4 py-3 text-left">Observações</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr
                                    v-for="item in purchaseReceipt.items"
                                    :key="item.id"
                                    class="border-t"
                                >
                                    <td class="px-4 py-3">{{ item.product_name || '—' }}</td>
                                    <td class="px-4 py-3 text-right">{{ item.quantity }}</td>
                                    <td class="px-4 py-3 text-right">{{ formatCurrency(item.unit_cost) }}</td>
                                    <td class="px-4 py-3 text-right">{{ formatCurrency(item.subtotal) }}</td>
                                    <td class="px-4 py-3">{{ item.notes || '—' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        v-if="purchaseReceipt.items.length === 0"
                        class="px-4 py-8 text-center text-muted-foreground"
                    >
                        Nenhum item encontrado.
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
