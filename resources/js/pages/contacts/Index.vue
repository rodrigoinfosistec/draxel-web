<script setup lang="ts">
import Can from '@/components/Can.vue'
import Heading from '@/components/Heading.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import AppLayout from '@/layouts/AppLayout.vue'
import type { BreadcrumbItem } from '@/types'
import { Head, Link, router } from '@inertiajs/vue3'
import { BookUser, Download, FileText, Pencil } from 'lucide-vue-next'
import { computed, ref } from 'vue'

type ContactItem = {
    id: number
    type: string
    type_label: string
    value: string
    label: string | null
}

type ContactRow = {
    id: number
    name: string
    description: string | null
    items: ContactItem[]
    created_at: string | null
}

type PaginationLink = {
    url: string | null
    label: string
    active: boolean
}

const props = defineProps<{
    contacts: {
        data: ContactRow[]
        links: PaginationLink[]
    }
    filters: {
        search: string
    }
}>()

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Agenda', href: '/contacts' },
]

const search = ref(props.filters.search ?? '')

function submitSearch() {
    router.get(
        '/contacts',
        { search: search.value || undefined },
        {
            preserveState: true,
            replace: true,
        },
    )
}

const exportParams = computed(() => {
    const params = new URLSearchParams()

    if (search.value) params.set('search', search.value)

    const query = params.toString()

    return {
        csv: query ? `/contacts/export/csv?${query}` : '/contacts/export/csv',
        pdf: query ? `/contacts/export/pdf?${query}` : '/contacts/export/pdf',
    }
})
</script>

<template>
    <Head title="Agenda" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">
            <div
                class="relative overflow-hidden rounded-2xl border bg-card/40 p-4 shadow-sm backdrop-blur-[1px] sm:p-5"
            >
                <div
                    class="absolute inset-0"
                    style="background: linear-gradient(to bottom right, var(--company-color-soft), transparent, transparent);"
                />

                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Agenda"
                        description="Gerencie os contatos do tenant."
                        :icon="BookUser"
                    />

                    <div class="flex flex-col gap-3 sm:flex-row">
                        <Can permission="contacts.viewAny">
                            <Button as-child variant="outline" class="w-full sm:w-auto">
                                <a :href="exportParams.csv">
                                    <Download class="mr-2 h-4 w-4" />
                                    Exportar CSV
                                </a>
                            </Button>
                        </Can>

                        <Can permission="contacts.viewAny">
                            <Button as-child variant="outline" class="w-full sm:w-auto">
                                <a :href="exportParams.pdf">
                                    <FileText class="mr-2 h-4 w-4" />
                                    Exportar PDF
                                </a>
                            </Button>
                        </Can>

                        <Can permission="contacts.create">
                            <Link href="/contacts/create" class="w-full sm:w-auto">
                                <Button class="w-full sm:w-auto">Novo contato</Button>
                            </Link>
                        </Can>
                    </div>
                </div>
            </div>

            <div class="rounded-xl border bg-card/50 p-4 shadow-sm">
                <form class="flex flex-col gap-3 sm:flex-row" @submit.prevent="submitSearch">
                    <Input
                        v-model="search"
                        type="text"
                        placeholder="Buscar por nome ou descrição"
                        class="w-full"
                    />
                    <Button type="submit" variant="outline" class="w-full sm:w-auto">
                        Buscar
                    </Button>
                </form>
            </div>

            <div class="space-y-4">
                <div
                    v-for="contact in contacts.data"
                    :key="contact.id"
                    class="rounded-xl border bg-card/50 shadow-sm"
                >
                    <div class="flex flex-col gap-4 border-b px-4 py-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="space-y-1">
                            <div class="font-semibold">{{ contact.name }}</div>
                            <div class="text-sm text-muted-foreground">
                                {{ contact.description || 'Sem descrição' }}
                            </div>
                        </div>

                        <Can permission="contacts.update">
                            <Link
                                :href="`/contacts/${contact.id}/edit`"
                                class="inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-medium text-foreground transition hover:bg-muted"
                            >
                                <Pencil class="h-4 w-4" />
                                <span>Editar</span>
                            </Link>
                        </Can>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-[720px] w-full text-sm">
                            <thead class="bg-muted/50">
                                <tr>
                                    <th class="px-4 py-3 text-left">Tipo</th>
                                    <th class="px-4 py-3 text-left">Valor</th>
                                    <th class="px-4 py-3 text-left">Rótulo</th>
                                </tr>
                            </thead>

                            <tbody>
                                <tr v-for="item in contact.items" :key="item.id" class="border-t">
                                    <td class="px-4 py-3">{{ item.type_label }}</td>
                                    <td class="px-4 py-3">{{ item.value }}</td>
                                    <td class="px-4 py-3">{{ item.label || '—' }}</td>
                                </tr>

                                <tr v-if="contact.items.length === 0">
                                    <td colspan="3" class="px-4 py-6 text-center text-muted-foreground">
                                        Nenhum meio de contato cadastrado.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div
                    v-if="contacts.data.length === 0"
                    class="rounded-xl border bg-card/50 px-4 py-8 text-center text-muted-foreground shadow-sm"
                >
                    Nenhum contato encontrado.
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <Link
                    v-for="link in contacts.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    v-html="link.label"
                    class="rounded-md border px-3 py-2 text-sm"
                    :class="{
                        'bg-muted': link.active,
                        'pointer-events-none opacity-50': !link.url,
                    }"
                />
            </div>
        </div>
    </AppLayout>
</template>
