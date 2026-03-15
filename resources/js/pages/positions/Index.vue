<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, router } from '@inertiajs/vue3'
import { BriefcaseBusiness, Download, FileText, Pencil } from 'lucide-vue-next'
import { computed, ref } from 'vue'

type PositionItem = {
    id: number
    name: string
    slug: string
    description: string | null
}

type PaginationLink = {
    url: string | null
    label: string
    active: boolean
}

const props = defineProps<{
    positions: {
        data: PositionItem[]
        links: PaginationLink[]
    }
    filters: {
        search: string
    }
}>()

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Cargos de funcionário',
        href: '/positions',
    },
]

const search = ref(props.filters.search ?? '')

function submitSearch() {
    router.get(
        '/positions',
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
        csv: query ? `/positions/export/csv?${query}` : '/positions/export/csv',
        pdf: query ? `/positions/export/pdf?${query}` : '/positions/export/pdf',
    }
})
</script>

<template>
    <Head title="Cargos" />

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
                        title="Cargos de funcionário"
                        description="Gerencie os cargos."
                        :icon="BriefcaseBusiness"
                    />

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Can permission="positions.viewAny">
                            <Button as-child variant="outline" class="w-full sm:w-auto">
                                <a :href="exportParams.csv">
                                    <Download class="mr-2 h-4 w-4" />
                                    Exportar CSV
                                </a>
                            </Button>
                        </Can>

                        <Can permission="positions.viewAny">
                            <Button as-child variant="outline" class="w-full sm:w-auto">
                                <a :href="exportParams.pdf">
                                    <FileText class="mr-2 h-4 w-4" />
                                    Exportar PDF
                                </a>
                            </Button>
                        </Can>

                        <Can permission="positions.create">
                            <Link href="/positions/create" class="w-full sm:w-auto">
                                <Button class="w-full sm:w-auto">Novo cargo</Button>
                            </Link>
                        </Can>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border bg-card/50 p-4 shadow-sm">
                <form
                    class="flex flex-col gap-3 sm:flex-row"
                    @submit.prevent="submitSearch"
                >
                    <Input
                        v-model="search"
                        type="text"
                        placeholder="Buscar por nome, slug ou descrição"
                        class="w-full"
                    />

                    <Button type="submit" variant="outline" class="w-full sm:w-auto">
                        Buscar
                    </Button>
                </form>
            </div>

            <div class="rounded-xl border bg-card/50 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-[720px] w-full text-sm">
                        <thead class="bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Nome</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Slug</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Descrição</th>
                                <th class="px-4 py-3 text-right whitespace-nowrap">Ações</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="position in positions.data"
                                :key="position.id"
                                class="border-t"
                            >
                                <td class="px-4 py-3 align-top">
                                    <div class="font-medium">{{ position.name }}</div>
                                </td>

                                <td class="px-4 py-3 align-top">
                                    <div class="text-sm text-muted-foreground">
                                        {{ position.slug }}
                                    </div>
                                </td>

                                <td class="px-4 py-3 align-top">
                                    <div class="text-sm text-muted-foreground">
                                        {{ position.description || '—' }}
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-right align-top">
                                    <Can permission="positions.update">
                                        <Link
                                            :href="`/positions/${position.id}/edit`"
                                            class="inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-medium text-foreground transition hover:bg-muted"
                                        >
                                            <Pencil class="h-4 w-4" />
                                            <span class="hidden sm:inline">Editar</span>
                                        </Link>
                                    </Can>
                                </td>
                            </tr>

                            <tr v-if="positions.data.length === 0">
                                <td
                                    colspan="4"
                                    class="px-4 py-8 text-center text-muted-foreground"
                                >
                                    Nenhum cargo encontrado.
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
