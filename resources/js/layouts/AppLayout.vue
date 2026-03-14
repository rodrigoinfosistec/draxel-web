<script setup lang="ts">
import AppFlashAlert from '@/components/AppFlashAlert.vue'
import { useCompanyTheme } from '@/composables/useCompanyTheme'
import AppLayout from '@/layouts/app/AppSidebarLayout.vue'
import type { BreadcrumbItem } from '@/types'

type Props = {
    breadcrumbs?: BreadcrumbItem[]
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
})

useCompanyTheme()

const currentYear = new Date().getFullYear()
const appVersion = '1.0.0'
</script>

<template>
    <AppFlashAlert />

    <div class="relative min-h-screen">
        <AppLayout :breadcrumbs="breadcrumbs">
            <div
                class="pointer-events-none fixed inset-0 z-[1] flex items-center justify-center"
                aria-hidden="true"
            >
                <img
                    src="/images/brand/logo-full.png"
                    alt=""
                    class="h-auto w-[500px] max-w-[80vw] opacity-10"
                >
            </div>

            <div class="relative z-[2] flex min-h-[calc(100vh-4rem)] flex-col">
                <div class="flex-1">
                    <slot />
                </div>

                <footer class="mt-8 border-t border-border/60 px-6 py-4">
                    <div class="flex flex-col gap-2 text-xs text-muted-foreground md:flex-row md:items-center md:justify-between">
                        <p>
                            © {{ currentYear }} Draxel. Todos os direitos reservados.
                        </p>

                        <div class="flex items-center gap-3">
                            <span>Ambiente operacional</span>
                            <span class="h-1 w-1 rounded-full bg-muted-foreground/60" />
                            <span>Versão {{ appVersion }}</span>
                        </div>
                    </div>
                </footer>
            </div>
        </AppLayout>
    </div>
</template>
