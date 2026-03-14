<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import InputError from '@/components/InputError.vue'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, useForm } from '@inertiajs/vue3'
import { ArrowLeft, Headset } from 'lucide-vue-next'

type UserItem = {
    id: number
    name: string
    email: string
} | null

type MessageItem = {
    id: number
    message: string
    is_internal: boolean
    created_at: string | null
    user: UserItem
}

type TicketItem = {
    id: number
    code: string
    subject: string
    description: string
    status: string
    status_label: string
    is_open: boolean
    created_at: string | null
    creator: UserItem
    messages: MessageItem[]
}

type StatusOption = {
    value: string
    label: string
}

const props = defineProps<{
    ticket: TicketItem
    statuses: StatusOption[]
}>()

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Chamados',
        href: '/support-tickets',
    },
    {
        title: props.ticket.code,
        href: `/support-tickets/${props.ticket.id}`,
    },
]

const replyForm = useForm({
    message: '',
    is_internal: false,
})

const statusForm = useForm({
    status: props.ticket.status,
})

function submitReply() {
    replyForm.post(`/support-tickets/${props.ticket.id}/reply`, {
        preserveScroll: true,
        onSuccess: () => {
            replyForm.reset('message', 'is_internal')
        },
    })
}

function submitStatus() {
    statusForm.patch(`/support-tickets/${props.ticket.id}/status`, {
        preserveScroll: true,
    })
}

function statusBadgeClass(statusValue: string) {
    switch (statusValue) {
        case 'open':
            return 'bg-blue-100 text-blue-700'
        case 'in_progress':
            return 'bg-amber-100 text-amber-700'
        case 'waiting_customer':
            return 'bg-orange-100 text-orange-700'
        case 'waiting_support':
            return 'bg-violet-100 text-violet-700'
        case 'resolved':
            return 'bg-emerald-100 text-emerald-700'
        case 'closed':
            return 'bg-zinc-200 text-zinc-700'
        default:
            return 'bg-muted text-foreground'
    }
}
</script>

