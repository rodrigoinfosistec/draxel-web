<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft, Boxes } from 'lucide-vue-next'

defineProps<{
    warehouses: Array<{ id: number; name: string }>
    products: Array<{ id: number; name: string }>
    typeOptions: Array<{ value: string; label: string }>
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Estoque', href: '/inventory' },
    { title: 'Movimentações', href: '/inventory/movements' },
    { title: 'Nova movimentação', href: '/inventory/movements/create' },
]

const form = useForm({
    warehouse_id: '',
    product_id: '',
    type: '',
    quantity: '',
    unit_cost: '',
    reference: '',
    notes: '',
    moved_at: '',
})

function submit() {
    form.post('/inventory/movements')
}
</script>

<template>
    <Head title="Nova movimentação" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <div
                class="relative overflow-hidden rounded-2xl border bg-card/40 p-4 shadow-sm backdrop-blur-[1px] sm:p-5"
            >
                <div
                    class="absolute inset-0"
                    style="background: linear-gradient(to bottom right, var(--company-color-soft), transparent, transparent);"
                />

                <div class="relative flex items-start justify-between gap-4">
                    <Heading
                        title="Nova movimentação"
                        description="Registre uma entrada, saída ou ajuste em um depósito."
                        :icon="Boxes"
                    />

                    <Link
                        href="/inventory/movements"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border bg-background px-4 py-2 text-sm font-medium transition hover:bg-muted"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        <span>Voltar</span>
                    </Link>
                </div>
            </div>

            <form
                class="space-y-8 rounded-2xl border bg-card/50 p-5 shadow-sm sm:p-6"
                @submit.prevent="submit"
            >
                <section class="space-y-4">
                    <div>
                        <h2 class="text-sm font-semibold tracking-tight">Dados da movimentação</h2>
                        <p class="text-sm text-muted-foreground">
                            Preencha os dados da movimentação de estoque.
                        </p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="warehouse_id">Depósito</Label>
                            <select
                                id="warehouse_id"
                                v-model="form.warehouse_id"
                                class="flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm"
                            >
                                <option value="">Selecione</option>
                                <option
                                    v-for="warehouse in warehouses"
                                    :key="warehouse.id"
                                    :value="String(warehouse.id)"
                                >
                                    {{ warehouse.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.warehouse_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="product_id">Produto</Label>
                            <select
                                id="product_id"
                                v-model="form.product_id"
                                class="flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm"
                            >
                                <option value="">Selecione</option>
                                <option
                                    v-for="product in products"
                                    :key="product.id"
                                    :value="String(product.id)"
                                >
                                    {{ product.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.product_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="type">Tipo</Label>
                            <select
                                id="type"
                                v-model="form.type"
                                class="flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm"
                            >
                                <option value="">Selecione</option>
                                <option
                                    v-for="typeOption in typeOptions"
                                    :key="typeOption.value"
                                    :value="typeOption.value"
                                >
                                    {{ typeOption.label }}
                                </option>
                            </select>
                            <InputError :message="form.errors.type" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="quantity">Quantidade</Label>
                            <Input
                                id="quantity"
                                v-model="form.quantity"
                                type="number"
                                step="0.001"
                                min="0.001"
                            />
                            <InputError :message="form.errors.quantity" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="unit_cost">Custo unitário</Label>
                            <Input
                                id="unit_cost"
                                v-model="form.unit_cost"
                                type="number"
                                step="0.01"
                                min="0"
                            />
                            <InputError :message="form.errors.unit_cost" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="reference">Referência</Label>
                            <Input id="reference" v-model="form.reference" />
                            <InputError :message="form.errors.reference" />
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <Label for="moved_at">Data da movimentação</Label>
                            <Input
                                id="moved_at"
                                v-model="form.moved_at"
                                type="datetime-local"
                            />
                            <InputError :message="form.errors.moved_at" />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="notes">Observação</Label>
                        <textarea
                            id="notes"
                            v-model="form.notes"
                            rows="4"
                            class="w-full rounded-md border bg-background px-3 py-2 text-sm"
                        />
                        <InputError :message="form.errors.notes" />
                    </div>
                </section>

                <div class="flex flex-col-reverse gap-3 border-t pt-6 sm:flex-row sm:items-center sm:justify-end">
                    <Link
                        href="/inventory/movements"
                        class="inline-flex items-center justify-center rounded-lg border px-4 py-2 text-sm font-medium transition hover:bg-muted"
                    >
                        Cancelar
                    </Link>

                    <Can permission="inventory.createStockMovement">
                        <Button :disabled="form.processing" class="sm:min-w-[140px]">
                            {{ form.processing ? 'Salvando...' : 'Salvar' }}
                        </Button>
                    </Can>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
