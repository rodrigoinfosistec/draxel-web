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
import { ArrowLeft, IdCard } from 'lucide-vue-next'

type PositionOption = {
    id: number
    name: string
}

type EmployeeFormData = {
    id: number
    name: string
    cpf: string
    registration: string
    position_id: number | null
    is_active: boolean
}

const props = defineProps<{
    employee: EmployeeFormData
    positions: PositionOption[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Funcionários', href: '/employees' },
    { title: 'Editar funcionário', href: `/employees/${props.employee.id}/edit` },
]

function onlyDigits(value: string) {
    return value.replace(/\D/g, '')
}

function formatCpf(value: string) {
    const digits = onlyDigits(value).slice(0, 11)

    return digits
        .replace(/^(\d{3})(\d)/, '$1.$2')
        .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
        .replace(/\.(\d{3})(\d)/, '.$1-$2')
}

const form = useForm({
    name: props.employee.name,
    cpf: formatCpf(props.employee.cpf),
    registration: props.employee.registration,
    position_id: props.employee.position_id ?? '',
    is_active: props.employee.is_active,
})

function handleCpfInput(event: Event) {
    const target = event.target as HTMLInputElement
    form.cpf = formatCpf(target.value)
}

function submit() {
    form.transform((data) => ({
        ...data,
        cpf: onlyDigits(data.cpf),
    })).put(`/employees/${props.employee.id}`)
}

async function destroy() {
    const confirmed = await useConfirm({
        title: 'Excluir funcionário?',
        text: 'Essa ação removerá o funcionário permanentemente.',
        confirmButtonText: 'Sim, excluir',
        cancelButtonText: 'Cancelar',
        icon: 'warning',
    })

    if (!confirmed) {
        return
    }

    form.delete(`/employees/${props.employee.id}`)
}
</script>

<template>
    <Head title="Editar funcionário" />

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
                        title="Editar funcionário"
                        description="Atualize os dados do funcionário."
                        :icon="IdCard"
                    />

                    <Link
                        href="/employees"
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
                            Atualize os dados básicos do funcionário.
                        </p>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="grid gap-2 md:col-span-2">
                            <Label for="name">Nome</Label>
                            <Input id="name" v-model="form.name" />
                            <InputError :message="form.errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="cpf">CPF</Label>
                            <Input
                                id="cpf"
                                :model-value="form.cpf"
                                inputmode="numeric"
                                maxlength="14"
                                placeholder="000.000.000-00"
                                @input="handleCpfInput"
                            />
                            <InputError :message="form.errors.cpf" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="registration">Matrícula</Label>
                            <Input id="registration" v-model="form.registration" />
                            <InputError :message="form.errors.registration" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="position_id">Cargo</Label>
                            <select
                                id="position_id"
                                v-model="form.position_id"
                                class="flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm"
                            >
                                <option value="">Selecione</option>
                                <option
                                    v-for="position in positions"
                                    :key="position.id"
                                    :value="position.id"
                                >
                                    {{ position.name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.position_id" />
                        </div>
                    </div>
                </section>

                <section class="space-y-4">
                    <div>
                        <h2 class="text-sm font-semibold tracking-tight">Status</h2>
                        <p class="text-sm text-muted-foreground">
                            Controle se o funcionário permanece ativo.
                        </p>
                    </div>

                    <div class="rounded-xl border bg-background p-4">
                        <label
                            class="flex cursor-pointer items-center justify-between gap-4 rounded-lg border bg-card px-4 py-3 transition hover:bg-muted/40"
                        >
                            <div class="space-y-1">
                                <div class="text-sm font-medium">Funcionário ativo</div>
                                <div class="text-xs text-muted-foreground">
                                    {{
                                        form.is_active
                                            ? 'O funcionário ficará disponível normalmente no sistema.'
                                            : 'O funcionário ficará inativo no sistema.'
                                    }}
                                </div>
                            </div>

                            <div class="relative inline-flex items-center">
                                <input
                                    v-model="form.is_active"
                                    type="checkbox"
                                    class="peer sr-only"
                                />

                                <div
                                    class="h-6 w-11 rounded-full bg-muted transition peer-checked:bg-green-600"
                                />

                                <div
                                    class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"
                                />
                            </div>
                        </label>
                    </div>
                </section>

                <div
                    class="flex flex-col-reverse gap-3 border-t pt-6 lg:flex-row lg:items-center lg:justify-between"
                >
                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Can permission="employees.delete">
                            <Button
                                type="button"
                                variant="destructive"
                                :disabled="form.processing"
                                class="sm:min-w-[140px]"
                                @click="destroy"
                            >
                                Excluir
                            </Button>
                        </Can>
                    </div>

                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center">
                        <Link
                            href="/employees"
                            class="inline-flex items-center justify-center rounded-lg border px-4 py-2 text-sm font-medium transition hover:bg-muted"
                        >
                            Cancelar
                        </Link>

                        <Can permission="employees.update">
                            <Button :disabled="form.processing" class="sm:min-w-[140px]">
                                {{ form.processing ? 'Salvando...' : 'Salvar' }}
                            </Button>
                        </Can>
                    </div>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
