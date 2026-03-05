<script setup lang="ts">
</script>

<template>
    <div class="flex items-center">
        <!-- Logo para sidebar ABERTA -->
        <img
            src="/images/brand/logo-dashboard.png"
            alt="Draxel"
            class="h-15 w-auto object-contain group-data-[collapsible=icon]:hidden"
        />

        <!-- Logo para sidebar FECHADA (modo icon) -->
        <img
            src="/images/brand/logo-full.png"
            alt="Draxel"
            class="hidden h-10 w-10 object-contain group-data-[collapsible=icon]:block"
        />
    </div>
</template>
