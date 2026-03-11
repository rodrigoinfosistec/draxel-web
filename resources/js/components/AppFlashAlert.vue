<script setup lang="ts">
import { usePage } from '@inertiajs/vue3'
import Swal from 'sweetalert2'
import { computed, watch } from 'vue'

type FlashAlert = {
    type?: 'success' | 'error' | 'warning' | 'info' | 'question'
    title?: string
    text?: string | null
    toast?: boolean
    position?:
        | 'top'
        | 'top-start'
        | 'top-end'
        | 'center'
        | 'center-start'
        | 'center-end'
        | 'bottom'
        | 'bottom-start'
        | 'bottom-end'
    timer?: number
    confirmButtonText?: string
} | null

const page = usePage<{
    flash: {
        alert: FlashAlert
    }
}>()

const alert = computed(() => page.props.flash?.alert ?? null)

watch(
    alert,
    (value) => {
        if (!value) {
            return
        }

        Swal.fire({
            icon: value.type ?? 'success',
            title: value.title ?? '',
            text: value.text ?? '',
            toast: value.toast ?? true,
            position: value.position ?? 'top-end',
            timer: value.timer ?? 2500,
            showConfirmButton: value.toast ? false : true,
            confirmButtonText: value.confirmButtonText ?? 'OK',
            timerProgressBar: value.toast ?? true,
        })
    },
    { immediate: true },
)
</script>

<template>
    <slot />
</template>
