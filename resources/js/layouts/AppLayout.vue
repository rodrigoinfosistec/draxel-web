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
</script>

<template>
    <AppFlashAlert />

    <AppLayout :breadcrumbs="breadcrumbs">
        <slot />
    </AppLayout>
</template>
