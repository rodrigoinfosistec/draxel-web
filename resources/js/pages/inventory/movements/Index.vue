<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, router } from '@inertiajs/vue3'
import { Boxes, Download, FileText, Plus, Search } from 'lucide-vue-next'
import { computed, ref } from 'vue'

const props = defineProps<{
    movements: {
        data: Array<{
            id: number
            warehouse_name: string | null
            product_name: string | null
            type_label: string
            source_label: string
            quantity: string | number
            unit_cost: string | number | null
            reference: string | null
            notes: string | null
            moved_at: string | null
            user_name: string | null
        }>
        links: Array<{ url: string | null; label: string; active: boolean }>
    }
    filters: {
        warehouse_id: string
        product_id: string
        type: string
        start_date: string
        end_date: string
        search: string
    }
    warehouses: Array<{ id: number; name: string }>
    products: Array<{ id: number; name: string }>
    typeOptions: Array<{ value: string; label: string }>
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Estoque', href: '/inventory' },
    { title: 'Movimentações', href: '/inventory/movements' },
]

const warehouseId = ref(props.filters.warehouse_id ?? '')
const productId = ref(props.filters.product_id ?? '')
const type = ref(props.filters.type ?? '')
const startDate = ref(props.filters.start_date ?? '')
const endDate = ref(props.filters.end_date ?? '')
const search = ref(props.filters.search ?? '')

function submitSearch() {
    router.get(
        '/inventory/movements',
        {
            warehouse_id: warehouseId.value || undefined,
            product_id: productId.value || undefined,
            type: type.value || undefined,
            start_date: startDate.value || undefined,
            end_date: endDate.value || undefined,
            search: search.value || undefined,
        },
        {
            preserveState: true,
            replace: true,
        },
    )
}

const exportParams = computed(() => {
    const params = new URLSearchParams()

    if (warehouseId.value) params.set('warehouse_id', warehouseId.value)
    if (productId.value) params.set('product_id', productId.value)
    if (type.value) params.set('type', type.value)
    if (startDate.value) params.set('start_date', startDate.value)
    if (endDate.value) params.set('end_date', endDate.value)
    if (search.value) params.set('search', search.value)

    const query = params.toString()

    return {
        csv: query ? `/inventory/movements/export/csv?${query}` : '/inventory/movements/export/csv',
        pdf: query ? `/inventory/movements/export/pdf?${query}` : '/inventory/movements/export/pdf',
    }
})
</script>

<template>
    <Head title="Movimentações" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <div
                class="relative overflow-hidden rounded-2xl border bg-card/40 p-4 shadow-sm backdrop-blur-[1px] sm:p-5"
            >
                <div
                    class="absolute inset-0"
                    style="background: linear-gradient(to bottom right, var(--company-color-soft), transparent, transparent);"
                />

                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Movimentações"
                        description="Consulte o histórico de entradas, saídas e ajustes por depósito."
                        :icon="Boxes"
                    />

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Can permission="inventory.exportStockMovement">
                            <Button as-child variant="outline" class="w-full sm:w-auto">
                                <a :href="exportParams.csv">
                                    <Download class="mr-2 h-4 w-4" />
                                    Exportar CSV
                                </a>
                            </Button>
                        </Can>

                        <Can permission="inventory.exportStockMovement">
                            <Button as-child variant="outline" class="w-full sm:w-auto">
                                <a :href="exportParams.pdf">
                                    <FileText class="mr-2 h-4 w-4" />
                                    Exportar PDF
                                </a>
                            </Button>
                        </Can>

                        <Can permission="inventory.createStockMovement">
                            <Link href="/inventory/movements/create" class="w-full sm:w-auto">
                                <Button class="w-full sm:w-auto">
                                    <Plus class="mr-2 h-4 w-4" />
                                    Nova movimentação
                                </Button>
                            </Link>
                        </Can>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border bg-card/50 p-4 shadow-sm">
                <form class="grid gap-3 md:grid-cols-2 xl:grid-cols-6" @submit.prevent="submitSearch">
                    <select
                        v-model="warehouseId"
                        class="flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm"
                    >
                        <option value="">Todos os depósitos</option>
                        <option
                            v-for="warehouse in warehouses"
                            :key="warehouse.id"
                            :value="String(warehouse.id)"
                        >
                            {{ warehouse.name }}
                        </option>
                    </select>

                    <select
                        v-model="productId"
                        class="flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm"
                    >
                        <option value="">Todos os produtos</option>
                        <option
                            v-for="product in products"
                            :key="product.id"
                            :value="String(product.id)"
                        >
                            {{ product.name }}
                        </option>
                    </select>

                    <select
                        v-model="type"
                        class="flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm"
                    >
                        <option value="">Todos os tipos</option>
                        <option
                            v-for="option in typeOptions"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </option>
                    </select>

                    <Input v-model="startDate" type="date" />
                    <Input v-model="endDate" type="date" />
                    <Input v-model="search" type="text" placeholder="Produto, depósito, referência ou observação" />

                    <div class="md:col-span-2 xl:col-span-6 flex flex-col gap-3 sm:flex-row">
                        <Button type="submit" variant="outline" class="w-full sm:w-auto">
                            <Search class="mr-2 h-4 w-4" />
                            Buscar
                        </Button>
                    </div>
                </form>
            </div>

            <div class="rounded-xl border bg-card/50 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-[1180px] w-full text-sm">
                        <thead class="bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Depósito</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Produto</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Tipo</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Origem</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Quantidade</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Custo unitário</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Referência</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Observação</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Data</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Usuário</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="movement in movements.data"
                                :key="movement.id"
                                class="border-t"
                            >
                                <td class="px-4 py-3 align-top">{{ movement.warehouse_name || '—' }}</td>
                                <td class="px-4 py-3 align-top">{{ movement.product_name || '—' }}</td>
                                <td class="px-4 py-3 align-top">{{ movement.type_label || '—' }}</td>
                                <td class="px-4 py-3 align-top">{{ movement.source_label || '—' }}</td>
                                <td class="px-4 py-3 align-top">{{ movement.quantity }}</td>
                                <td class="px-4 py-3 align-top">{{ movement.unit_cost ?? '—' }}</td>
                                <td class="px-4 py-3 align-top">{{ movement.reference || '—' }}</td>
                                <td class="px-4 py-3 align-top">{{ movement.notes || '—' }}</td>
                                <td class="px-4 py-3 align-top">{{ movement.moved_at || '—' }}</td>
                                <td class="px-4 py-3 align-top">{{ movement.user_name || '—' }}</td>
                            </tr>

                            <tr v-if="movements.data.length === 0">
                                <td colspan="10" class="px-4 py-8 text-center text-muted-foreground">
                                    Nenhuma movimentação encontrada.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <Link
                    v-for="link in movements.links"
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
