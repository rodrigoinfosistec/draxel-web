<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    permission?: string
    permissions?: string[]
    requireAll?: boolean
}>()

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

const userPermissions = computed(() => page.props.auth?.permissions ?? [])

const allowed = computed(() => {
    if (props.permission) {
        return userPermissions.value.includes(props.permission)
    }

    if (props.permissions && props.permissions.length > 0) {
        if (props.requireAll) {
            return props.permissions.every((permission) =>
                userPermissions.value.includes(permission),
            )
        }

        return props.permissions.some((permission) =>
            userPermissions.value.includes(permission),
        )
    }

    return true
})
</script>

<template>
    <slot v-if="allowed" />
</template>
