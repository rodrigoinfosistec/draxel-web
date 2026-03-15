<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, router } from '@inertiajs/vue3'
import { Download, FileText, IdCard, Pencil } from 'lucide-vue-next'
import { computed, ref } from 'vue'

type PositionItem = {
    id: number
    name: string
} | null

type EmployeeItem = {
    id: number
    name: string
    cpf: string
    registration: string
    is_active: boolean
    position: PositionItem
}

type PaginationLink = {
    url: string | null
    label: string
    active: boolean
}

const props = defineProps<{
    employees: {
        data: EmployeeItem[]
        links: PaginationLink[]
    }
    filters: {
        search: string
    }
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Funcionários', href: '/employees' },
]

const search = ref(props.filters.search ?? '')

function submitSearch() {
    router.get(
        '/employees',
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
        csv: query ? `/employees/export/csv?${query}` : '/employees/export/csv',
        pdf: query ? `/employees/export/pdf?${query}` : '/employees/export/pdf',
    }
})
</script>

<template>
    <Head title="Funcionários" />

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
                        title="Funcionários"
                        description="Gerencie os funcionários."
                        :icon="IdCard"
                    />

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Can permission="employees.viewAny">
                            <Button as-child variant="outline" class="w-full sm:w-auto">
                                <a :href="exportParams.csv">
                                    <Download class="mr-2 h-4 w-4" />
                                    Exportar CSV
                                </a>
                            </Button>
                        </Can>

                        <Can permission="employees.viewAny">
                            <Button as-child variant="outline" class="w-full sm:w-auto">
                                <a :href="exportParams.pdf">
                                    <FileText class="mr-2 h-4 w-4" />
                                    Exportar PDF
                                </a>
                            </Button>
                        </Can>

                        <Can permission="employees.create">
                            <Link href="/employees/create" class="w-full sm:w-auto">
                                <Button class="w-full sm:w-auto">Novo funcionário</Button>
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
                        placeholder="Buscar por nome, CPF ou matrícula"
                        class="w-full"
                    />
                    <Button type="submit" variant="outline" class="w-full sm:w-auto">
                        Buscar
                    </Button>
                </form>
            </div>

            <div class="rounded-xl border bg-card/50 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-[820px] w-full text-sm">
                        <thead class="bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Funcionário</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Cargo</th>
                                <th class="px-4 py-3 text-left whitespace-nowrap">Status</th>
                                <th class="px-4 py-3 text-right whitespace-nowrap">Ações</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="employee in employees.data"
                                :key="employee.id"
                                class="border-t"
                            >
                                <td class="px-4 py-3 align-top">
                                    <div class="font-medium">{{ employee.name }}</div>
                                    <div class="text-xs text-muted-foreground">
                                        CPF: {{ employee.cpf }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        Matrícula: {{ employee.registration }}
                                    </div>
                                </td>

                                <td class="px-4 py-3 align-top">
                                    <div class="text-sm text-muted-foreground">
                                        {{ employee.position?.name ?? '—' }}
                                    </div>
                                </td>

                                <td class="px-4 py-3 align-top">
                                    <span
                                        class="inline-flex rounded-md px-2 py-1 text-xs whitespace-nowrap"
                                        :class="
                                            employee.is_active
                                                ? 'bg-green-100 text-green-700'
                                                : 'bg-red-100 text-red-700'
                                        "
                                    >
                                        {{ employee.is_active ? 'Ativo' : 'Inativo' }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 text-right align-top">
                                    <Can permission="employees.update">
                                        <Link
                                            :href="`/employees/${employee.id}/edit`"
                                            class="inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-medium text-foreground transition hover:bg-muted"
                                        >
                                            <Pencil class="h-4 w-4" />
                                            <span class="hidden sm:inline">Editar</span>
                                        </Link>
                                    </Can>
                                </td>
                            </tr>

                            <tr v-if="employees.data.length === 0">
                                <td colspan="4" class="px-4 py-8 text-center text-muted-foreground">
                                    Nenhum funcionário encontrado.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <Link
                    v-for="link in employees.links"
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
