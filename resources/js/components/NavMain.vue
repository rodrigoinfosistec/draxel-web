<script setup lang="ts">
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import type { NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';

defineProps<{
    items: NavItem[]
}>()

const { isCurrentUrl } = useCurrentUrl()
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Navegação</SidebarGroupLabel>

        <SidebarMenu>
            <SidebarMenuItem v-for="item in items" :key="item.title">
                <SidebarMenuButton
                    as-child
                    :is-active="isCurrentUrl(item.href)"
                    :tooltip="item.title"
                    :class="isCurrentUrl(item.href)
                        ? 'bg-[var(--company-color-soft)] border-l-2 border-[var(--company-color)]'
                        : ''"
                >
                    <Link
                        :href="item.href"
                        class="flex items-center gap-2 transition-colors"
                        :class="[
                            'text-[var(--company-color,var(--sidebar-foreground))]',
                            '[&_svg]:text-[var(--company-color,var(--sidebar-foreground))]',
                            isCurrentUrl(item.href)
                                ? 'font-semibold'
                                : 'opacity-90 hover:opacity-100 hover:bg-[var(--company-color-soft)]/60'
                        ]"
                    >
                        <component :is="item.icon" />

                        <span
                            class="text-[var(--company-color,var(--sidebar-foreground))]"
                            :class="isCurrentUrl(item.href) ? 'font-semibold' : ''"
                        >
                            {{ item.title }}
                        </span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
        </SidebarMenu>
    </SidebarGroup>
</template>
