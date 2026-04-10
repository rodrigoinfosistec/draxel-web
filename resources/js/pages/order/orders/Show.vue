<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import { useConfirm } from '@/composables/useConfirm'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, router } from '@inertiajs/vue3'
import { ArrowLeft, CheckCircle2, Pencil, ShoppingCart, XCircle } from 'lucide-vue-next'

const props = defineProps<{
    order: {
        id: number
        number: string
        issued_at: string | null
        warehouse_id: number | null
        warehouse_name: string | null
        status: string
        status_label: string
        type_label: string | null
        destination_name: string | null
        notes: string | null
        created_by: string | null
        confirmed_by: string | null
        confirmed_at: string | null
        cancelled_by: string | null
        cancelled_at: string | null
        items: {
            id: number
            product_id: number
            product_name: string | null
            quantity: number
            notes: string | null
        }[]
    }
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Pedidos', href: '/order' },
    { title: 'Lista de pedidos', href: '/order/orders' },
    { title: props.order.number, href: `/order/orders/${props.order.id}` },
]

function statusClass(status: string) {
    if (status === 'confirmed') {
        return 'bg-green-100 text-green-700'
    }

    if (status === 'cancelled') {
        return 'bg-red-100 text-red-700'
    }

    return 'bg-amber-100 text-amber-700'
}

async function confirmOrder() {
    const confirmed = await useConfirm({
        title: 'Confirmar pedido?',
        text: 'Essa ação refletirá a saída dos produtos no estoque do depósito informado.',
        confirmButtonText: 'Sim, confirmar',
        cancelButtonText: 'Cancelar',
        icon: 'question',
    })

    if (!confirmed) {
        return
    }

    router.post(`/order/orders/${props.order.id}/confirm`)
}

async function cancelOrder() {
    const confirmed = await useConfirm({
        title: 'Cancelar pedido?',
        text: 'Essa ação cancelará o pedido em rascunho.',
        confirmButtonText: 'Sim, cancelar',
        cancelButtonText: 'Voltar',
        icon: 'warning',
    })

    if (!confirmed) {
        return
    }

    router.post(`/order/orders/${props.order.id}/cancel`)
}
</script>

