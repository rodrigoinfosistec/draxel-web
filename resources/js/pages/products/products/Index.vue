<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, router } from '@inertiajs/vue3'
import { Box, Download, FileText, Pencil } from 'lucide-vue-next'
import { computed, ref } from 'vue'

type ProductItem = {
    id: number
    name: string
    sku: string
    barcode: string | null
    ncm_code: string | null
    purchase_description: string | null
    category: string | null
    brand: string | null
    unit_of_measure: string | null
    description: string | null
    tracks_stock: boolean
    is_active: boolean
    created_at: string | null
}

type PaginationLink = {
    url: string | null
    label: string
    active: boolean
}

const props = defineProps<{
    products: {
        data: ProductItem[]
        links: PaginationLink[]
    }
    filters: {
        search: string
    }
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Menu Produtos', href: '/products/dashboard' },
    { title: 'Produtos', href: '/products' },
]

const search = ref(props.filters.search ?? '')

function submitSearch() {
    router.get(
        '/products',
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
        csv: query ? `/products/export/csv?${query}` : '/products/export/csv',
        pdf: query ? `/products/export/pdf?${query}` : '/products/export/pdf',
    }
})
</script>

<template>
    <Head title="Produtos" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <div class="relative overflow-hidden rounded-2xl border bg-card/40 p-4 shadow-sm backdrop-blur-[1px] sm:p-5">
                <div
                    class="absolute inset-0"
                    style="background: linear-gradient(to bottom right, var(--company-color-soft), transparent, transparent);"
                />

                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Produtos"
                        description="Gerencie os produtos compartilhados do tenant."
                        :icon="Box"
                    />

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Can permission="products.viewAny">
                            <Button as-child variant="outline" class="w-full sm:w-auto">
                                <a :href="exportParams.csv">
                                    <Download class="mr-2 h-4 w-4" />
                                    Exportar CSV
                                </a>
                            </Button>
                        </Can>

                        <Can permission="products.viewAny">
                            <Button as-child variant="outline" class="w-full sm:w-auto">
                                <a :href="exportParams.pdf">
                                    <FileText class="mr-2 h-4 w-4" />
                                    Exportar PDF
                                </a>
                            </Button>
                        </Can>

                        <Can permission="products.create">
                            <Link href="/products/create" class="w-full sm:w-auto">
                                <Button class="w-full sm:w-auto">Novo produto</Button>
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
                        placeholder="Buscar por nome, SKU, GTIN/EAN, NCM, descrição ou descrição de compra"
                        class="w-full"
                    />
                    <Button type="submit" variant="outline" class="w-full sm:w-auto">
                        Buscar
                    </Button>
                </form>
            </div>

            <div class="rounded-xl border bg-card/50 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-[1320px] w-full text-sm">
                        <thead class="bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Produto</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">SKU</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">GTIN/EAN</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">NCM</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Categoria</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Marca</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Unidade</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Estoque</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Status</th>
                                <th class="px-4 py-3 text-right whitespace-nowrap">Ações</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="product in products.data"
                                :key="product.id"
                                class="border-t"
                            >
                                <td class="px-4 py-3 align-top">
                                    <div class="font-medium">{{ product.name }}</div>

                                    <div v-if="product.purchase_description" class="text-xs text-muted-foreground">
                                        Compra/XML: {{ product.purchase_description }}
                                    </div>

                                    <div class="text-xs text-muted-foreground">
                                        {{ product.description || '—' }}
                                    </div>
                                </td>

                                <td class="px-4 py-3 align-top">
                                    <div class="font-medium">{{ product.sku }}</div>
                                </td>

                                <td class="px-4 py-3 align-top">
                                    {{ product.barcode || '—' }}
                                </td>

                                <td class="px-4 py-3 align-top">
                                    {{ product.ncm_code || '—' }}
                                </td>

                                <td class="px-4 py-3 align-top">
                                    {{ product.category || '—' }}
                                </td>

                                <td class="px-4 py-3 align-top">
                                    {{ product.brand || '—' }}
                                </td>

                                <td class="px-4 py-3 align-top">
                                    {{ product.unit_of_measure || '—' }}
                                </td>

                                <td class="px-4 py-3 align-top">
                                    <span
                                        class="inline-flex rounded-md px-2 py-1 text-xs whitespace-nowrap"
                                        :class="
                                            product.tracks_stock
                                                ? 'bg-blue-100 text-blue-700'
                                                : 'bg-zinc-100 text-zinc-700'
                                        "
                                    >
                                        {{ product.tracks_stock ? 'Controla' : 'Não controla' }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 align-top">
                                    <span
                                        class="inline-flex rounded-md px-2 py-1 text-xs whitespace-nowrap"
                                        :class="
                                            product.is_active
                                                ? 'bg-green-100 text-green-700'
                                                : 'bg-red-100 text-red-700'
                                        "
                                    >
                                        {{ product.is_active ? 'Ativo' : 'Inativo' }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 text-right align-top">
                                    <div class="flex justify-end gap-2">
                                        <Can permission="products.update">
                                            <Link
                                                :href="`/products/${product.id}/edit`"
                                                class="inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-medium text-foreground transition hover:bg-muted"
                                            >
                                                <Pencil class="h-4 w-4" />
                                                <span class="hidden sm:inline">Editar</span>
                                            </Link>
                                        </Can>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="products.data.length === 0">
                                <td colspan="10" class="px-4 py-8 text-center text-muted-foreground">
                                    Nenhum produto encontrado.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <Link
                    v-for="link in products.links"
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
