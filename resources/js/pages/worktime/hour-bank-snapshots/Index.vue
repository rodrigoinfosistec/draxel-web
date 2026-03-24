<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, router } from '@inertiajs/vue3'
import { Download, FileClock, FileText, Plus, Trash2 } from 'lucide-vue-next'
import Swal from 'sweetalert2'

type SnapshotItem = {
    id: number
    name: string
    period_start: string
    period_end: string
    status: string
    status_label: string
    employees_count: number
    consolidated_at: string | null
    reversed_at: string | null
    can_delete: boolean
}

defineProps<{
    snapshots: {
        data: SnapshotItem[]
        links?: Array<{
            url: string | null
            label: string
            active: boolean
        }>
    }
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Ponto', href: '/worktime' },
    { title: 'Fechamentos', href: '/worktime/hour-bank-snapshots' },
]

function statusBadgeClass(status: string) {
    if (status === 'draft') {
        return 'bg-amber-100 text-amber-700'
    }

    if (status === 'consolidated') {
        return 'bg-emerald-100 text-emerald-700'
    }

    if (status === 'reversed') {
        return 'bg-zinc-200 text-zinc-700'
    }

    return 'bg-muted text-foreground'
}

async function destroySnapshot(snapshotId: number) {
    const result = await Swal.fire({
        title: 'Excluir fechamento?',
        text: 'Essa ação removerá definitivamente este fechamento.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar',
        reverseButtons: true,
    })

    if (!result.isConfirmed) {
        return
    }

    router.delete(`/worktime/hour-bank-snapshots/${snapshotId}`)
}
</script>

<template>
    <Head title="Fechamentos" />

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
                        title="Fechamentos"
                        description="Gerencie snapshots, acompanhe revisões e consolide períodos do banco de horas."
                        :icon="FileClock"
                    />

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Can permission="worktime.exportHourBankSnapshot">
                            <a href="/worktime/hour-bank-snapshots/export/csv">
                                <Button variant="outline" type="button">
                                    <Download class="mr-2 h-4 w-4" />
                                    Exportar CSV
                                </Button>
                            </a>
                        </Can>

                        <Can permission="worktime.exportHourBankSnapshot">
                            <a href="/worktime/hour-bank-snapshots/export/pdf">
                                <Button variant="outline" type="button">
                                    <FileText class="mr-2 h-4 w-4" />
                                    Exportar PDF
                                </Button>
                            </a>
                        </Can>

                        <Can permission="worktime.createHourBankSnapshot">
                            <Link href="/worktime/hour-bank-snapshots/create">
                                <Button type="button">
                                    <Plus class="mr-2 h-4 w-4" />
                                    Novo fechamento
                                </Button>
                            </Link>
                        </Can>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border bg-card/50 shadow-sm">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-muted/50">
                            <tr>
                                <th class="px-4 py-3 text-left">Nome</th>
                                <th class="px-4 py-3 text-left">Período</th>
                                <th class="px-4 py-3 text-left">Status</th>
                                <th class="px-4 py-3 text-left">Funcionários</th>
                                <th class="px-4 py-3 text-left">Consolidado em</th>
                                <th class="px-4 py-3 text-left">Revertido em</th>
                                <th class="px-4 py-3 text-right">Ações</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr
                                v-for="snapshot in snapshots.data"
                                :key="snapshot.id"
                                class="border-t"
                            >
                                <td class="px-4 py-3 font-medium">
                                    {{ snapshot.name }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ snapshot.period_start }} a {{ snapshot.period_end }}
                                </td>

                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex rounded-md px-2 py-1 text-xs font-medium"
                                        :class="statusBadgeClass(snapshot.status)"
                                    >
                                        {{ snapshot.status_label }}
                                    </span>
                                </td>

                                <td class="px-4 py-3">
                                    {{ snapshot.employees_count }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ snapshot.consolidated_at ?? '—' }}
                                </td>

                                <td class="px-4 py-3">
                                    {{ snapshot.reversed_at ?? '—' }}
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-2">
                                        <Link :href="`/worktime/hour-bank-snapshots/${snapshot.id}`">
                                            <Button variant="outline" type="button">
                                                Abrir
                                            </Button>
                                        </Link>

                                        <Can permission="worktime.deleteHourBankSnapshot">
                                            <Button
                                                v-if="snapshot.can_delete"
                                                variant="outline"
                                                type="button"
                                                class="border-red-200 text-red-600 hover:bg-red-50 hover:text-red-700"
                                                @click="destroySnapshot(snapshot.id)"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </Button>
                                        </Can>
                                    </div>
                                </td>
                            </tr>

                            <tr v-if="snapshots.data.length === 0">
                                <td colspan="7" class="px-4 py-6 text-center text-muted-foreground">
                                    Nenhum fechamento encontrado.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div
                    v-if="snapshots.links && snapshots.links.length > 3"
                    class="flex flex-wrap items-center justify-end gap-2 border-t px-4 py-4"
                >
                    <Link
                        v-for="link in snapshots.links"
                        :key="link.label"
                        :href="link.url || '#'"
                        class="inline-flex"
                    >
                        <Button
                            type="button"
                            variant="outline"
                            :disabled="!link.url"
                            :class="{ 'border-primary text-primary': link.active }"
                            v-html="link.label"
                        />
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
