<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, router } from '@inertiajs/vue3'
import { Boxes, Download, FileText, Search } from 'lucide-vue-next'
import { computed, ref } from 'vue'

const props = defineProps<{
    positions: {
        data: Array<{
            product_id: number
            product_name: string
            total_quantity: string | number
        }>
        links: Array<{ url: string | null; label: string; active: boolean }>
    }
    filters: {
        search: string
    }
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Estoque', href: '/inventory' },
    { title: 'Posição consolidada', href: '/inventory/positions/consolidated' },
]

const search = ref(props.filters.search ?? '')

function submitSearch() {
    router.get(
        '/inventory/positions/consolidated',
        {
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

    if (search.value) params.set('search', search.value)

    const query = params.toString()

    return {
        csv: query
            ? `/inventory/positions/consolidated/export/csv?${query}`
            : '/inventory/positions/consolidated/export/csv',
        pdf: query
            ? `/inventory/positions/consolidated/export/pdf?${query}`
            : '/inventory/positions/consolidated/export/pdf',
    }
})
</script>

<template>
    <Head title="Posição consolidada" />

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
                        title="Posição consolidada"
                        description="Consulte o saldo total dos produtos somando todos os depósitos."
                        :icon="Boxes"
                    />

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Can permission="inventory.exportInventoryPosition">
                            <Button as-child variant="outline" class="w-full sm:w-auto">
                                <a :href="exportParams.csv">
                                    <Download class="mr-2 h-4 w-4" />
                                    Exportar CSV
                                </a>
                            </Button>
                        </Can>

                        <Can permission="inventory.exportInventoryPosition">
                            <Button as-child variant="outline" class="w-full sm:w-auto">
                                <a :href="exportParams.pdf">
                                    <FileText class="mr-2 h-4 w-4" />
                                    Exportar PDF
                                </a>
                            </Button>
                        </Can>

                        <Can permission="inventory.viewAnyProductStock">
                            <Link href="/inventory/positions" class="w-full sm:w-auto">
                                <Button variant="outline" class="w-full sm:w-auto">
                                    Ver por depósito
                                </Button>
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
                        placeholder="Buscar por produto"
                        class="w-full"
                    />

                    <Button type="submit" variant="outline" class="w-full sm:w-auto">
                        <Search class="mr-2 h-4 w-4" />
                        Buscar
                    </Button>
                </form>
            </div>

            <div class="rounded-xl border bg-card/50 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-[720px] w-full text-sm">
                        <thead class="bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Produto</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Saldo total</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="position in positions.data"
                                :key="position.product_id"
                                class="border-t"
                            >
                                <td class="px-4 py-3 align-top">{{ position.product_name || '—' }}</td>
                                <td class="px-4 py-3 align-top">{{ position.total_quantity }}</td>
                            </tr>

                            <tr v-if="positions.data.length === 0">
                                <td colspan="2" class="px-4 py-8 text-center text-muted-foreground">
                                    Nenhuma posição consolidada encontrada.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <Link
                    v-for="link in positions.links"
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
