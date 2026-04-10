<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, router } from '@inertiajs/vue3'
import { Download, FileText, Link2, Pencil } from 'lucide-vue-next'
import { computed, ref } from 'vue'

type ReferenceItem = {
    id: number
    supplier: string | null
    supplier_trade_name: string | null
    product: string | null
    product_sku: string | null
    supplier_product_code: string | null
    supplier_product_description: string | null
    barcode: string | null
    unit: string | null
    is_active: boolean
    created_at: string | null
}

type PaginationLink = {
    url: string | null
    label: string
    active: boolean
}

const props = defineProps<{
    references: {
        data: ReferenceItem[]
        links: PaginationLink[]
    }
    filters: {
        search: string
    }
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Vínculos fornecedor x produto', href: '/supplier-product-references' },
]

const search = ref(props.filters.search ?? '')

function submitSearch() {
    router.get(
        '/supplier-product-references',
        { search: search.value || undefined },
        {
            preserveState: true,
            replace: true,
        },
    )
}

const exportParams = computed(() => {
    const params = new URLSearchParams()

    if (search.value) params.set('search', search.value)

    const query = params.toString()

    return {
        csv: query ? `/supplier-product-references/export/csv?${query}` : '/supplier-product-references/export/csv',
        pdf: query ? `/supplier-product-references/export/pdf?${query}` : '/supplier-product-references/export/pdf',
    }
})
</script>

<template>
    <Head title="Vínculos fornecedor x produto" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <div class="relative overflow-hidden rounded-2xl border bg-card/40 p-4 shadow-sm backdrop-blur-[1px] sm:p-5">
                <div class="absolute inset-0" style="background: linear-gradient(to bottom right, var(--company-color-soft), transparent, transparent);" />

                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Vínculos fornecedor x produto"
                        description="Mantenha os vínculos comerciais que sustentam o recebimento por XML."
                        :icon="Link2"
                    />

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Can permission="supplierProductReferences.viewAny">
                            <Button as-child variant="outline" class="w-full sm:w-auto">
                                <a :href="exportParams.csv">
                                    <Download class="mr-2 h-4 w-4" />
                                    Exportar CSV
                                </a>
                            </Button>
                        </Can>

                        <Can permission="supplierProductReferences.viewAny">
                            <Button as-child variant="outline" class="w-full sm:w-auto">
                                <a :href="exportParams.pdf">
                                    <FileText class="mr-2 h-4 w-4" />
                                    Exportar PDF
                                </a>
                            </Button>
                        </Can>

                        <Can permission="supplierProductReferences.create">
                            <Link href="/supplier-product-references/create" class="w-full sm:w-auto">
                                <Button class="w-full sm:w-auto">Novo vínculo</Button>
                            </Link>
                        </Can>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border bg-card/50 p-4 shadow-sm">
                <form class="flex flex-col gap-3 sm:flex-row" @submit.prevent="submitSearch">
                    <Input
                        v-model="search"
                        type="text"
                        placeholder="Buscar por fornecedor, produto, SKU, código, descrição, GTIN/EAN ou unidade"
                        class="w-full"
                    />
                    <Button type="submit" variant="outline" class="w-full sm:w-auto">
                        Buscar
                    </Button>
                </form>
            </div>

            <div class="rounded-xl border bg-card/50 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-[1400px] w-full text-sm">
                        <thead class="bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left">Fornecedor</th>
                                <th class="px-4 py-3 text-left">Produto</th>
                                <th class="px-4 py-3 text-left">Código do fornecedor</th>
                                <th class="px-4 py-3 text-left">Descrição do fornecedor</th>
                                <th class="px-4 py-3 text-left">GTIN/EAN</th>
                                <th class="px-4 py-3 text-left">Unidade</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-right">Ações</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="reference in references.data"
                                :key="reference.id"
                                class="border-t"
                            >
                                <td class="px-4 py-3 align-top">
                                    <div class="font-medium">{{ reference.supplier || '—' }}</div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ reference.supplier_trade_name || '—' }}
                                    </div>
                                </td>

                                <td class="px-4 py-3 align-top">
                                    <div class="font-medium">{{ reference.product || '—' }}</div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ reference.product_sku || '—' }}
                                    </div>
                                </td>

                                <td class="px-4 py-3 align-top">
                                    {{ reference.supplier_product_code || '—' }}
                                </td>

                                <td class="px-4 py-3 align-top">
                                    {{ reference.supplier_product_description || '—' }}
                                </td>

                                <td class="px-4 py-3 align-top">
                                    {{ reference.barcode || '—' }}
                                </td>

                                <td class="px-4 py-3 align-top">
                                    {{ reference.unit || '—' }}
                                </td>

                                <td class="px-4 py-3 align-top">
                                    <span
                                        class="inline-flex rounded-md px-2 py-1 text-xs whitespace-nowrap"
                                        :class="
                                            reference.is_active
                                                ? 'bg-green-100 text-green-700'
                                                : 'bg-red-100 text-red-700'
                                        "
                                    >
                                        {{ reference.is_active ? 'Ativo' : 'Inativo' }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 text-right align-top">
                                    <div class="flex justify-end gap-2">
                                        <Can permission="supplierProductReferences.update">
                                            <Link
                                                :href="`/supplier-product-references/${reference.id}/edit`"
                                                class="inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-medium transition hover:bg-muted"
                                            >
                                                <Pencil class="h-4 w-4" />
                                                <span class="hidden sm:inline">Editar</span>
                                            </Link>
                                        </Can>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="references.data.length === 0">
                                <td colspan="8" class="px-4 py-8 text-center text-muted-foreground">
                                    Nenhum vínculo encontrado.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <Link
                    v-for="link in references.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    v-html="link.label"
                    class="rounded-md border px-3 py-2 text-sm"
                    :class="{
                        'bg-muted': link.active,
                        'pointer-events-none opacity-50': !link.url,
                    }"
                />
            </div>
        </div>
    </AppLayout>
</template>
