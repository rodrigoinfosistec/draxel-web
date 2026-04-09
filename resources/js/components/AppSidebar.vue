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
import { Link, usePage } from '@inertiajs/vue3'
import {
    AlarmClock,
    BookUser,
    Box,
    Boxes,
    BriefcaseBusiness,
    Building2,
    CalendarDays,
    Factory,
    Headset,
    IdCard,
    LayoutGrid,
    Settings2,
    ShieldCheck,
    Users,
} from 'lucide-vue-next'

const page = usePage<{
    auth: {
        user: {
            id: number
            name: string
            email: string
        } | null
        permissions: string[]
    }
}>()

function can(permission: string): boolean {
    return page.props.auth.permissions.includes(permission)
}

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },

    ...(can('contacts.viewAny')
        ? [
              {
                  title: 'Agenda',
                  href: '/contacts',
                  icon: BookUser,
              },
          ]
        : []),

    ...(can('employees.viewAny')
        ? [
              {
                  title: 'Funcionários',
                  href: '/employees',
                  icon: IdCard,
              },
          ]
        : []),

    ...(can('positions.viewAny')
        ? [
              {
                  title: 'Cargos de funcionário',
                  href: '/positions',
                  icon: BriefcaseBusiness,
              },
          ]
        : []),

    ...(can('departments.viewAny')
        ? [
              {
                  title: 'Departamentos',
                  href: '/departments',
                  icon: Building2,
              },
          ]
        : []),

    ...(can('products.dashboard')
        ? [
              {
                  title: 'Produtos',
                  href: '/products/dashboard',
                  icon: Box,
              },
          ]
        : []),

    ...(can('inventory.viewDashboard')
        ? [
              {
                  title: 'Estoque',
                  href: '/inventory',
                  icon: Boxes,
              },
          ]
        : []),

    ...(can('production.viewDashboard')
        ? [
              {
                  title: 'Produção',
                  href: '/production',
                  icon: Factory,
              },
          ]
        : []),

    ...(can('worktime.viewAny')
        ? [
              {
                  title: 'Ponto',
                  href: '/worktime/',
                  icon: AlarmClock,
              },
          ]
        : []),

    ...(can('holidays.viewAny')
        ? [
              {
                  title: 'Feriados',
                  href: '/holidays',
                  icon: CalendarDays,
              },
          ]
        : []),

    ...(can('users.viewAny')
        ? [
              {
                  title: 'Usuários',
                  href: '/users',
                  icon: Users,
              },
          ]
        : []),

    ...(can('roles.viewAny')
        ? [
              {
                  title: 'Funções de usuário',
                  href: '/roles',
                  icon: ShieldCheck,
              },
          ]
        : []),
]

const footerNavItems: NavItem[] = [
    ...(can('support.viewAny')
        ? [
              {
                  title: 'Suporte',
                  href: '/support-tickets',
                  icon: Headset,
              },
          ]
        : []),

    ...(can('parameters.view')
        ? [
              {
                  title: 'Parâmetros',
                  href: '/parameters',
                  icon: Settings2,
              },
          ]
        : []),
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

            <div class="mt-2 p-2">
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
