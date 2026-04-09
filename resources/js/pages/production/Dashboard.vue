<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link } from '@inertiajs/vue3'
import { ArrowRight, ClipboardList, Factory, LayoutGrid, PackagePlus } from 'lucide-vue-next'

type CardItem = {
    title: string
    description: string
    href: string
    permission: string
    action_label: string
    icon: string
}

defineProps<{
    stats: {
        entries_count: number
        posted_count: number
        draft_count: number
        total_quantity: number
    }
    cards: CardItem[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Produção',
        href: '/production',
    },
]

function resolveIcon(icon: string) {
    if (icon === 'factory') {
        return Factory
    }

    if (icon === 'package-plus') {
        return PackagePlus
    }

    if (icon === 'clipboard-list') {
        return ClipboardList
    }

    return LayoutGrid
}
</script>

<template>
    <Head title="Produção" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <div class="relative overflow-hidden rounded-2xl border bg-card/40 p-5 shadow-sm backdrop-blur-[1px] sm:p-6">
                <div
                    class="absolute inset-0"
                    style="background: linear-gradient(to bottom right, var(--company-color-soft), transparent, transparent);"
                />

                <div class="relative flex flex-col gap-4">
                    <Heading
                        title="Produção"
                        description="Acesse os serviços do módulo de produção."
                        :icon="Factory"
                    />
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border bg-card/50 p-5 shadow-sm">
                    <div class="text-sm text-muted-foreground">Entradas registradas</div>
                    <div class="mt-2 text-2xl font-semibold tracking-tight">
                        {{ stats.entries_count }}
                    </div>
                </div>

                <div class="rounded-2xl border bg-card/50 p-5 shadow-sm">
                    <div class="text-sm text-muted-foreground">Entradas lançadas</div>
                    <div class="mt-2 text-2xl font-semibold tracking-tight">
                        {{ stats.posted_count }}
                    </div>
                </div>

                <div class="rounded-2xl border bg-card/50 p-5 shadow-sm">
                    <div class="text-sm text-muted-foreground">Rascunhos</div>
                    <div class="mt-2 text-2xl font-semibold tracking-tight">
                        {{ stats.draft_count }}
                    </div>
                </div>

                <div class="rounded-2xl border bg-card/50 p-5 shadow-sm">
                    <div class="text-sm text-muted-foreground">Quantidade total</div>
                    <div class="mt-2 text-2xl font-semibold tracking-tight">
                        {{ stats.total_quantity }}
                    </div>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                <Can
                    v-for="card in cards"
                    :key="card.href"
                    :permission="card.permission"
                >
                    <div class="group relative overflow-hidden rounded-2xl border bg-card/50 p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
                        <div
                            class="absolute inset-0 opacity-0 transition group-hover:opacity-100"
                            style="background: linear-gradient(to bottom right, var(--company-color-soft), transparent, transparent);"
                        />

                        <div class="relative flex h-full flex-col gap-4">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl border bg-background">
                                <component :is="resolveIcon(card.icon)" class="h-5 w-5" />
                            </div>

                            <div class="space-y-2">
                                <h2 class="text-base font-semibold tracking-tight">
                                    {{ card.title }}
                                </h2>

                                <p class="text-sm text-muted-foreground">
                                    {{ card.description }}
                                </p>
                            </div>

                            <div class="mt-auto pt-2">
                                <Link :href="card.href" class="inline-flex w-full sm:w-auto">
                                    <Button variant="outline" class="w-full sm:w-auto">
                                        {{ card.action_label }}
                                        <ArrowRight class="ml-2 h-4 w-4" />
                                    </Button>
                                </Link>
                            </div>
                        </div>
                    </div>
                </Can>
            </div>
        </div>
    </AppLayout>
</template>
