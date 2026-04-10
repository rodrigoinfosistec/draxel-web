<script setup lang="ts">
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft, Link2 } from 'lucide-vue-next'

type Option = {
    value: number
    label: string
}

defineProps<{
    suppliers: Option[]
    products: Option[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Vínculos fornecedor x produto', href: '/supplier-product-references' },
    { title: 'Novo vínculo', href: '/supplier-product-references/create' },
]

const form = useForm({
    supplier_id: '',
    product_id: '',
    supplier_product_code: '',
    supplier_product_description: '',
    barcode: '',
    unit: '',
    is_active: true,
})

function submit() {
    form.post('/supplier-product-references')
}
</script>

<template>
    <Head title="Novo vínculo fornecedor x produto" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <div class="relative overflow-hidden rounded-2xl border bg-card/40 p-4 shadow-sm backdrop-blur-[1px] sm:p-5">
                <div class="absolute inset-0" style="background: linear-gradient(to bottom right, var(--company-color-soft), transparent, transparent);" />

                <div class="relative flex items-start justify-between gap-4">
                    <Heading
                        title="Novo vínculo fornecedor x produto"
                        description="Cadastre a referência comercial do item do fornecedor."
                        :icon="Link2"
                    />

                    <Link
                        href="/supplier-product-references"
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
                        <h2 class="text-sm font-semibold tracking-tight">Vínculo principal</h2>
                        <p class="text-sm text-muted-foreground">
                            Defina qual produto interno corresponde ao item comercial do fornecedor.
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="supplier_id">Fornecedor</Label>
                            <select
                                id="supplier_id"
                                v-model="form.supplier_id"
                                class="flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm"
                            >
                                <option value="">Selecione</option>
                                <option
                                    v-for="supplier in suppliers"
                                    :key="supplier.value"
                                    :value="supplier.value"
                                >
                                    {{ supplier.label }}
                                </option>
                            </select>
                            <InputError :message="form.errors.supplier_id" />
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
                                    :key="product.value"
                                    :value="product.value"
                                >
                                    {{ product.label }}
                                </option>
                            </select>
                            <InputError :message="form.errors.product_id" />
                        </div>
                    </div>
                </section>

                <section class="space-y-4">
                    <div>
                        <h2 class="text-sm font-semibold tracking-tight">Referência comercial</h2>
                        <p class="text-sm text-muted-foreground">
                            Esses dados serão usados no vínculo inteligente do XML.
                        </p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="supplier_product_code">Código do fornecedor</Label>
                            <Input id="supplier_product_code" v-model="form.supplier_product_code" />
                            <InputError :message="form.errors.supplier_product_code" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="barcode">GTIN / EAN</Label>
                            <Input id="barcode" v-model="form.barcode" maxlength="14" />
                            <InputError :message="form.errors.barcode" />
                        </div>

                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="supplier_product_description">Descrição do fornecedor</Label>
                            <Input id="supplier_product_description" v-model="form.supplier_product_description" />
                            <InputError :message="form.errors.supplier_product_description" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="unit">Unidade</Label>
                            <Input id="unit" v-model="form.unit" />
                            <InputError :message="form.errors.unit" />
                        </div>
                    </div>
                </section>

                <section class="space-y-4">
                    <div class="rounded-xl border bg-background p-4">
                        <label class="flex cursor-pointer items-center justify-between gap-4 rounded-lg border bg-card px-4 py-3 transition hover:bg-muted/40">
                            <div class="space-y-1">
                                <div class="text-sm font-medium">Vínculo ativo</div>
                                <div class="text-xs text-muted-foreground">
                                    {{
                                        form.is_active
                                            ? 'O vínculo ficará disponível normalmente no sistema.'
                                            : 'O vínculo ficará inativo no sistema.'
                                    }}
                                </div>
                            </div>

                            <div class="relative inline-flex items-center">
                                <input v-model="form.is_active" type="checkbox" class="peer sr-only" />
                                <div class="h-6 w-11 rounded-full bg-muted transition peer-checked:bg-green-600" />
                                <div class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5" />
                            </div>
                        </label>

                        <InputError :message="form.errors.is_active" class="mt-2" />
                    </div>
                </section>

                <div class="flex justify-end">
                    <Button type="submit" :disabled="form.processing">
                        Salvar vínculo
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
