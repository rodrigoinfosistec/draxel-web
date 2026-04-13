<script setup lang="ts">
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft, ReceiptText, Save, Trash2 } from 'lucide-vue-next'

type Supplier = {
    id: number
    name: string
}

type Warehouse = {
    id: number
    name: string
}

type Product = {
    id: number
    name: string
}

type PurchaseReceiptItem = {
    product_id: number
    quantity: number | string
    unit_cost: number | string
    notes: string | null
}

type PurchaseReceipt = {
    id: number
    supplier_id: number
    warehouse_id: number
    number: string | null
    invoice_number: string | null
    issue_date: string | null
    receipt_date: string | null
    notes: string | null
    items: PurchaseReceiptItem[]
}

const props = defineProps<{
    purchaseReceipt: PurchaseReceipt
    suppliers: Supplier[]
    warehouses: Warehouse[]
    products: Product[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Recebimento de compras', href: '/purchase-receipts' },
    { title: 'Recebimentos', href: '/purchase-receipts/receipts' },
    { title: `Editar #${props.purchaseReceipt.id}`, href: `/purchase-receipts/receipts/${props.purchaseReceipt.id}/edit` },
]

const form = useForm({
    supplier_id: props.purchaseReceipt.supplier_id as number | '',
    warehouse_id: props.purchaseReceipt.warehouse_id as number | '',
    number: props.purchaseReceipt.number ?? '',
    invoice_number: props.purchaseReceipt.invoice_number ?? '',
    issue_date: props.purchaseReceipt.issue_date ?? '',
    receipt_date: props.purchaseReceipt.receipt_date ?? '',
    notes: props.purchaseReceipt.notes ?? '',
    items: props.purchaseReceipt.items.map((item) => ({
        product_id: item.product_id as number | '',
        quantity: Number(item.quantity),
        unit_cost: Number(item.unit_cost),
        notes: item.notes ?? '',
    })),
})

function addItem() {
    form.items.push({
        product_id: '',
        quantity: 1,
        unit_cost: 0,
        notes: '',
    })
}

function removeItem(index: number) {
    if (form.items.length === 1) {
        return
    }

    form.items.splice(index, 1)
}

function submit() {
    form.put(`/purchase-receipts/receipts/${props.purchaseReceipt.id}`)
}

function subtotal(item: { quantity: number; unit_cost: number }) {
    return Number(item.quantity || 0) * Number(item.unit_cost || 0)
}

function totalAmount() {
    return form.items.reduce((sum, item) => sum + subtotal(item), 0)
}

function formatCurrency(value: number) {
    return value.toLocaleString('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    })
}
</script>

<template>
    <Head :title="`Editar recebimento #${purchaseReceipt.id}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <div class="relative overflow-hidden rounded-2xl border bg-card/40 p-4 shadow-sm backdrop-blur-[1px] sm:p-5">
                <div
                    class="absolute inset-0"
                    style="background: linear-gradient(to bottom right, var(--company-color-soft), transparent, transparent);"
                />

                <div class="relative flex items-start justify-between gap-4">
                    <Heading
                        :title="`Editar recebimento #${purchaseReceipt.id}`"
                        description="Atualize os dados do recebimento enquanto ele ainda estiver em digitação."
                        :icon="ReceiptText"
                    />

                    <Link
                        :href="`/purchase-receipts/receipts/${purchaseReceipt.id}`"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border bg-background px-4 py-2 text-sm font-medium transition hover:bg-muted"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        <span>Voltar</span>
                    </Link>
                </div>
            </div>

            <form class="space-y-8 rounded-2xl border bg-card/50 p-5 shadow-sm sm:p-6" @submit.prevent="submit">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <div class="grid gap-2">
                        <Label for="supplier_id">Fornecedor</Label>
                        <select
                            id="supplier_id"
                            v-model="form.supplier_id"
                            class="flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm"
                        >
                            <option value="">Selecione</option>
                            <option v-for="supplier in suppliers" :key="supplier.id" :value="supplier.id">
                                {{ supplier.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.supplier_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="warehouse_id">Depósito</Label>
                        <select
                            id="warehouse_id"
                            v-model="form.warehouse_id"
                            class="flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm"
                        >
                            <option value="">Selecione</option>
                            <option v-for="warehouse in warehouses" :key="warehouse.id" :value="warehouse.id">
                                {{ warehouse.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.warehouse_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="number">Número</Label>
                        <input
                            id="number"
                            v-model="form.number"
                            type="text"
                            class="flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm"
                        >
                        <InputError :message="form.errors.number" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="invoice_number">Nota fiscal</Label>
                        <input
                            id="invoice_number"
                            v-model="form.invoice_number"
                            type="text"
                            class="flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm"
                        >
                        <InputError :message="form.errors.invoice_number" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="issue_date">Data de emissão</Label>
                        <input
                            id="issue_date"
                            v-model="form.issue_date"
                            type="date"
                            class="flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm"
                        >
                        <InputError :message="form.errors.issue_date" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="receipt_date">Data de recebimento</Label>
                        <input
                            id="receipt_date"
                            v-model="form.receipt_date"
                            type="date"
                            class="flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm"
                        >
                        <InputError :message="form.errors.receipt_date" />
                    </div>

                    <div class="grid gap-2 md:col-span-2 xl:col-span-3">
                        <Label for="notes">Observações</Label>
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            class="min-h-[100px] w-full rounded-md border bg-background px-3 py-2 text-sm"
                        />
                        <InputError :message="form.errors.notes" />
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-semibold">Itens do recebimento</h2>

                        <Button type="button" variant="outline" @click="addItem">
                            Adicionar item
                        </Button>
                    </div>

                    <InputError :message="form.errors.items" />

                    <div
                        v-for="(item, index) in form.items"
                        :key="index"
                        class="rounded-xl border bg-background/50 p-4"
                    >
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-12">
                            <div class="grid gap-2 xl:col-span-4">
                                <Label :for="`product_${index}`">Produto</Label>
                                <select
                                    :id="`product_${index}`"
                                    v-model="item.product_id"
                                    class="flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm"
                                >
                                    <option value="">Selecione</option>
                                    <option v-for="product in products" :key="product.id" :value="product.id">
                                        {{ product.name }}
                                    </option>
                                </select>
                                <InputError :message="form.errors[`items.${index}.product_id`]" />
                            </div>

                            <div class="grid gap-2 xl:col-span-2">
                                <Label :for="`quantity_${index}`">Quantidade</Label>
                                <input
                                    :id="`quantity_${index}`"
                                    v-model="item.quantity"
                                    type="number"
                                    min="0.001"
                                    step="0.001"
                                    class="flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm"
                                >
                                <InputError :message="form.errors[`items.${index}.quantity`]" />
                            </div>

                            <div class="grid gap-2 xl:col-span-2">
                                <Label :for="`unit_cost_${index}`">Custo unitário</Label>
                                <input
                                    :id="`unit_cost_${index}`"
                                    v-model="item.unit_cost"
                                    type="number"
                                    min="0"
                                    step="0.01"
                                    class="flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm"
                                >
                                <InputError :message="form.errors[`items.${index}.unit_cost`]" />
                            </div>

                            <div class="grid gap-2 xl:col-span-3">
                                <Label :for="`notes_item_${index}`">Observações</Label>
                                <input
                                    :id="`notes_item_${index}`"
                                    v-model="item.notes"
                                    type="text"
                                    class="flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm"
                                >
                                <InputError :message="form.errors[`items.${index}.notes`]" />
                            </div>

                            <div class="flex items-end xl:col-span-1">
                                <Button type="button" variant="outline" class="w-full" @click="removeItem(index)">
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>

                        <div class="mt-3 text-right text-sm text-muted-foreground">
                            Subtotal:
                            <strong class="text-foreground">{{ formatCurrency(subtotal(item)) }}</strong>
                        </div>
                    </div>
                </div>

                <div class="rounded-xl border bg-background/50 p-4">
                    <div class="flex items-center justify-between text-sm">
                        <span class="text-muted-foreground">Valor total</span>
                        <strong>{{ formatCurrency(totalAmount()) }}</strong>
                    </div>
                </div>

                <div class="flex flex-col-reverse gap-3 border-t pt-6 sm:flex-row sm:items-center sm:justify-end">
                    <Link
                        :href="`/purchase-receipts/receipts/${purchaseReceipt.id}`"
                        class="inline-flex items-center justify-center rounded-lg border px-4 py-2 text-sm font-medium transition hover:bg-muted"
                    >
                        Cancelar
                    </Link>

                    <Button :disabled="form.processing" class="sm:min-w-[180px]">
                        <Save class="mr-2 h-4 w-4" />
                        {{ form.processing ? 'Salvando...' : 'Salvar alterações' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
