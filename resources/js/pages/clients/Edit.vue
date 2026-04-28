<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { useConfirm } from '@/composables/useConfirm'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft, UsersRound } from 'lucide-vue-next'

type ClientFormData = {
    id: number
    name: string
    document: string | null
    email: string | null
    phone: string | null
    address: string | null
    notes: string | null
}

const props = defineProps<{
    client: ClientFormData
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Clientes', href: '/clients' },
    { title: 'Editar cliente', href: `/clients/${props.client.id}/edit` },
]

const form = useForm({
    name: props.client.name,
    document: props.client.document ?? '',
    email: props.client.email ?? '',
    phone: props.client.phone ?? '',
    address: props.client.address ?? '',
    notes: props.client.notes ?? '',
})

function submit() {
    form.put(`/clients/${props.client.id}`)
}

async function destroy() {
    const confirmed = await useConfirm({
        title: 'Excluir cliente?',
        text: 'Essa ação removerá o cliente permanentemente.',
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar',
        icon: 'warning',
    })

    if (!confirmed) {
        return
    }

    form.delete(`/clients/${props.client.id}`)
}
</script>

<template>
    <Head title="Editar cliente" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <div
                class="relative overflow-hidden rounded-2xl border bg-card/40 p-4 shadow-sm backdrop-blur-[1px] sm:p-5"
            >
                <div
                    class="absolute inset-0"
                    style="background: linear-gradient(to bottom right, var(--company-color-soft), transparent, transparent);"
                />

                <div class="relative flex items-start justify-between gap-4">
                    <Heading
                        title="Editar cliente"
                        description="Atualize os dados do cliente."
                        :icon="UsersRound"
                    />

                    <Link
                        href="/clients"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border bg-background px-4 py-2 text-sm font-medium transition hover:bg-muted"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        <span>Voltar</span>
                    </Link>
                </div>
            </div>

            <form
                class="space-y-8 rounded-2xl border bg-card/50 p-5 shadow-sm sm:p-6"
                @submit.prevent="submit"
            >
                <section class="space-y-4">
                    <div>
                        <h2 class="text-sm font-semibold tracking-tight">Dados principais</h2>
                        <p class="text-sm text-muted-foreground">
                            Atualize os dados básicos do cliente.
                        </p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="grid gap-2 md:col-span-2">
                            <Label for="name">Nome</Label>
                            <Input id="name" v-model="form.name" />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="document">Documento</Label>
                            <Input id="document" v-model="form.document" />
                            <InputError :message="form.errors.document" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="phone">Telefone</Label>
                            <Input id="phone" v-model="form.phone" />
                            <InputError :message="form.errors.phone" />
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <Label for="email">E-mail</Label>
                            <Input id="email" v-model="form.email" type="email" />
                            <InputError :message="form.errors.email" />
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <Label for="address">Endereço</Label>
                            <textarea
                                id="address"
                                v-model="form.address"
                                rows="3"
                                class="flex w-full rounded-md border bg-background px-3 py-2 text-sm"
                            />
                            <InputError :message="form.errors.address" />
                        </div>

                        <div class="grid gap-2 md:col-span-2">
                            <Label for="notes">Observações</Label>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="4"
                                class="flex w-full rounded-md border bg-background px-3 py-2 text-sm"
                            />
                            <InputError :message="form.errors.notes" />
                        </div>
                    </div>
                </section>

                <div
                    class="flex flex-col-reverse gap-3 border-t pt-6 lg:flex-row lg:items-center lg:justify-between"
                >
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Can permission="clients.delete">
                            <Button
                                type="button"
                                variant="destructive"
                                :disabled="form.processing"
                                class="sm:min-w-35"
                                @click="destroy"
                            >
                                Excluir
                            </Button>
                        </Can>
                    </div>

                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center">
                        <Link
                            href="/clients"
                            class="inline-flex items-center justify-center rounded-lg border px-4 py-2 text-sm font-medium transition hover:bg-muted"
                        >
                            Cancelar
                        </Link>

                        <Can permission="clients.update">
                            <Button :disabled="form.processing" class="sm:min-w-35">
                                {{ form.processing ? 'Salvando...' : 'Salvar' }}
                            </Button>
                        </Can>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
