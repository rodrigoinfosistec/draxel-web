<script setup lang="ts">
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { useConfirm } from '@/composables/useConfirm'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import { ArrowLeft, Building2 } from 'lucide-vue-next'

const props = defineProps<{
    supplier: {
        id: number
        name: string
        trade_name: string | null
        document: string
        state_registration: string | null
        municipal_registration: string | null
        email: string | null
        phone: string | null
        mobile: string | null
        zip_code: string | null
        street: string | null
        number: string | null
        complement: string | null
        district: string | null
        city: string | null
        state: string | null
        notes: string | null
        is_active: boolean
    }
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Fornecedores', href: '/suppliers' },
    { title: 'Editar fornecedor', href: `/suppliers/${props.supplier.id}/edit` },
]

const form = useForm({
    name: props.supplier.name,
    trade_name: props.supplier.trade_name ?? '',
    document: props.supplier.document,
    state_registration: props.supplier.state_registration ?? '',
    municipal_registration: props.supplier.municipal_registration ?? '',
    email: props.supplier.email ?? '',
    phone: props.supplier.phone ?? '',
    mobile: props.supplier.mobile ?? '',
    zip_code: props.supplier.zip_code ?? '',
    street: props.supplier.street ?? '',
    number: props.supplier.number ?? '',
    complement: props.supplier.complement ?? '',
    district: props.supplier.district ?? '',
    city: props.supplier.city ?? '',
    state: props.supplier.state ?? '',
    notes: props.supplier.notes ?? '',
    is_active: props.supplier.is_active,
})

function submit() {
    form.put(`/suppliers/${props.supplier.id}`, {
        preserveState: false,
        preserveScroll: false,
    })
}

async function destroy() {
    const confirmed = await useConfirm({
        title: 'Excluir fornecedor?',
        text: 'Essa ação removerá o fornecedor permanentemente.',
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar',
        icon: 'warning',
    })

    if (!confirmed) {
        return
    }

    router.delete(`/suppliers/${props.supplier.id}`)
}
</script>

<template>
    <Head title="Editar fornecedor" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <div class="relative overflow-hidden rounded-2xl border bg-card/40 p-4 shadow-sm backdrop-blur-[1px] sm:p-5">
                <div class="absolute inset-0" style="background: linear-gradient(to bottom right, var(--company-color-soft), transparent, transparent);" />

                <div class="relative flex items-start justify-between gap-4">
                    <Heading
                        title="Editar fornecedor"
                        description="Atualize os dados do fornecedor."
                        :icon="Building2"
                    />

                    <Link
                        href="/suppliers"
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
                        <h2 class="text-sm font-semibold tracking-tight">Identificação</h2>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="name">Razão social</Label>
                            <Input id="name" v-model="form.name" />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="trade_name">Nome fantasia</Label>
                            <Input id="trade_name" v-model="form.trade_name" />
                            <InputError :message="form.errors.trade_name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="document">Documento</Label>
                            <Input id="document" v-model="form.document" />
                            <InputError :message="form.errors.document" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="state_registration">Inscrição estadual</Label>
                            <Input id="state_registration" v-model="form.state_registration" />
                            <InputError :message="form.errors.state_registration" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="municipal_registration">Inscrição municipal</Label>
                            <Input id="municipal_registration" v-model="form.municipal_registration" />
                            <InputError :message="form.errors.municipal_registration" />
                        </div>
                    </div>
                </section>

                <section class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="grid gap-2 sm:col-span-3">
                            <Label for="email">E-mail</Label>
                            <Input id="email" v-model="form.email" />
                            <InputError :message="form.errors.email" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="phone">Telefone</Label>
                            <Input id="phone" v-model="form.phone" />
                            <InputError :message="form.errors.phone" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="mobile">Celular</Label>
                            <Input id="mobile" v-model="form.mobile" />
                            <InputError :message="form.errors.mobile" />
                        </div>
                    </div>
                </section>

                <section class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-4">
                        <div class="grid gap-2">
                            <Label for="zip_code">CEP</Label>
                            <Input id="zip_code" v-model="form.zip_code" />
                            <InputError :message="form.errors.zip_code" />
                        </div>

                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="street">Logradouro</Label>
                            <Input id="street" v-model="form.street" />
                            <InputError :message="form.errors.street" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="number">Número</Label>
                            <Input id="number" v-model="form.number" />
                            <InputError :message="form.errors.number" />
                        </div>

                        <div class="grid gap-2 sm:col-span-2">
                            <Label for="complement">Complemento</Label>
                            <Input id="complement" v-model="form.complement" />
                            <InputError :message="form.errors.complement" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="district">Bairro</Label>
                            <Input id="district" v-model="form.district" />
                            <InputError :message="form.errors.district" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="city">Cidade</Label>
                            <Input id="city" v-model="form.city" />
                            <InputError :message="form.errors.city" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="state">UF</Label>
                            <Input id="state" v-model="form.state" maxlength="2" />
                            <InputError :message="form.errors.state" />
                        </div>
                    </div>
                </section>

                <section class="space-y-4">
                    <div class="grid gap-4">
                        <div class="grid gap-2">
                            <Label for="notes">Observações</Label>
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="4"
                                class="flex w-full rounded-md border bg-background px-3 py-2 text-sm"
                            />
                            <InputError :message="form.errors.notes" />
                        </div>

                        <div class="rounded-xl border bg-background p-4">
                            <label class="flex cursor-pointer items-center justify-between gap-4 rounded-lg border bg-card px-4 py-3 transition hover:bg-muted/40">
                                <div class="space-y-1">
                                    <div class="text-sm font-medium">Fornecedor ativo</div>
                                    <div class="text-xs text-muted-foreground">
                                        {{
                                            form.is_active
                                                ? 'O fornecedor ficará disponível normalmente no sistema.'
                                                : 'O fornecedor ficará inativo no sistema.'
                                        }}
                                    </div>
                                </div>

                                <div class="relative inline-flex items-center">
                                    <input v-model="form.is_active" type="checkbox" class="peer sr-only" />
                                    <div class="h-6 w-11 rounded-full bg-muted transition peer-checked:bg-green-600" />
                                    <div class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5" />
                                </div>
                            </label>

                            <InputError :message="form.errors.is_active" class="mt-2" />
                        </div>
                    </div>
                </section>

                <div class="flex flex-col justify-between gap-3 sm:flex-row">
                    <Button type="button" variant="destructive" @click="destroy">
                        Excluir fornecedor
                    </Button>

                    <Button type="submit" :disabled="form.processing">
                        Salvar alterações
                    </Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
