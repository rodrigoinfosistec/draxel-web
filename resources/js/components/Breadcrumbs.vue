<script setup lang="ts">
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';
import { Link } from '@inertiajs/vue3';

type Props = {
    breadcrumbs: BreadcrumbItemType[];
};

defineProps<Props>();
</script>

<template>
    <Breadcrumb>
        <BreadcrumbList>
            <template v-for="(item, index) in breadcrumbs" :key="index">
                <BreadcrumbItem>
                    <template v-if="index === breadcrumbs.length - 1">
                        <BreadcrumbPage
                            class="text-[var(--company-color)] font-semibold"
                        >
                            {{ item.title }}
                        </BreadcrumbPage>
                    </template>

                    <template v-else>
                        <BreadcrumbLink
                            as-child
                            class="transition-colors hover:text-[var(--company-color)]"
                        >
                            <Link :href="item.href">
                                {{ item.title }}
                            </Link>
                        </BreadcrumbLink>
                    </template>
                </BreadcrumbItem>

                <BreadcrumbSeparator
                    v-if="index !== breadcrumbs.length - 1"
                />
            </template>
        </BreadcrumbList>
    </Breadcrumb>
</template>