<template>
    <Head :title="order.number" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <div class="relative overflow-hidden rounded-2xl border bg-card/40 p-4 shadow-sm backdrop-blur-[1px] sm:p-5">
                <div
                    class="absolute inset-0"
                    style="background: linear-gradient(to bottom right, var(--company-color-soft), transparent, transparent);"
                />

                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        :title="order.number"
                        description="Visualize os dados completos do pedido."
                        :icon="ShoppingCart"
                    />

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Link
                            href="/order/orders"
                            class="inline-flex items-center justify-center gap-2 rounded-lg border bg-background px-4 py-2 text-sm font-medium transition hover:bg-muted"
                        >
                            <ArrowLeft class="h-4 w-4" />
                            Voltar
                        </Link>

                        <Can permission="order.updateOrder">
                            <Link
                                v-if="order.status === 'draft'"
                                :href="`/order/orders/${order.id}/edit`"
                                class="w-full sm:w-auto"
                            >
                                <Button variant="outline" class="w-full sm:w-auto">
                                    <Pencil class="mr-2 h-4 w-4" />
                                    Editar
                                </Button>
                            </Link>
                        </Can>

                        <Can permission="order.confirmOrder">
                            <Button
                                v-if="order.status === 'draft'"
                                class="w-full sm:w-auto"
                                @click="confirmOrder"
                            >
                                <CheckCircle2 class="mr-2 h-4 w-4" />
                                Confirmar
                            </Button>
                        </Can>

                        <Can permission="order.cancelOrder">
                            <Button
                                v-if="order.status === 'draft'"
                                variant="outline"
                                class="w-full sm:w-auto"
                                @click="cancelOrder"
                            >
                                <XCircle class="mr-2 h-4 w-4" />
                                Cancelar
                            </Button>
                        </Can>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border bg-card/50 p-5 shadow-sm">
                    <div class="text-sm text-muted-foreground">Número</div>
                    <div class="mt-2 text-2xl font-semibold tracking-tight">
                        {{ order.number }}
                    </div>
                </div>

                <div class="rounded-2xl border bg-card/50 p-5 shadow-sm">
                    <div class="text-sm text-muted-foreground">Data</div>
                    <div class="mt-2 text-2xl font-semibold tracking-tight">
                        {{ order.issued_at || '—' }}
                    </div>
                </div>

                <div class="rounded-2xl border bg-card/50 p-5 shadow-sm">
                    <div class="text-sm text-muted-foreground">Depósito</div>
                    <div class="mt-2 text-2xl font-semibold tracking-tight">
                        {{ order.warehouse_name || '—' }}
                    </div>
                </div>

                <div class="rounded-2xl border bg-card/50 p-5 shadow-sm">
                    <div class="text-sm text-muted-foreground">Status</div>
                    <div class="mt-3">
                        <span
                            class="inline-flex rounded-md px-2 py-1 text-xs whitespace-nowrap"
                            :class="statusClass(order.status)"
                        >
                            {{ order.status_label }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-[1.7fr_1fr]">
                <div class="rounded-xl border bg-card/50 shadow-sm">
                    <div class="border-b px-4 py-3">
                        <h2 class="text-sm font-semibold tracking-tight">Itens do pedido</h2>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-[900px] w-full text-sm">
                            <thead class="bg-muted/50">
                                <tr>
                                    <th class="px-4 py-3 text-left">Produto</th>
                                    <th class="px-4 py-3 text-left">Quantidade</th>
                                    <th class="px-4 py-3 text-left">Observação</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr
                                    v-for="item in order.items"
                                    :key="item.id"
                                    class="border-t"
                                >
                                    <td class="px-4 py-3">{{ item.product_name || '—' }}</td>
                                    <td class="px-4 py-3">{{ item.quantity }}</td>
                                    <td class="px-4 py-3">{{ item.notes || '—' }}</td>
                                </tr>

                                <tr v-if="order.items.length === 0">
                                    <td colspan="3" class="px-4 py-8 text-center text-muted-foreground">
                                        Nenhum item encontrado.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-xl border bg-card/50 p-5 shadow-sm">
                        <h2 class="text-sm font-semibold tracking-tight">Dados do pedido</h2>

                        <div class="mt-4 space-y-3 text-sm">
                            <div class="flex justify-between gap-4">
                                <span class="text-muted-foreground">Tipo</span>
                                <span class="font-medium">{{ order.type_label || '—' }}</span>
                            </div>

                            <div class="flex justify-between gap-4">
                                <span class="text-muted-foreground">Destino</span>
                                <span class="font-medium text-right">{{ order.destination_name || '—' }}</span>
                            </div>

                            <div class="flex justify-between gap-4">
                                <span class="text-muted-foreground">Criado por</span>
                                <span class="font-medium">{{ order.created_by || '—' }}</span>
                            </div>

                            <div class="flex justify-between gap-4">
                                <span class="text-muted-foreground">Confirmado por</span>
                                <span class="font-medium">{{ order.confirmed_by || '—' }}</span>
                            </div>

                            <div class="flex justify-between gap-4">
                                <span class="text-muted-foreground">Confirmado em</span>
                                <span class="font-medium">{{ order.confirmed_at || '—' }}</span>
                            </div>

                            <div class="flex justify-between gap-4">
                                <span class="text-muted-foreground">Cancelado por</span>
                                <span class="font-medium">{{ order.cancelled_by || '—' }}</span>
                            </div>

                            <div class="flex justify-between gap-4">
                                <span class="text-muted-foreground">Cancelado em</span>
                                <span class="font-medium">{{ order.cancelled_at || '—' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border bg-card/50 p-5 shadow-sm">
                        <h2 class="text-sm font-semibold tracking-tight">Observações</h2>

                        <p class="mt-4 text-sm text-muted-foreground">
                            {{ order.notes || 'Sem observações.' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
