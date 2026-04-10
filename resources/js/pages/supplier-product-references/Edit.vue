<script setup lang="ts">
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { useConfirm } from '@/composables/useConfirm'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ArrowLeft, Link2 } from 'lucide-vue-next'

type Option = {
    value: number
    label: string
}

const props = defineProps<{
    reference: {
        id: number
        supplier_id: number
        product_id: number
        supplier_product_code: string | null
        supplier_product_description: string | null
        barcode: string | null
        unit: string | null
        is_active: boolean
    }
    suppliers: Option[]
    products: Option[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Vínculos fornecedor x produto', href: '/supplier-product-references' },
    { title: 'Editar vínculo', href: `/supplier-product-references/${props.reference.id}/edit` },
]

const form = useForm({
    supplier_id: props.reference.supplier_id,
    product_id: props.reference.product_id,
    supplier_product_code: props.reference.supplier_product_code ?? '',
    supplier_product_description: props.reference.supplier_product_description ?? '',
    barcode: props.reference.barcode ?? '',
    unit: props.reference.unit ?? '',
    is_active: props.reference.is_active,
})

function submit() {
    form.put(`/supplier-product-references/${props.reference.id}`, {
        preserveState: false,
        preserveScroll: false,
    })
}

async function destroy() {
    const confirmed = await useConfirm({
        title: 'Excluir vínculo?',
        text: 'Essa ação removerá o vínculo permanentemente.',
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar',
        icon: 'warning',
    })

    if (!confirmed) {
        return
    }

    router.delete(`/supplier-product-references/${props.reference.id}`)
}
</script>

<template>
    <Head title="Editar vínculo fornecedor x produto" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <div class="relative overflow-hidden rounded-2xl border bg-card/40 p-4 shadow-sm backdrop-blur-[1px] sm:p-5">
                <div class="absolute inset-0" style="background: linear-gradient(to bottom right, var(--company-color-soft), transparent, transparent);" />

                <div class="relative flex items-start justify-between gap-4">
                    <Heading
                        title="Editar vínculo fornecedor x produto"
                        description="Atualize a referência comercial do item do fornecedor."
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

                <div class="flex flex-col justify-between gap-3 sm:flex-row">
                    <Button type="button" variant="destructive" @click="destroy">
                        Excluir vínculo
                    </Button>

                    <Button type="submit" :disabled="form.processing">
                        Salvar alterações
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