<template>
    <Head :title="ticket.code" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <Heading
                    :title="ticket.code"
                    :description="ticket.subject"
                    :icon="Headset"
                />

                <Link
                    href="/support-tickets"
                    class="inline-flex items-center justify-center gap-2 rounded-lg border bg-background px-4 py-2 text-sm font-medium transition hover:bg-muted"
                >
                    <ArrowLeft class="h-4 w-4" />
                    <span>Voltar</span>
                </Link>
            </div>

            <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr),320px]">
                <div class="space-y-6">
                    <div class="rounded-2xl border bg-card p-5 shadow-sm sm:p-6">
                        <div class="mb-4 flex flex-col gap-3 border-b pb-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <div class="text-sm text-muted-foreground">Solicitante</div>
                                <div class="font-medium">{{ ticket.creator?.name ?? '—' }}</div>
                                <div class="text-sm text-muted-foreground break-all">
                                    {{ ticket.creator?.email ?? '—' }}
                                </div>
                            </div>

                            <span
                                class="inline-flex w-fit rounded-md px-2 py-1 text-xs whitespace-nowrap"
                                :class="statusBadgeClass(ticket.status)"
                            >
                                {{ ticket.status_label }}
                            </span>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <div class="text-sm font-medium">Descrição inicial</div>
                                <div class="mt-2 whitespace-pre-wrap text-sm text-muted-foreground">
                                    {{ ticket.description }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-2xl border bg-card p-5 shadow-sm sm:p-6">
                        <div class="mb-4">
                            <h2 class="text-sm font-semibold tracking-tight">Histórico do chamado</h2>
                            <p class="text-sm text-muted-foreground">
                                Acompanhe as interações registradas neste chamado.
                            </p>
                        </div>

                        <div class="space-y-4">
                            <div
                                v-for="message in ticket.messages"
                                :key="message.id"
                                class="rounded-xl border bg-background p-4"
                            >
                                <div class="mb-2 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <div class="font-medium">{{ message.user?.name ?? '—' }}</div>
                                        <div class="text-xs text-muted-foreground break-all">
                                            {{ message.user?.email ?? '—' }}
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <span
                                            v-if="message.is_internal"
                                            class="inline-flex rounded-md bg-zinc-200 px-2 py-1 text-xs text-zinc-700"
                                        >
                                            Interna
                                        </span>

                                        <span class="text-xs text-muted-foreground whitespace-nowrap">
                                            {{ message.created_at ?? '—' }}
                                        </span>
                                    </div>
                                </div>

                                <div class="whitespace-pre-wrap text-sm text-muted-foreground">
                                    {{ message.message }}
                                </div>
                            </div>

                            <div v-if="ticket.messages.length === 0" class="text-sm text-muted-foreground">
                                Nenhuma interação registrada.
                            </div>
                        </div>
                    </div>

                    <form
                        class="space-y-4 rounded-2xl border bg-card p-5 shadow-sm sm:p-6"
                        @submit.prevent="submitReply"
                    >
                        <div>
                            <h2 class="text-sm font-semibold tracking-tight">Nova resposta</h2>
                            <p class="text-sm text-muted-foreground">
                                Registre uma nova interação neste chamado.
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="message">Mensagem</Label>
                            <textarea
                                id="message"
                                v-model="replyForm.message"
                                rows="6"
                                class="flex min-h-[140px] w-full rounded-md border bg-background px-3 py-2 text-sm outline-none ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring"
                            />
                            <InputError :message="replyForm.errors.message" />
                        </div>

                        <label class="flex items-center gap-3 text-sm">
                            <input
                                v-model="replyForm.is_internal"
                                type="checkbox"
                                class="h-4 w-4 rounded border-border"
                            />
                            Marcar como observação interna
                        </label>

                        <div class="flex justify-end">
                            <Can permission="support.reply">
                                <Button :disabled="replyForm.processing">
                                    {{ replyForm.processing ? 'Enviando...' : 'Enviar resposta' }}
                                </Button>
                            </Can>
                        </div>
                    </form>
                </div>

                <div class="space-y-6">
                    <div class="rounded-2xl border bg-card p-5 shadow-sm sm:p-6">
                        <div class="space-y-3">
                            <div>
                                <div class="text-sm text-muted-foreground">Código</div>
                                <div class="font-medium">{{ ticket.code }}</div>
                            </div>

                            <div>
                                <div class="text-sm text-muted-foreground">Assunto</div>
                                <div class="font-medium">{{ ticket.subject }}</div>
                            </div>

                            <div>
                                <div class="text-sm text-muted-foreground">Criado em</div>
                                <div class="font-medium">{{ ticket.created_at ?? '—' }}</div>
                            </div>

                            <div>
                                <div class="text-sm text-muted-foreground">Situação</div>
                                <div class="font-medium">
                                    {{ ticket.is_open ? 'Aberto' : 'Encerrado' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <form
                        class="space-y-4 rounded-2xl border bg-card p-5 shadow-sm sm:p-6"
                        @submit.prevent="submitStatus"
                    >
                        <div>
                            <h2 class="text-sm font-semibold tracking-tight">Status do chamado</h2>
                            <p class="text-sm text-muted-foreground">
                                Atualize o status operacional do chamado.
                            </p>
                        </div>

                        <div class="grid gap-2">
                            <Label for="status">Status</Label>
                            <select
                                id="status"
                                v-model="statusForm.status"
                                class="flex h-10 w-full rounded-md border bg-background px-3 py-2 text-sm"
                            >
                                <option
                                    v-for="item in statuses"
                                    :key="item.value"
                                    :value="item.value"
                                >
                                    {{ item.label }}
                                </option>
                            </select>
                            <InputError :message="statusForm.errors.status" />
                        </div>

                        <div class="flex justify-end">
                            <Can permission="support.changeStatus">
                                <Button :disabled="statusForm.processing">
                                    {{ statusForm.processing ? 'Salvando...' : 'Atualizar status' }}
                                </Button>
                            </Can>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
