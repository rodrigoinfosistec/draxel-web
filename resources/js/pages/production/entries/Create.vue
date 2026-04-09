<script setup lang="ts">
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft, Factory, Plus, Search, Trash2 } from 'lucide-vue-next'
import { computed, ref } from 'vue'

type Option = {
    id: number
    name: string
}

const props = defineProps<{
    warehouses: Option[]
    products: Option[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Produção', href: '/production' },
    { title: 'Entradas de produção', href: '/production/entries' },
    { title: 'Nova entrada de produção', href: '/production/entries/create' },
]

const form = useForm({
    entry_date: new Date().toISOString().slice(0, 10),
    warehouse_id: '',
    notes: '',
    items: [] as Array<{
        product_id: string
        product_name: string
        quantity: string
        unit_cost: string
        notes: string
    }>,
})

const productSearch = ref('')
const selectedProductId = ref('')

const filteredProducts = computed(() => {
    const term = productSearch.value.trim().toLowerCase()

    if (term === '') {
        return props.products.slice(0, 20)
    }

    return props.products
        .filter((product) => product.name.toLowerCase().includes(term))
        .slice(0, 20)
})

const itemsCount = computed(() => form.items.length)

const totalQuantity = computed(() => {
    return form.items.reduce((total, item) => {
        const quantity = Number(item.quantity || 0)
        return total + (Number.isNaN(quantity) ? 0 : quantity)
    }, 0)
})

function addSelectedProduct() {
    if (!selectedProductId.value) {
        return
    }

    const product = props.products.find((item) => String(item.id) === String(selectedProductId.value))

    if (!product) {
        return
    }

    const existingIndex = form.items.findIndex(
        (item) => String(item.product_id) === String(product.id),
    )

    if (existingIndex >= 0) {
        const currentQuantity = Number(form.items[existingIndex].quantity || 0)
        form.items[existingIndex].quantity = String(currentQuantity > 0 ? currentQuantity + 1 : 1)
    } else {
        form.items.unshift({
            product_id: String(product.id),
            product_name: product.name,
            quantity: '1',
            unit_cost: '',
            notes: '',
        })
    }

    selectedProductId.value = ''
    productSearch.value = ''
}

function addProductByClick(product: Option) {
    selectedProductId.value = String(product.id)
    addSelectedProduct()
}

function removeItem(index: number) {
    form.items.splice(index, 1)
}

function moveItemUp(index: number) {
    if (index === 0) {
        return
    }

    const current = form.items[index]
    form.items[index] = form.items[index - 1]
    form.items[index - 1] = current
}

function moveItemDown(index: number) {
    if (index >= form.items.length - 1) {
        return
    }

    const current = form.items[index]
    form.items[index] = form.items[index + 1]
    form.items[index + 1] = current
}

function clearItems() {
    form.items = []
}

function submit() {
    form.transform((data) => ({
        ...data,
        items: data.items.map((item) => ({
            product_id: item.product_id,
            quantity: item.quantity,
            unit_cost: item.unit_cost,
            notes: item.notes,
        })),
    })).post('/production/entries')
}
</script>

<template>
    <Head title="Nova entrada de produção" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <div class="relative overflow-hidden rounded-2xl border bg-card/40 p-4 shadow-sm backdrop-blur-[1px] sm:p-5">
                <div
                    class="absolute inset-0"
                    style="background: linear-gradient(to bottom right, var(--company-color-soft), transparent, transparent);"
                />

                <div class="relative flex items-start justify-between gap-4">
                    <Heading
                        title="Nova entrada de produção"
                        description="Cadastre um lançamento de produção para refletir a entrada dos produtos no estoque."
                        :icon="Factory"
                    />

                    <Link
                        href="/production/entries"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border bg-background px-4 py-2 text-sm font-medium transition hover:bg-muted"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        <span>Voltar</span>
                    </Link>
                </div>
            </div>

            <form class="space-y-8" @submit.prevent="submit">
                <section class="rounded-2xl border bg-card/50 p-5 shadow-sm sm:p-6">
                    <div class="space-y-4">
                        <div>
                            <h2 class="text-sm font-semibold tracking-tight">Dados da entrada</h2>
                            <p class="text-sm text-muted-foreground">
                                Informe os dados principais do lançamento de produção.
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
                    </div>
                </section>

                <section class="rounded-2xl border bg-card/50 p-5 shadow-sm sm:p-6">
                    <div class="sticky top-0 z-10 -mx-5 -mt-5 mb-6 border-b bg-card/95 px-5 py-4 backdrop-blur sm:-mx-6 sm:-mt-6 sm:px-6">
                        <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                            <div class="space-y-1">
                                <h2 class="text-sm font-semibold tracking-tight">Itens da produção</h2>
                                <p class="text-sm text-muted-foreground">
                                    Adicione produtos rapidamente e ajuste a lista conforme necessário.
                                </p>
                            </div>

                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                <div class="rounded-xl border bg-background px-4 py-2 text-sm">
                                    <span class="text-muted-foreground">Itens:</span>
                                    <span class="ml-2 font-semibold">{{ itemsCount }}</span>
                                </div>

                                <div class="rounded-xl border bg-background px-4 py-2 text-sm">
                                    <span class="text-muted-foreground">Qtd. total:</span>
                                    <span class="ml-2 font-semibold">{{ totalQuantity.toFixed(3) }}</span>
                                </div>

                                <Button :disabled="form.processing || form.items.length === 0" class="sm:min-w-[140px]">
                                    {{ form.processing ? 'Salvando...' : 'Salvar' }}
                                </Button>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-5">
                        <div class="rounded-xl border bg-background p-4">
                            <div class="grid gap-4 xl:grid-cols-[1.2fr_1fr_auto]">
                                <div class="grid gap-2">
                                    <Label for="product_search">Buscar produto</Label>
                                    <div class="relative">
                                        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                        <Input
                                            id="product_search"
                                            v-model="productSearch"
                                            type="text"
                                            placeholder="Digite para localizar um produto"
                                            class="pl-9"
                                        />
                                    </div>
                                </div>

                                <div class="grid gap-2">
                                    <Label for="selected_product">Produto</Label>
                                    <select
                                        id="selected_product"
                                        v-model="selectedProductId"
                                        class="flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm"
                                    >
                                        <option value="">Selecione</option>
                                        <option
                                            v-for="product in filteredProducts"
                                            :key="product.id"
                                            :value="product.id"
                                        >
                                            {{ product.name }}
                                        </option>
                                    </select>
                                </div>

                                <div class="flex items-end">
                                    <Button type="button" variant="outline" class="w-full xl:w-auto" @click="addSelectedProduct">
                                        <Plus class="mr-2 h-4 w-4" />
                                        Adicionar
                                    </Button>
                                </div>
                            </div>

                            <div
                                v-if="productSearch.trim() !== '' && filteredProducts.length > 0"
                                class="mt-4 rounded-xl border"
                            >
                                <div class="max-h-56 overflow-y-auto">
                                    <button
                                        v-for="product in filteredProducts"
                                        :key="product.id"
                                        type="button"
                                        class="flex w-full items-center justify-between border-b px-4 py-3 text-left text-sm transition last:border-b-0 hover:bg-muted/60"
                                        @click="addProductByClick(product)"
                                    >
                                        <span>{{ product.name }}</span>
                                        <span class="text-xs text-muted-foreground">Adicionar</span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="text-sm text-muted-foreground">
                                Ajuste quantidades, custo e observações diretamente na lista.
                            </div>

                            <Button
                                type="button"
                                variant="destructive"
                                :disabled="form.items.length === 0"
                                @click="clearItems"
                            >
                                Limpar lista
                            </Button>
                        </div>

                        <div class="rounded-xl border bg-background">
                            <div v-if="form.items.length === 0" class="px-4 py-10 text-center text-sm text-muted-foreground">
                                Nenhum produto adicionado ainda.
                            </div>

                            <div v-else class="overflow-x-auto">
                                <table class="min-w-[1100px] w-full text-sm">
                                    <thead class="bg-muted/50">
                                        <tr>
                                            <th class="px-4 py-3 text-left">#</th>
                                            <th class="px-4 py-3 text-left">Produto</th>
                                            <th class="px-4 py-3 text-left">Quantidade</th>
                                            <th class="px-4 py-3 text-left">Custo unitário</th>
                                            <th class="px-4 py-3 text-left">Observação</th>
                                            <th class="px-4 py-3 text-right">Ações</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <tr
                                            v-for="(item, index) in form.items"
                                            :key="`${item.product_id}-${index}`"
                                            class="border-t align-top"
                                        >
                                            <td class="px-4 py-3 text-muted-foreground">
                                                {{ index + 1 }}
                                            </td>

                                            <td class="px-4 py-3">
                                                <div class="font-medium">{{ item.product_name }}</div>
                                                <div class="mt-1 text-xs text-muted-foreground">
                                                    ID: {{ item.product_id }}
                                                </div>
                                                <InputError :message="form.errors[`items.${index}.product_id`]" />
                                            </td>

                                            <td class="px-4 py-3">
                                                <Input
                                                    v-model="item.quantity"
                                                    type="number"
                                                    step="0.001"
                                                    min="0.001"
                                                />
                                                <InputError :message="form.errors[`items.${index}.quantity`]" />
                                            </td>

                                            <td class="px-4 py-3">
                                                <Input
                                                    v-model="item.unit_cost"
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                />
                                                <InputError :message="form.errors[`items.${index}.unit_cost`]" />
                                            </td>

                                            <td class="px-4 py-3">
                                                <Input v-model="item.notes" />
                                                <InputError :message="form.errors[`items.${index}.notes`]" />
                                            </td>

                                            <td class="px-4 py-3">
                                                <div class="flex justify-end gap-2">
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        :disabled="index === 0"
                                                        @click="moveItemUp(index)"
                                                    >
                                                        ↑
                                                    </Button>

                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        :disabled="index === form.items.length - 1"
                                                        @click="moveItemDown(index)"
                                                    >
                                                        ↓
                                                    </Button>

                                                    <Button
                                                        type="button"
                                                        variant="destructive"
                                                        size="sm"
                                                        @click="removeItem(index)"
                                                    >
                                                        <Trash2 class="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <InputError :message="form.errors.items" />
                    </div>
                </section>

                <div class="flex flex-col-reverse gap-3 border-t pt-6 sm:flex-row sm:items-center sm:justify-end">
                    <Link
                        href="/production/entries"
                        class="inline-flex items-center justify-center rounded-lg border px-4 py-2 text-sm font-medium transition hover:bg-muted"
                    >
                        Cancelar
                    </Link>

                    <Button :disabled="form.processing || form.items.length === 0" class="sm:min-w-[140px]">
                        {{ form.processing ? 'Salvando...' : 'Salvar' }}
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
