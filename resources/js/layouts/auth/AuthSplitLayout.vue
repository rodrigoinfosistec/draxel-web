<script setup lang="ts">
import { home } from '@/routes'
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()

const name = computed(() => page.props.name as string)
const tenant = computed(() => page.props.tenant as { name?: string } | null)

defineProps<{
    title?: string
    description?: string
}>()
</script>

<template>
    <div class="relative grid min-h-dvh lg:grid-cols-2">
        <!-- LADO ESQUERDO -->
        <div class="relative hidden overflow-hidden lg:flex">
            <!-- Gradiente base -->
            <div class="absolute inset-0 bg-gradient-to-br from-zinc-950 via-zinc-900 to-zinc-800" />

            <!-- Grid overlay -->
            <div
                class="absolute inset-0 opacity-[0.08]
                [background-image:linear-gradient(to_right,rgba(255,255,255,0.25)_1px,transparent_1px),linear-gradient(to_bottom,rgba(255,255,255,0.25)_1px,transparent_1px)]
                [background-size:48px_48px]"
            />

            <!-- Blobs -->
            <div class="absolute -top-24 -left-24 h-72 w-72 rounded-full bg-white/10 blur-3xl" />
            <div class="absolute -bottom-24 -right-24 h-72 w-72 rounded-full bg-white/5 blur-3xl" />

            <!-- Radial highlights -->
            <div
                class="absolute inset-0 opacity-40
                [background:radial-gradient(circle_at_20%_10%,rgba(255,255,255,0.12),transparent_40%),radial-gradient(circle_at_80%_60%,rgba(255,255,255,0.08),transparent_45%)]"
            />

            <div class="relative flex w-full flex-col p-10">
                <Link
                    :href="home()"
                    class="flex items-center gap-2 text-lg font-semibold text-white"
                >
                    <img
                        src="/images/brand/logo-horizontal.png"
                        alt="Draxel"
                        class="h-30 w-auto"
                    />
                </Link>

                <div class="mt-auto max-w-md text-white/90">
                    <div class="text-sm font-medium text-white/80">
                        {{ tenant?.name }}
                    </div>

                    <div class="mt-2 text-3xl font-semibold leading-tight tracking-tight">
                        Gestão simples, controle e rastreabilidade.
                    </div>

                    <div class="mt-3 text-sm leading-relaxed text-white/75">
                        Acesse sua conta para continuar. Segurança, auditoria e
                        multiempresa já integrados.
                    </div>
                </div>
            </div>
        </div>

        <!-- LADO DIREITO -->
        <div class="relative flex items-center justify-center overflow-hidden px-6 py-10 lg:px-10">
            <!-- Background imagem -->
            <div
                class="absolute inset-0 bg-cover bg-center"
                style="background-image: url('/images/backgrounds/background.jpg')"
            />
            <div class="absolute inset-0 bg-background/10" />

            <div class="relative w-full max-w-md">
                <div class="rounded-2xl border border-border/60 bg-background/85 p-6 shadow-sm">
                    <!-- Logo mobile -->
                    <div class="mb-8 flex justify-center lg:hidden">
                        <Link :href="home()" class="flex items-center gap-2">
                            <img
                                src="/images/brand/logo-horizontal.png"
                                alt="Draxel"
                                class="h-20 w-auto"
                            />
                        </Link>
                    </div>

                    <!-- Title -->
                    <div class="mb-6 space-y-2">
                        <h1 v-if="title" class="text-xl font-semibold tracking-tight">
                            {{ title }}
                        </h1>

                        <p v-if="description" class="text-sm text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>

                    <slot />
                </div>

                <!-- Footer -->
                <div class="mt-6 text-center text-xs text-muted-foreground">
                    © {{ new Date().getFullYear() }} {{ name }}. Todos os direitos reservados.
                </div>
            </div>
        </div>
    </div>
</template>
