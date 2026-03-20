<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft, BookUser, Plus, Trash2 } from 'lucide-vue-next'

type ItemTypeOption = {
    value: string
    label: string
}

const props = defineProps<{
    itemTypes: ItemTypeOption[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Agenda', href: '/contacts' },
    { title: 'Novo contato', href: '/contacts/create' },
]

const form = useForm({
    name: '',
    description: '',
    items: [
        {
            type: '',
            value: '',
            label: '',
            sort_order: 0,
        },
    ],
})

function addItem() {
    form.items.push({
        type: '',
        value: '',
        label: '',
        sort_order: form.items.length,
    })
}

function removeItem(index: number) {
    if (form.items.length === 1) {
        return
    }

    form.items.splice(index, 1)
    reindexItems()
}

function reindexItems() {
    form.items = form.items.map((item, index) => ({
        ...item,
        sort_order: index,
    }))
}

function submit() {
    reindexItems()
    form.post('/contacts')
}
</script>

<template>
    <Head title="Novo contato" />

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
                        title="Novo contato"
                        description="Cadastre um novo contato na agenda."
                        :icon="BookUser"
                    />

                    <Link
                        href="/contacts"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border bg-background px-4 py-2 text-sm font-medium transition hover:bg-muted"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        <span>Voltar</span>
                    </Link>
                </div>
            </div>

            <form class="space-y-8 rounded-2xl border bg-card/50 p-5 shadow-sm sm:p-6" @submit.prevent="submit">
                <section class="space-y-4">
                    <div>
                        <h2 class="text-sm font-semibold tracking-tight">Dados principais</h2>
                        <p class="text-sm text-muted-foreground">
                            Preencha os dados básicos do contato.
                        </p>
                    </div>

                    <div class="grid gap-4">
                        <div class="grid gap-2">
                            <Label for="name">Nome</Label>
                            <Input id="name" v-model="form.name" />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="description">Descrição</Label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                class="flex w-full rounded-md border bg-background px-3 py-2 text-sm"
                            />
                            <InputError :message="form.errors.description" />
                        </div>
                    </div>
                </section>

                <section class="space-y-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="text-sm font-semibold tracking-tight">Meios de contato</h2>
                            <p class="text-sm text-muted-foreground">
                                Adicione um ou mais meios de contato.
                            </p>
                        </div>

                        <Button type="button" variant="outline" @click="addItem">
                            <Plus class="mr-2 h-4 w-4" />
                            Adicionar contato
                        </Button>
                    </div>

                    <div class="space-y-4">
                        <div
                            v-for="(item, index) in form.items"
                            :key="index"
                            class="rounded-xl border bg-background p-4"
                        >
                            <div class="mb-4 flex items-center justify-between">
                                <div class="text-sm font-medium">
                                    Contato {{ index + 1 }}
                                </div>

                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    :disabled="form.items.length === 1"
                                    @click="removeItem(index)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>

                            <div class="grid gap-4 md:grid-cols-3">
                                <div class="grid gap-2">
                                    <Label :for="`type_${index}`">Tipo</Label>
                                    <select
                                        :id="`type_${index}`"
                                        v-model="form.items[index].type"
                                        class="flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm"
                                    >
                                        <option value="">Selecione</option>
                                        <option
                                            v-for="itemType in itemTypes"
                                            :key="itemType.value"
                                            :value="itemType.value"
                                        >
                                            {{ itemType.label }}
                                        </option>
                                    </select>
                                    <InputError :message="form.errors[`items.${index}.type`]" />
                                </div>

                                <div class="grid gap-2">
                                    <Label :for="`value_${index}`">Valor</Label>
                                    <Input :id="`value_${index}`" v-model="form.items[index].value" />
                                    <InputError :message="form.errors[`items.${index}.value`]" />
                                </div>

                                <div class="grid gap-2">
                                    <Label :for="`label_${index}`">Rótulo</Label>
                                    <Input :id="`label_${index}`" v-model="form.items[index].label" />
                                    <InputError :message="form.errors[`items.${index}.label`]" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <InputError :message="form.errors.items" />
                </section>

                <div class="flex flex-col-reverse gap-3 border-t pt-6 sm:flex-row sm:items-center sm:justify-end">
                    <Link
                        href="/contacts"
                        class="inline-flex items-center justify-center rounded-lg border px-4 py-2 text-sm font-medium transition hover:bg-muted"
                    >
                        Cancelar
                    </Link>

                    <Can permission="contacts.create">
                        <Button :disabled="form.processing" class="sm:min-w-[140px]">
                            {{ form.processing ? 'Salvando...' : 'Salvar' }}
                        </Button>
                    </Can>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
