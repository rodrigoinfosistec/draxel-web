<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue'
import { dashboard } from '@/routes'
import type { BreadcrumbItem } from '@/types'
import { Head, usePage } from '@inertiajs/vue3'
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

const page = usePage<{
    auth: {
        user: User
    }
    tenant: Tenant | null
    currentCompanyId: number | null
}>()

const user = computed(() => page.props.auth.user)
const tenant = computed(() => page.props.tenant)
const currentCompanyId = computed(() => page.props.currentCompanyId)
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">

            <!-- Header -->
            <div class="flex flex-col gap-1">
                <h1 class="text-2xl font-semibold tracking-tight">
                    Bem-vindo, {{ user?.name }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    Tenant: <strong>{{ tenant?.name }}</strong>
                    • Empresa ativa ID: <strong>{{ currentCompanyId }}</strong>
                </p>
            </div>

            <!-- KPI Cards -->
            <div class="grid gap-6 md:grid-cols-3">
                <div class="rounded-xl border bg-card p-6 shadow-sm">
                    <p class="text-sm text-muted-foreground">Usuários</p>
                    <h2 class="text-3xl font-bold">—</h2>
                    <p class="text-xs text-muted-foreground mt-2">
                        Métrica futura
                    </p>
                </div>

                <div class="rounded-xl border bg-card p-6 shadow-sm">
                    <p class="text-sm text-muted-foreground">Empresas</p>
                    <h2 class="text-3xl font-bold">—</h2>
                    <p class="text-xs text-muted-foreground mt-2">
                        Métrica futura
                    </p>
                </div>

                <div class="rounded-xl border bg-card p-6 shadow-sm">
                    <p class="text-sm text-muted-foreground">Status do Sistema</p>
                    <h2 class="text-3xl font-bold text-green-600">
                        Online
                    </h2>
                    <p class="text-xs text-muted-foreground mt-2">
                        Infraestrutura operacional
                    </p>
                </div>
            </div>

            <!-- Área principal -->
            <div class="rounded-xl border bg-card p-8 shadow-sm">
                <h3 class="text-lg font-medium mb-4">
                    Área de trabalho
                </h3>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-lg border p-4 hover:bg-muted/40 transition">
                        <h4 class="font-medium">Módulos</h4>
                        <p class="text-sm text-muted-foreground">
                            Ativação e gerenciamento de módulos por tenant.
                        </p>
                    </div>

                    <div class="rounded-lg border p-4 hover:bg-muted/40 transition">
                        <h4 class="font-medium">Auditoria</h4>
                        <p class="text-sm text-muted-foreground">
                            Histórico de ações e rastreabilidade.
                        </p>
                    </div>

                    <div class="rounded-lg border p-4 hover:bg-muted/40 transition">
                        <h4 class="font-medium">Relatórios</h4>
                        <p class="text-sm text-muted-foreground">
                            Indicadores estratégicos e métricas.
                        </p>
                    </div>

                    <div class="rounded-lg border p-4 hover:bg-muted/40 transition">
                        <h4 class="font-medium">Configurações</h4>
                        <p class="text-sm text-muted-foreground">
                            Ajustes de tenant e empresa.
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
