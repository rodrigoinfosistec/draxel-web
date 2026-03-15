<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { dashboard } from '@/routes'
import type { BreadcrumbItem } from '@/types'
import { Head, usePage } from '@inertiajs/vue3'
import { Activity, BarChart3, Building2, FolderKanban, Settings, ShieldCheck, Sparkles, Users } from 'lucide-vue-next'
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

const workspaceItems = [
    {
        title: 'Módulos',
        description: 'Controle da estrutura funcional do tenant, habilitando e acompanhando os recursos ativos do sistema.',
        icon: FolderKanban,
    },
    {
        title: 'Auditoria',
        description: 'Rastreabilidade completa das ações do sistema, fortalecendo segurança operacional e governança.',
        icon: ShieldCheck,
    },
    {
        title: 'Relatórios',
        description: 'Base para indicadores gerenciais, leitura estratégica do negócio e apoio à tomada de decisão.',
        icon: BarChart3,
    },
    {
        title: 'Configurações',
        description: 'Ajustes estruturais do ambiente, parâmetros da operação e personalização do tenant e empresa.',
        icon: Settings,
    },
]
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <section class="relative overflow-hidden rounded-3xl border bg-card/40 shadow-sm backdrop-blur-[1px]">
                <div
                    class="absolute inset-0"
                    style="background: linear-gradient(to bottom right, var(--company-color-soft), transparent, transparent);"
                />

                <div class="relative flex flex-col gap-6 p-6 md:p-8">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                        <div class="space-y-4">
                            <div class="inline-flex w-fit items-center gap-2 rounded-full border bg-background/80 px-3 py-1 text-xs font-medium text-muted-foreground backdrop-blur">
                                <Sparkles class="h-3.5 w-3.5" />
                                Ambiente operacional ativo
                            </div>

                            <div class="space-y-2">
                                <h1
                                    class="text-3xl font-semibold tracking-tight md:text-4xl"
                                    style="color: var(--company-color);"
                                >
                                    Bem-vindo, {{ user?.name }}
                                </h1>

                                <p class="max-w-2xl text-sm leading-6 text-muted-foreground md:text-base">
                                    Tenha uma visão central do ambiente atual, acompanhe o contexto operacional e acesse com mais clareza os recursos do sistema.
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-2 lg:min-w-[380px]">
                            <div class="rounded-2xl border bg-background/80 p-4 shadow-sm backdrop-blur">
                                <p class="text-[11px] font-medium uppercase tracking-[0.16em] text-muted-foreground">
                                    Tenant
                                </p>
                                <p class="mt-2 text-base font-semibold text-foreground">
                                    {{ tenant?.name ?? 'Não identificado' }}
                                </p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ tenant?.slug ? `@${tenant.slug}` : 'Sem slug disponível' }}
                                </p>
                            </div>

                            <div class="rounded-2xl border bg-background/80 p-4 shadow-sm backdrop-blur">
                                <p class="text-[11px] font-medium uppercase tracking-[0.16em] text-muted-foreground">
                                    Empresa ativa
                                </p>
                                <p class="mt-2 text-base font-semibold text-foreground">
                                    {{ currentCompany?.name ?? 'Nenhuma empresa selecionada' }}
                                </p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    Contexto operacional atual
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div class="rounded-2xl border bg-background/80 p-5 shadow-sm backdrop-blur">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="text-sm text-muted-foreground">
                                        Usuário conectado
                                    </p>
                                    <h2 class="mt-2 text-xl font-semibold">
                                        {{ user?.name }}
                                    </h2>
                                </div>

                                <div class="rounded-xl bg-primary/10 p-2 text-primary">
                                    <Users class="h-5 w-5" />
                                </div>
                            </div>

                            <p class="mt-4 text-xs leading-5 text-muted-foreground">
                                Sessão autenticada e pronta para navegação nas rotinas disponíveis do ambiente.
                            </p>
                        </div>

                        <div class="rounded-2xl border bg-background/80 p-5 shadow-sm backdrop-blur">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="text-sm text-muted-foreground">
                                        Contexto atual
                                    </p>
                                    <h2 class="mt-2 text-xl font-semibold">
                                        {{ currentCompany?.name ?? 'Aguardando contexto' }}
                                    </h2>
                                </div>

                                <div class="rounded-xl bg-primary/10 p-2 text-primary">
                                    <Building2 class="h-5 w-5" />
                                </div>
                            </div>

                            <p class="mt-4 text-xs leading-5 text-muted-foreground">
                                Os dados e fluxos acompanham a empresa atualmente selecionada.
                            </p>
                        </div>

                        <div class="rounded-2xl border bg-background/80 p-5 shadow-sm backdrop-blur">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="text-sm text-muted-foreground">
                                        Status do sistema
                                    </p>
                                    <h2 class="mt-2 text-xl font-semibold text-emerald-600">
                                        Online
                                    </h2>
                                </div>

                                <div class="rounded-xl bg-emerald-500/10 p-2 text-emerald-600">
                                    <Activity class="h-5 w-5" />
                                </div>
                            </div>

                            <p class="mt-4 text-xs leading-5 text-muted-foreground">
                                Ambiente disponível para uso, gestão e acompanhamento das rotinas do tenant.
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border bg-card/50 p-6 shadow-sm md:p-8">
                <div class="mb-6 flex flex-col gap-2">
                    <h3 class="text-xl font-semibold tracking-tight">
                        Centro de trabalho
                    </h3>
                    <p class="text-sm text-muted-foreground">
                        Acesso rápido às áreas mais relevantes do ambiente, com foco em operação, governança e evolução do sistema.
                    </p>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <article
                        v-for="item in workspaceItems"
                        :key="item.title"
                        class="group rounded-2xl border bg-background/70 p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-primary/30 hover:bg-background hover:shadow-md"
                    >
                        <div class="mb-4 flex items-center justify-between">
                            <div class="rounded-2xl bg-primary/10 p-3 text-primary transition group-hover:bg-primary/15">
                                <component :is="item.icon" class="h-5 w-5" />
                            </div>

                            <span class="text-[11px] font-medium uppercase tracking-[0.16em] text-muted-foreground">
                                Recurso
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
            </section>

            <section class="grid gap-4 xl:grid-cols-[1.2fr_0.8fr]">
                <div class="rounded-3xl border bg-card/50 p-6 shadow-sm md:p-8">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold tracking-tight">
                                Visão do ambiente
                            </h3>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Resumo institucional e operacional do contexto atual.
                            </p>
                        </div>

                        <div class="rounded-full border px-3 py-1 text-xs font-medium text-muted-foreground">
                            Dashboard
                        </div>
                    </div>

                    <div class="mt-6 grid gap-4 md:grid-cols-2">
                        <div class="rounded-2xl border bg-background/70 p-5">
                            <p class="text-xs font-medium uppercase tracking-[0.16em] text-muted-foreground">
                                Tenant
                            </p>
                            <p class="mt-2 text-lg font-semibold">
                                {{ tenant?.name ?? 'Não identificado' }}
                            </p>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Estrutura principal do ambiente em uso.
                            </p>
                        </div>

                        <div class="rounded-2xl border bg-background/70 p-5">
                            <p class="text-xs font-medium uppercase tracking-[0.16em] text-muted-foreground">
                                Empresa
                            </p>
                            <p class="mt-2 text-lg font-semibold">
                                {{ currentCompany?.name ?? 'Não selecionada' }}
                            </p>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Unidade operacional vinculada ao contexto atual.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border bg-card/50 p-6 shadow-sm md:p-8">
                    <h3 class="text-lg font-semibold tracking-tight">
                        Leitura rápida
                    </h3>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Síntese do estado atual do ambiente.
                    </p>

                    <div class="mt-6 space-y-3">
                        <div class="rounded-2xl border bg-background/70 p-4">
                            <p class="text-sm font-medium">
                                Operação ativa
                            </p>
                            <p class="mt-1 text-sm text-muted-foreground">
                                O ambiente está carregado e pronto para uso no tenant
                                <strong>{{ tenant?.name ?? 'Não identificado' }}</strong>.
                            </p>
                        </div>

                        <div class="rounded-2xl border bg-background/70 p-4">
                            <p class="text-sm font-medium">
                                Empresa em contexto
                            </p>
                            <p class="mt-1 text-sm text-muted-foreground">
                                As rotinas atuais consideram
                                <strong>{{ currentCompany?.name ?? 'nenhuma empresa selecionada' }}</strong>
                                como base operacional.
                            </p>
                        </div>

                        <div class="rounded-2xl border bg-background/70 p-4">
                            <p class="text-sm font-medium">
                                Acesso autenticado
                            </p>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Sessão de <strong>{{ user?.name }}</strong> ativa e pronta para navegação segura.
                            </p>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
