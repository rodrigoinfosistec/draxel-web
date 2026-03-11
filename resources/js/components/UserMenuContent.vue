<script setup lang="ts">
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import UserInfo from '@/components/UserInfo.vue';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
import type { User } from '@/types';
import { Link, router, usePage } from '@inertiajs/vue3';
import { Activity, LogOut, Settings, Shield } from 'lucide-vue-next';
import { computed } from 'vue';

type Props = {
    user: User;
};

type PageProps = {
    tenant?: {
        slug?: string;
    } | null;
};

defineProps<Props>();

const page = usePage<PageProps>();

const showAdminAccess = computed(() => {
    return page.props.tenant?.slug === 'dpanel';
});

const handleLogout = () => {
    router.flushAll();
};
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-email="true" />
        </div>
    </DropdownMenuLabel>

    <DropdownMenuSeparator />

    <DropdownMenuGroup v-if="showAdminAccess">
        <DropdownMenuItem as-child>
            <a
                href="/admin"
                target="_blank"
                rel="noopener noreferrer"
                class="block w-full cursor-pointer"
            >
                <Shield class="mr-2 h-4 w-4" />
                Painel Global
            </a>
        </DropdownMenuItem>
    </DropdownMenuGroup>

    <DropdownMenuSeparator v-if="showAdminAccess" />

    <DropdownMenuGroup v-if="showAdminAccess">
        <DropdownMenuItem as-child>
            <a
                href="/horizon"
                target="_blank"
                rel="noopener noreferrer"
                class="block w-full cursor-pointer"
            >
                <Activity class="mr-2 h-4 w-4" />
                Horizon
            </a>
        </DropdownMenuItem>
    </DropdownMenuGroup>

    <DropdownMenuSeparator v-if="showAdminAccess" />

    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <Link class="block w-full cursor-pointer" :href="edit()" prefetch>
                <Settings class="mr-2 h-4 w-4" />
                Configurações
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>

    <DropdownMenuSeparator />

    <DropdownMenuItem :as-child="true">
        <Link
            class="block w-full cursor-pointer"
            :href="logout()"
            @click="handleLogout"
            as="button"
            data-test="logout-button"
        >
            <LogOut class="mr-2 h-4 w-4" />
            Sair
        </Link>
    </DropdownMenuItem>
</template>
