<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { dashboard } from '@/routes'
import type { BreadcrumbItem } from '@/types'
import { Head, usePage } from '@inertiajs/vue3'
import {
    Activity,
    BarChart3,
    Blocks,
    BriefcaseBusiness,
    Building2,
    ChartNoAxesCombined,
    CircleCheckBig,
    FolderKanban,
    Gauge,
    LayoutGrid,
    ShieldCheck,
    Sparkles,
    Users
} from 'lucide-vue-next'
import { computed } from 'vue'

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
    },
]

type Tenant = {
    id: number
    slug: string
    name: string
}

type User = {
    id: number
    name: string
}

type Company = {
    id: number
    name: string
}

const page = usePage<{
    auth: {
        user: User
    }
    tenant: Tenant | null
    currentCompany: Company | null
}>()

const user = computed(() => page.props.auth.user)
const tenant = computed(() => page.props.tenant)
const currentCompany = computed(() => page.props.currentCompany)

const overviewCards = computed(() => [
    {
        title: 'Grupo',
        value: tenant.value?.name ?? 'Não identificado',
        helper: tenant.value?.slug ? `@${tenant.value.slug}` : 'Indisponível',
        icon: BriefcaseBusiness,
    },
    {
        title: 'Empresa',
        value: currentCompany.value?.name ?? 'Não selecionada',
        helper: 'Contexto ativo',
        icon: Building2,
    },
    {
        title: 'Usuário',
        value: user.value?.name ?? 'Usuário',
        helper: 'Sessão autenticada',
        icon: Users,
    },
    {
        title: 'Ambiente',
        value: 'Online',
        helper: 'Operação normal',
        icon: Activity,
        highlight: true,
    },
])

const quickAccessItems = [
    {
        title: 'Módulos',
        description: 'Acesso às áreas funcionais do sistema.',
        icon: FolderKanban,
        badge: 'Sistema',
    },
    {
        title: 'Usuários',
        description: 'Gestão de acessos e perfis.',
        icon: ShieldCheck,
        badge: 'Acesso',
    },
    {
        title: 'Relatórios',
        description: 'Visualização de informações consolidadas.',
        icon: BarChart3,
        badge: 'Análise',
    },
    {
        title: 'Recursos',
        description: 'Estrutura disponível no ambiente.',
        icon: Blocks,
        badge: 'Ambiente',
    },
]

const statusItems = [
    {
        label: 'Disponibilidade',
        value: 'Ativa',
        tone: 'text-emerald-600',
    },
    {
        label: 'Estrutura',
        value: 'Estável',
        tone: 'text-primary',
    },
    {
        label: 'Operação',
        value: 'Em curso',
        tone: 'text-foreground',
    },
]

const highlights = [
    {
        title: 'Visão operacional',
        description: 'Leitura centralizada das informações principais do ambiente.',
        icon: LayoutGrid,
    },
    {
        title: 'Gestão estruturada',
        description: 'Organização visual orientada à navegação e acompanhamento.',
        icon: ShieldCheck,
    },
    {
        title: 'Expansão contínua',
        description: 'Base preparada para novas áreas, indicadores e recursos.',
        icon: Gauge,
    },
]

