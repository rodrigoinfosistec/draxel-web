<script setup lang="ts">
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft, Box } from 'lucide-vue-next'

type Option = {
    value: number
    label: string
}

defineProps<{
    categories: Option[]
    brands: Option[]
    unitOfMeasures: Option[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Produtos', href: '/products' },
    { title: 'Novo produto', href: '/products/create' },
]

const form = useForm({
    name: '',
    sku: '',
    barcode: '',
    ncm_code: '',
    product_category_id: '',
    unit_of_measure_id: '',
    brand_id: '',
    description: '',
    purchase_description: '',
    tracks_stock: true,
    is_active: true,
})

function submit() {
    form.post('/products')
}
</script>

<template>
    <Head title="Novo produto" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <div class="relative overflow-hidden rounded-2xl border bg-card/40 p-4 shadow-sm backdrop-blur-[1px] sm:p-5">
                <div
                    class="absolute inset-0"
                    style="background: linear-gradient(to bottom right, var(--company-color-soft), transparent, transparent);"
                />

                <div class="relative flex items-start justify-between gap-4">
                    <Heading
                        title="Novo produto"
                        description="Cadastre um novo produto compartilhado."
                        :icon="Box"
                    />

                    <Link
                        href="/products"
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
                        <h2 class="text-sm font-semibold tracking-tight">Dados principais</h2>
                        <p class="text-sm text-muted-foreground">
                            Preencha os dados básicos do produto.
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="name">Nome</Label>
                            <Input id="name" v-model="form.name" />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="sku">SKU</Label>
                            <Input id="sku" v-model="form.sku" />
                            <InputError :message="form.errors.sku" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="product_category_id">Categoria</Label>
                            <select
                                id="product_category_id"
                                v-model="form.product_category_id"
                                class="flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm"
                            >
                                <option value="">Selecione</option>
                                <option
                                    v-for="category in categories"
                                    :key="category.value"
                                    :value="category.value"
                                >
                                    {{ category.label }}
                                </option>
                            </select>
                            <InputError :message="form.errors.product_category_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="unit_of_measure_id">Unidade de medida</Label>
                            <select
                                id="unit_of_measure_id"
                                v-model="form.unit_of_measure_id"
                                class="flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm"
                            >
                                <option value="">Selecione</option>
                                <option
                                    v-for="unitOfMeasure in unitOfMeasures"
                                    :key="unitOfMeasure.value"
                                    :value="unitOfMeasure.value"
                                >
                                    {{ unitOfMeasure.label }}
                                </option>
                            </select>
                            <InputError :message="form.errors.unit_of_measure_id" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="brand_id">Marca</Label>
                            <select
                                id="brand_id"
                                v-model="form.brand_id"
                                class="flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm"
                            >
                                <option value="">Selecione</option>
                                <option
                                    v-for="brand in brands"
                                    :key="brand.value"
                                    :value="brand.value"
                                >
                                    {{ brand.label }}
                                </option>
                            </select>
                            <InputError :message="form.errors.brand_id" />
                        </div>

                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="description">Descrição interna</Label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="4"
                                class="flex w-full rounded-md border bg-background px-3 py-2 text-sm"
                            />
                            <InputError :message="form.errors.description" />
                        </div>
                    </div>
                </section>

                <section class="space-y-4">
                    <div>
                        <h2 class="text-sm font-semibold tracking-tight">Identificação de recebimento</h2>
                        <p class="text-sm text-muted-foreground">
                            Esses dados ajudam no vínculo inteligente com XML de NF-e e conferência de recebimento.
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="barcode">GTIN / EAN</Label>
                            <Input id="barcode" v-model="form.barcode" maxlength="14" />
                            <InputError :message="form.errors.barcode" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="ncm_code">NCM</Label>
                            <Input id="ncm_code" v-model="form.ncm_code" maxlength="8" />
                            <InputError :message="form.errors.ncm_code" />
                        </div>

                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="purchase_description">Descrição de compra / XML</Label>
                            <Input id="purchase_description" v-model="form.purchase_description" />
                            <InputError :message="form.errors.purchase_description" />
                        </div>
                    </div>
                </section>

                <section class="space-y-4">
                    <div>
                        <h2 class="text-sm font-semibold tracking-tight">Controle</h2>
                        <p class="text-sm text-muted-foreground">
                            Defina como o produto se comporta no sistema.
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="rounded-xl border bg-background p-4">
                            <label class="flex cursor-pointer items-center justify-between gap-4 rounded-lg border bg-card px-4 py-3 transition hover:bg-muted/40">
                                <div class="space-y-1">
                                    <div class="text-sm font-medium">Controla estoque</div>
                                    <div class="text-xs text-muted-foreground">
                                        {{
                                            form.tracks_stock
                                                ? 'O produto participará normalmente do controle de estoque.'
                                                : 'O produto não participará do controle de estoque.'
                                        }}
                                    </div>
                                </div>

                                <div class="relative inline-flex items-center">
                                    <input
                                        v-model="form.tracks_stock"
                                        type="checkbox"
                                        class="peer sr-only"
                                    />
                                    <div class="h-6 w-11 rounded-full bg-muted transition peer-checked:bg-green-600" />
                                    <div class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5" />
                                </div>
                            </label>

                            <InputError :message="form.errors.tracks_stock" class="mt-2" />
                        </div>

                        <div class="rounded-xl border bg-background p-4">
                            <label class="flex cursor-pointer items-center justify-between gap-4 rounded-lg border bg-card px-4 py-3 transition hover:bg-muted/40">
                                <div class="space-y-1">
                                    <div class="text-sm font-medium">Produto ativo</div>
                                    <div class="text-xs text-muted-foreground">
                                        {{
                                            form.is_active
                                                ? 'O produto ficará disponível normalmente no sistema.'
                                                : 'O produto ficará inativo no sistema.'
                                        }}
                                    </div>
                                </div>

                                <div class="relative inline-flex items-center">
                                    <input
                                        v-model="form.is_active"
                                        type="checkbox"
                                        class="peer sr-only"
                                    />
                                    <div class="h-6 w-11 rounded-full bg-muted transition peer-checked:bg-green-600" />
                                    <div class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5" />
                                </div>
                            </label>

                            <InputError :message="form.errors.is_active" class="mt-2" />
                        </div>
                    </div>
                </section>

                <div class="flex justify-end">
                    <Button type="submit" :disabled="form.processing">
                        Salvar produto
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
