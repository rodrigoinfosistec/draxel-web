<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editDefaultCompany } from '@/routes/default-company';
import { edit as editProfile } from '@/routes/profile';
import { show } from '@/routes/two-factor';
import { edit as editPassword } from '@/routes/user-password';
import type { NavItem } from '@/types';
import { Link } from '@inertiajs/vue3';
import { ArrowLeft, Settings } from 'lucide-vue-next';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Perfil',
        href: editProfile(),
    },
    {
        title: 'Senha',
        href: editPassword(),
    },
    {
        title: 'Autenticação de dois fatores',
        href: show(),
    },
    {
        title: 'Aparência',
        href: editAppearance(),
    },
    {
        title: 'Empresa padrão',
        href: editDefaultCompany(),
    },
];

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div class="px-4 py-6">
        <div
            class="relative mb-6 overflow-hidden rounded-2xl border bg-card/40 p-4 shadow-sm backdrop-blur-[1px] sm:p-5"
        >
            <div
                class="absolute inset-0"
                style="background: linear-gradient(to bottom right, var(--company-color-soft), transparent, transparent);"
            />

            <div class="relative flex items-start justify-between gap-4">
                <Heading
                    title="Configurações"
                    description="Gerencie seu perfil e as configurações da sua conta."
                    :icon="Settings"
                />

                <Link
                    href="/dashboard"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border bg-background px-4 py-2 text-sm font-medium transition hover:bg-muted"
                >
                    <ArrowLeft class="h-4 w-4" />
                    <span>Voltar</span>
                </Link>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row lg:space-x-12">
            <aside class="w-full max-w-xl lg:w-48">
                <nav
                    class="flex flex-col space-y-1 space-x-0"
                    aria-label="Configurações"
                >
                    <Button
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        variant="ghost"
                        :class="[
                            'w-full justify-start',
                            { 'bg-muted': isCurrentOrParentUrl(item.href) },
                        ]"
                        as-child
                    >
                        <Link :href="item.href">
                            {{ item.title }}
                        </Link>
                    </Button>
                </nav>
            </aside>

            <Separator class="my-6 lg:hidden" />

            <div class="flex-1 md:max-w-2xl">
                <section class="max-w-xl space-y-12">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
