<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue'
import CompanySwitcher from '@/components/CompanySwitcher.vue'
import NavFooter from '@/components/NavFooter.vue'
import NavMain from '@/components/NavMain.vue'
import NavUser from '@/components/NavUser.vue'
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar'
import { dashboard } from '@/routes'
import type { NavItem } from '@/types'
import { Link } from '@inertiajs/vue3'
import { LayoutGrid, ShieldCheck, Users } from 'lucide-vue-next'

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
]

const footerNavItems: NavItem[] = [
    {
        title: 'Usuários',
        href: '/users',
        icon: Users,
    },
    {
        title: 'Funções de usuário',
        href: '/roles',
        icon: ShieldCheck,
    },
]
</script>

<template>
    <Sidebar collapsible="icon" variant="inset" class="group/sidebar">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>

            <div class="p-2 mt-2">
                <CompanySwitcher />
            </div>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
