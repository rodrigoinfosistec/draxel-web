<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { useConfirm } from '@/composables/useConfirm'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft, Factory, Plus, Trash2 } from 'lucide-vue-next'

type Option = {
    id: number
    name: string
}

const props = defineProps<{
    entry: {
        id: number
        number: string
        entry_date: string
        warehouse_id: number
        notes: string | null
        status: string
        items: {
            product_id: number
            quantity: number
            unit_cost: number | null
            notes: string | null
        }[]
    }
    warehouses: Option[]
    products: Option[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Produção', href: '/production' },
    { title: 'Entradas de produção', href: '/production/entries' },
    { title: 'Editar entrada de produção', href: `/production/entries/${props.entry.id}/edit` },
]

const form = useForm({
    entry_date: props.entry.entry_date,
    warehouse_id: String(props.entry.warehouse_id),
    notes: props.entry.notes ?? '',
    items: props.entry.items.map((item) => ({
        product_id: String(item.product_id),
        quantity: String(item.quantity),
        unit_cost: item.unit_cost !== null ? String(item.unit_cost) : '',
        notes: item.notes ?? '',
    })),
})

function addItem() {
    form.items.push({
        product_id: '',
        quantity: '',
        unit_cost: '',
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
    form.put(`/production/entries/${props.entry.id}`)
}

async function destroy() {
    const confirmed = await useConfirm({
        title: 'Excluir entrada de produção?',
        text: 'Essa ação removerá o lançamento permanentemente.',
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar',
        icon: 'warning',
    })

    if (!confirmed) {
        return
    }

    form.delete(`/production/entries/${props.entry.id}`)
}
</script>

<template>
    <Head title="Editar entrada de produção" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <div class="relative overflow-hidden rounded-2xl border bg-card/40 p-4 shadow-sm backdrop-blur-[1px] sm:p-5">
                <div
                    class="absolute inset-0"
                    style="background: linear-gradient(to bottom right, var(--company-color-soft), transparent, transparent);"
                />

                <div class="relative flex items-start justify-between gap-4">
                    <Heading
                        title="Editar entrada de produção"
                        description="Atualize os dados do lançamento em rascunho."
                        :icon="Factory"
                    />

                    <Link
                        :href="`/production/entries/${entry.id}`"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border bg-background px-4 py-2 text-sm font-medium transition hover:bg-muted"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        <span>Voltar</span>
                    </Link>
                </div>
            </div>

            <form class="space-y-8 rounded-2xl border bg-card/50 p-5 shadow-sm sm:p-6" @submit.prevent="submit">
                <section class="space-y-4">
                    <div>
                        <h2 class="text-sm font-semibold tracking-tight">Dados da entrada</h2>
                        <p class="text-sm text-muted-foreground">
                            Atualize os dados principais do lançamento.
                        </p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="entry_date">Data</Label>
                            <Input id="entry_date" v-model="form.entry_date" type="date" />
                            <InputError :message="form.errors.entry_date" />
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

                <section class="space-y-4">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-sm font-semibold tracking-tight">Itens da produção</h2>
                            <p class="text-sm text-muted-foreground">
                                Atualize os produtos e quantidades produzidas.
                            </p>
                        </div>

                        <Button type="button" variant="outline" @click="addItem">
                            <Plus class="mr-2 h-4 w-4" />
                            Adicionar item
                        </Button>
                    </div>

                    <div class="space-y-4">
                        <div
                            v-for="(item, index) in form.items"
                            :key="index"
                            class="rounded-xl border bg-background p-4"
                        >
                            <div class="mb-4 flex items-center justify-between gap-3">
                                <div class="text-sm font-medium">
                                    Item {{ index + 1 }}
                                </div>

                                <Button
                                    type="button"
                                    variant="destructive"
                                    size="sm"
                                    :disabled="form.items.length === 1"
                                    @click="removeItem(index)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>

                            <div class="grid gap-4 lg:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label :for="`product_${index}`">Produto</Label>
                                    <select
                                        :id="`product_${index}`"
                                        v-model="item.product_id"
                                        class="flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm"
                                    >
                                        <option value="">Selecione</option>
                                        <option
                                            v-for="product in products"
                                            :key="product.id"
                                            :value="product.id"
                                        >
                                            {{ product.name }}
                                        </option>
                                    </select>
                                    <InputError :message="form.errors[`items.${index}.product_id`]" />
                                </div>

                                <div class="grid gap-2">
                                    <Label :for="`quantity_${index}`">Quantidade</Label>
                                    <Input :id="`quantity_${index}`" v-model="item.quantity" type="number" step="0.001" min="0.001" />
                                    <InputError :message="form.errors[`items.${index}.quantity`]" />
                                </div>
                            </div>

                            <div class="mt-4 grid gap-4 lg:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label :for="`unit_cost_${index}`">Custo unitário</Label>
                                    <Input :id="`unit_cost_${index}`" v-model="item.unit_cost" type="number" step="0.01" min="0" />
                                    <InputError :message="form.errors[`items.${index}.unit_cost`]" />
                                </div>

                                <div class="grid gap-2">
                                    <Label :for="`item_notes_${index}`">Observação do item</Label>
                                    <Input :id="`item_notes_${index}`" v-model="item.notes" />
                                    <InputError :message="form.errors[`items.${index}.notes`]" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <InputError :message="form.errors.items" />
                </section>

                <div class="flex flex-col-reverse gap-3 border-t pt-6 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Can permission="production.deleteEntry">
                            <Button
                                type="button"
                                variant="destructive"
                                :disabled="form.processing"
                                class="sm:min-w-[140px]"
                                @click="destroy"
                            >
                                Excluir
                            </Button>
                        </Can>
                    </div>

                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center">
                        <Link
                            :href="`/production/entries/${entry.id}`"
                            class="inline-flex items-center justify-center rounded-lg border px-4 py-2 text-sm font-medium transition hover:bg-muted"
                        >
                            Cancelar
                        </Link>

                        <Can permission="production.updateEntry">
                            <Button :disabled="form.processing" class="sm:min-w-[140px]">
                                {{ form.processing ? 'Salvando...' : 'Salvar' }}
                            </Button>
                        </Can>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
