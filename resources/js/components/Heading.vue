<script setup lang="ts">
import type { Component } from 'vue'

type Props = {
    title: string
    description?: string
    variant?: 'default' | 'small'
    icon?: Component | null
}

withDefaults(defineProps<Props>(), {
    variant: 'default',
    icon: null,
})
</script>

<template>
    <header :class="variant === 'small' ? '' : 'mb-8 space-y-0.5'">
        <div class="flex items-center gap-2">
            <component
                :is="icon"
                v-if="icon"
                :class="
                    variant === 'small'
                        ? 'h-4 w-4 text-[var(--company-color,var(--foreground))]'
                        : 'h-5 w-5 text-[var(--company-color,var(--foreground))]'
                "
            />

            <h2
                :class="
                    variant === 'small'
                        ? 'mb-0.5 text-base font-medium text-[var(--company-color,var(--foreground))]'
                        : 'text-xl font-semibold tracking-tight text-[var(--company-color,var(--foreground))]'
                "
            >
                {{ title }}
            </h2>
        </div>

        <p v-if="description" class="text-sm text-muted-foreground">
            {{ description }}
        </p>
    </header>
</template>
