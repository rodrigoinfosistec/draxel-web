<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import { useConfirm } from '@/composables/useConfirm'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, router } from '@inertiajs/vue3'
import { ArrowLeft, Factory, FileText, Pencil } from 'lucide-vue-next'

const props = defineProps<{
    entry: {
        id: number
        number: string
        entry_date: string
        warehouse_id: number
        warehouse_name: string | null
        status: string
        status_label: string
        notes: string | null
        created_by: string | null
        posted_by: string | null
        posted_at: string | null
        cancelled_by: string | null
        cancelled_at: string | null
        cancel_reason: string | null
        items: {
            id: number
            product_id: number
            product_name: string | null
            quantity: number
            unit_cost: number | null
            total_cost: number | null
            notes: string | null
        }[]
    }
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Produção', href: '/production' },
    { title: 'Entradas de produção', href: '/production/entries' },
    { title: props.entry.number, href: `/production/entries/${props.entry.id}` },
]

function statusClass(status: string) {
    if (status === 'posted') {
        return 'bg-green-100 text-green-700'
    }

    if (status === 'cancelled') {
        return 'bg-red-100 text-red-700'
    }

    return 'bg-amber-100 text-amber-700'
}

async function postEntry() {
    const confirmed = await useConfirm({
        title: 'Lançar entrada de produção?',
        text: 'Essa ação refletirá os produtos no estoque do depósito informado.',
        confirmButtonText: 'Sim, lançar',
        cancelButtonText: 'Cancelar',
        icon: 'question',
    })

    if (!confirmed) {
        return
    }

    router.post(`/production/entries/${props.entry.id}/post`)
}

async function cancelEntry() {
    const confirmed = await useConfirm({
        title: 'Cancelar entrada de produção?',
        text: 'Essa ação realizará o estorno dos produtos no estoque.',
        confirmButtonText: 'Sim, cancelar',
        cancelButtonText: 'Voltar',
        icon: 'warning',
    })

    if (!confirmed) {
        return
    }

    router.post(`/production/entries/${props.entry.id}/cancel`)
}

function openPdf() {
    window.open(`/production/entries/${props.entry.id}/pdf`, '_blank')
}
</script>