const roadmapItems = [
    'Indicadores operacionais',
    'Alertas do ambiente',
    'Resumo diário',
    'Atalhos estratégicos',
]
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <section class="relative overflow-hidden rounded-3xl border bg-card/50 shadow-sm backdrop-blur-[1px]">
                <div
                    class="absolute inset-0"
                    style="background: linear-gradient(135deg, var(--company-color-soft), transparent 45%, transparent 100%);"
                />
                <div class="absolute right-0 top-0 h-40 w-40 rounded-full bg-primary/5 blur-3xl" />

                <div class="relative flex flex-col gap-8 p-6 md:p-8 xl:p-10">
                    <div class="flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
                        <div class="max-w-3xl space-y-4">
                            <div class="inline-flex w-fit items-center gap-2 rounded-full border bg-background/80 px-3 py-1 text-xs font-medium text-muted-foreground backdrop-blur">
                                <Sparkles class="h-3.5 w-3.5" />
                                Painel principal
                            </div>

                            <div class="space-y-3">
                                <h1
                                    class="text-3xl font-semibold tracking-tight md:text-4xl xl:text-5xl"
                                    style="color: var(--company-color);"
                                >
                                    Dashboard
                                </h1>

                                <p class="max-w-2xl text-sm leading-6 text-muted-foreground md:text-base">
                                    Visão central do ambiente, com acesso rápido às informações principais da operação.
                                </p>
                            </div>

                            <div class="flex flex-wrap gap-2 pt-1">
                                <div class="inline-flex items-center gap-2 rounded-full border bg-background/75 px-3 py-1.5 text-xs text-muted-foreground">
                                    <CircleCheckBig class="h-3.5 w-3.5 text-emerald-600" />
                                    Ambiente ativo
                                </div>

                                <div class="inline-flex items-center gap-2 rounded-full border bg-background/75 px-3 py-1.5 text-xs text-muted-foreground">
                                    <ShieldCheck class="h-3.5 w-3.5 text-primary" />
                                    Acesso validado
                                </div>

                                <div class="inline-flex items-center gap-2 rounded-full border bg-background/75 px-3 py-1.5 text-xs text-muted-foreground">
                                    <Gauge class="h-3.5 w-3.5 text-primary" />
                                    Estrutura disponível
                                </div>
                            </div>
                        </div>

                        <article
                            class="w-full xl:w-[320px] rounded-2xl border bg-background/85 p-4 shadow-sm backdrop-blur"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-[11px] font-medium uppercase tracking-[0.16em] text-muted-foreground">
                                        Ambiente
                                    </p>

                                    <h2 class="mt-2 text-xl font-semibold text-emerald-600">
                                        Online
                                    </h2>
                                </div>

                                <div class="rounded-2xl bg-emerald-500/10 p-2.5 text-emerald-600">
                                    <Activity class="h-5 w-5" />
                                </div>
                            </div>

                            <p class="mt-4 text-xs leading-5 text-muted-foreground">
                                Operação normal
                            </p>
                        </article>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        <article
                            v-for="item in overviewCards.filter((item) => item.title !== 'Ambiente')"
                            :key="item.title"
                            class="rounded-2xl border bg-background/80 p-5 shadow-sm backdrop-blur"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-sm text-muted-foreground">
                                        {{ item.title }}
                                    </p>

                                    <h2 class="mt-2 text-xl font-semibold text-foreground">
                                        {{ item.value }}
                                    </h2>
                                </div>

                                <div class="rounded-2xl bg-primary/10 p-2.5 text-primary">
                                    <component :is="item.icon" class="h-5 w-5" />
                                </div>
                            </div>

                            <p class="mt-4 text-xs leading-5 text-muted-foreground">
                                {{ item.helper }}
                            </p>
                        </article>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-[1.3fr_0.7fr]">
                <div class="rounded-3xl border bg-card/50 p-6 shadow-sm md:p-8">
                    <div class="mb-6 flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-xl font-semibold tracking-tight">
                                Acessos principais
                            </h3>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Áreas centrais do ambiente.
                            </p>
                        </div>

                        <div class="rounded-full border px-3 py-1 text-xs font-medium text-muted-foreground">
                            Menu rápido
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <article
                            v-for="item in quickAccessItems"
                            :key="item.title"
                            class="group rounded-2xl border bg-background/70 p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-primary/30 hover:bg-background hover:shadow-md"
                        >
                            <div class="mb-4 flex items-center justify-between">
                                <div class="rounded-2xl bg-primary/10 p-3 text-primary transition group-hover:bg-primary/15">
                                    <component :is="item.icon" class="h-5 w-5" />
                                </div>

                                <span class="rounded-full border px-2.5 py-1 text-[10px] font-medium uppercase tracking-[0.14em] text-muted-foreground">
                                    {{ item.badge }}
                                </span>
                            </div>

                            <h4 class="text-base font-semibold">
                                {{ item.title }}
                            </h4>

                            <p class="mt-2 text-sm leading-6 text-muted-foreground">
                                {{ item.description }}
                            </p>
                        </article>
                    </div>
                </div>

                <div class="rounded-3xl border bg-card/50 p-6 shadow-sm md:p-8">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold tracking-tight">
                                Status do ambiente
                            </h3>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Visão resumida.
                            </p>
                        </div>

                        <div class="rounded-2xl bg-primary/10 p-2.5 text-primary">
                            <ChartNoAxesCombined class="h-5 w-5" />
                        </div>
                    </div>

                    <div class="mt-6 space-y-3">
                        <div
                            v-for="item in statusItems"
                            :key="item.label"
                            class="rounded-2xl border bg-background/70 p-4"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-sm font-medium text-foreground">
                                    {{ item.label }}
                                </p>
                                <span class="text-sm font-semibold" :class="item.tone">
                                    {{ item.value }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 rounded-2xl border bg-background/80 p-4">
                        <p class="text-sm font-medium text-foreground">
                            Situação atual
                        </p>
                        <p class="mt-2 text-sm leading-6 text-muted-foreground">
                            Ambiente disponível para operação.
                        </p>
                    </div>
                </div>
            </section>

            <section class="grid gap-6 xl:grid-cols-[0.95fr_1.05fr]">
                <div class="rounded-3xl border bg-card/50 p-6 shadow-sm md:p-8">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold tracking-tight">
                                Destaques
                            </h3>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Informações institucionais do ambiente.
                            </p>
                        </div>

                        <div class="rounded-full border px-3 py-1 text-xs font-medium text-muted-foreground">
                            Visão geral
                        </div>
                    </div>

                    <div class="mt-6 space-y-4">
                        <article
                            v-for="item in highlights"
                            :key="item.title"
                            class="rounded-2xl border bg-background/70 p-5"
                        >
                            <div class="flex items-start gap-4">
                                <div class="rounded-2xl bg-primary/10 p-3 text-primary">
                                    <component :is="item.icon" class="h-5 w-5" />
                                </div>

                                <div>
                                    <h4 class="text-sm font-semibold text-foreground">
                                        {{ item.title }}
                                    </h4>
                                    <p class="mt-1 text-sm leading-6 text-muted-foreground">
                                        {{ item.description }}
                                    </p>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>

                <div class="rounded-3xl border bg-card/50 p-6 shadow-sm md:p-8">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold tracking-tight">
                                Expansões
                            </h3>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Recursos previstos para evolução do ambiente.
                            </p>
                        </div>

                        <div class="rounded-2xl bg-primary/10 p-2.5 text-primary">
                            <Gauge class="h-5 w-5" />
                        </div>
                    </div>

                    <div class="mt-6 grid gap-3 sm:grid-cols-2">
                        <div
                            v-for="item in roadmapItems"
                            :key="item"
                            class="rounded-2xl border bg-background/70 p-4"
                        >
                            <div class="flex items-start gap-3">
                                <div class="mt-0.5 rounded-full bg-primary/10 p-1.5 text-primary">
                                    <CircleCheckBig class="h-3.5 w-3.5" />
                                </div>

                                <p class="text-sm leading-6 text-foreground">
                                    {{ item }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 rounded-2xl border bg-background/80 p-5">
                        <div class="flex items-start gap-4">
                            <div class="rounded-2xl bg-emerald-500/10 p-3 text-emerald-600">
                                <Activity class="h-5 w-5" />
                            </div>

                            <div>
                                <p class="text-sm font-semibold text-foreground">
                                    Ambiente operacional
                                </p>
                                <p class="mt-1 text-sm leading-6 text-muted-foreground">
                                    Estrutura disponível para acompanhamento e navegação.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