<template>
    <Head :title="entry.number" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <div class="relative overflow-hidden rounded-2xl border bg-card/40 p-4 shadow-sm backdrop-blur-[1px] sm:p-5">
                <div
                    class="absolute inset-0"
                    style="background: linear-gradient(to bottom right, var(--company-color-soft), transparent, transparent);"
                />

                <div class="relative flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <Heading
                        :title="`Entrada de produção ${entry.number}`"
                        description="Visualize os dados do lançamento e as ações disponíveis."
                        :icon="Factory"
                    />

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Can permission="production.exportEntry">
                            <Button variant="outline" @click="openPdf">
                                <FileText class="mr-2 h-4 w-4" />
                                PDF
                            </Button>
                        </Can>

                        <Can permission="production.updateEntry">
                            <Link
                                v-if="entry.status === 'draft'"
                                :href="`/production/entries/${entry.id}/edit`"
                                class="w-full sm:w-auto"
                            >
                                <Button variant="outline" class="w-full sm:w-auto">
                                    <Pencil class="mr-2 h-4 w-4" />
                                    Editar
                                </Button>
                            </Link>
                        </Can>

                        <Link
                            href="/production/entries"
                            class="inline-flex items-center justify-center gap-2 rounded-lg border bg-background px-4 py-2 text-sm font-medium transition hover:bg-muted"
                        >
                            <ArrowLeft class="h-4 w-4" />
                            <span>Voltar</span>
                        </Link>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 xl:grid-cols-3">
                <div class="xl:col-span-2 space-y-6">
                    <div class="rounded-2xl border bg-card/50 p-5 shadow-sm">
                        <div class="mb-4 flex items-center justify-between gap-4">
                            <div>
                                <h2 class="text-sm font-semibold tracking-tight">Dados gerais</h2>
                                <p class="text-sm text-muted-foreground">
                                    Informações principais da entrada de produção.
                                </p>
                            </div>

                            <span
                                class="inline-flex rounded-md px-2 py-1 text-xs whitespace-nowrap"
                                :class="statusClass(entry.status)"
                            >
                                {{ entry.status_label }}
                            </span>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <div class="text-xs text-muted-foreground">Número</div>
                                <div class="text-sm font-medium">{{ entry.number }}</div>
                            </div>

                            <div>
                                <div class="text-xs text-muted-foreground">Data</div>
                                <div class="text-sm font-medium">{{ entry.entry_date }}</div>
                            </div>

                            <div>
                                <div class="text-xs text-muted-foreground">Depósito</div>
                                <div class="text-sm font-medium">{{ entry.warehouse_name || '—' }}</div>
                            </div>

                            <div>
                                <div class="text-xs text-muted-foreground">Criado por</div>
                                <div class="text-sm font-medium">{{ entry.created_by || '—' }}</div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <div class="text-xs text-muted-foreground">Observação</div>
                            <div class="mt-1 text-sm">{{ entry.notes || '—' }}</div>
                        </div>
                    </div>

                    <div class="rounded-2xl border bg-card/50 p-5 shadow-sm">
                        <div class="mb-4">
                            <h2 class="text-sm font-semibold tracking-tight">Itens</h2>
                            <p class="text-sm text-muted-foreground">
                                Produtos lançados na entrada de produção.
                            </p>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-[900px] w-full text-sm">
                                <thead class="bg-muted/50">
                                    <tr>
                                        <th class="px-4 py-3 text-left">Produto</th>
                                        <th class="px-4 py-3 text-left">Quantidade</th>
                                        <th class="px-4 py-3 text-left">Custo unitário</th>
                                        <th class="px-4 py-3 text-left">Custo total</th>
                                        <th class="px-4 py-3 text-left">Observação</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    <tr
                                        v-for="item in entry.items"
                                        :key="item.id"
                                        class="border-t"
                                    >
                                        <td class="px-4 py-3">{{ item.product_name || '—' }}</td>
                                        <td class="px-4 py-3">{{ item.quantity }}</td>
                                        <td class="px-4 py-3">{{ item.unit_cost ?? '—' }}</td>
                                        <td class="px-4 py-3">{{ item.total_cost ?? '—' }}</td>
                                        <td class="px-4 py-3">{{ item.notes || '—' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="rounded-2xl border bg-card/50 p-5 shadow-sm">
                        <div class="mb-4">
                            <h2 class="text-sm font-semibold tracking-tight">Ações</h2>
                            <p class="text-sm text-muted-foreground">
                                Operações disponíveis para este lançamento.
                            </p>
                        </div>

                        <div class="flex flex-col gap-3">
                            <Can permission="production.postEntry">
                                <Button
                                    v-if="entry.status === 'draft'"
                                    class="w-full"
                                    @click="postEntry"
                                >
                                    Lançar entrada
                                </Button>
                            </Can>

                            <Can permission="production.cancelEntry">
                                <Button
                                    v-if="entry.status === 'posted'"
                                    variant="destructive"
                                    class="w-full"
                                    @click="cancelEntry"
                                >
                                    Cancelar entrada
                                </Button>
                            </Can>
                        </div>
                    </div>

                    <div class="rounded-2xl border bg-card/50 p-5 shadow-sm">
                        <div class="mb-4">
                            <h2 class="text-sm font-semibold tracking-tight">Histórico</h2>
                        </div>

                        <div class="space-y-4 text-sm">
                            <div>
                                <div class="text-xs text-muted-foreground">Lançado por</div>
                                <div>{{ entry.posted_by || '—' }}</div>
                            </div>

                            <div>
                                <div class="text-xs text-muted-foreground">Data do lançamento</div>
                                <div>{{ entry.posted_at || '—' }}</div>
                            </div>

                            <div>
                                <div class="text-xs text-muted-foreground">Cancelado por</div>
                                <div>{{ entry.cancelled_by || '—' }}</div>
                            </div>

                            <div>
                                <div class="text-xs text-muted-foreground">Data do cancelamento</div>
                                <div>{{ entry.cancelled_at || '—' }}</div>
                            </div>

                            <div>
                                <div class="text-xs text-muted-foreground">Motivo do cancelamento</div>
                                <div>{{ entry.cancel_reason || '—' }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
