<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Company = { id: number; name: string }

const page = usePage()

const companies = computed(() => (page.props.companies as Company[]) ?? [])
const currentCompanyId = computed(() => (page.props.currentCompanyId as number | null) ?? null)

const open = ref(false)

function switchCompany(companyId: number) {
    router.post(
        '/company/switch',
        { company_id: companyId },
        { preserveScroll: true, preserveState: false }
    )
    open.value = false
}
</script>

<template>
    <div v-if="companies.length" class="w-full">
        <!-- EXPANDIDO (Sidebar aberto) -->
        <div class="hidden group-data-[state=expanded]/sidebar:block">
            <label class="block text-xs text-muted-foreground mb-1 uppercase tracking-wide">
                Empresa
            </label>

            <select
                :value="currentCompanyId"
                @change="switchCompany(Number(($event.target as HTMLSelectElement).value))"
                class="w-full border rounded px-2 py-2 text-sm bg-background"
            >
                <option
                    v-for="company in companies"
                    :key="company.id"
                    :value="company.id"
                >
                    {{ company.name }}
                </option>
            </select>
        </div>

        <!-- COLAPSADO (Sidebar só ícones) -->
        <div class="hidden group-data-[state=collapsed]/sidebar:block">
            <button
                type="button"
                class="w-full border rounded px-2 py-2 text-sm bg-background text-left"
                @click="open = !open"
                title="Trocar empresa"
            >
                <span class="block truncate">
                    {{
                        companies.find(c => c.id === currentCompanyId)?.name ??
                        'Empresa'
                    }}
                </span>
            </button>

            <div
                v-if="open"
                class="mt-2 rounded border bg-background shadow-sm overflow-hidden"
            >
                <button
                    v-for="company in companies"
                    :key="company.id"
                    type="button"
                    class="w-full px-3 py-2 text-left text-sm hover:bg-muted"
                    @click="switchCompany(company.id)"
                >
                    {{ company.name }}
                </button>
            </div>
        </div>

        <!-- FALLBACK (se o componente Sidebar não expor data-state, pelo menos mantém funcional) -->
        <div class="group-data-[state=expanded]/sidebar:hidden group-data-[state=collapsed]/sidebar:hidden">
            <select
                :value="currentCompanyId"
                @change="switchCompany(Number(($event.target as HTMLSelectElement).value))"
                class="w-full border rounded px-2 py-2 text-sm bg-background"
            >
                <option
                    v-for="company in companies"
                    :key="company.id"
                    :value="company.id"
                >
                    {{ company.name }}
                </option>
            </select>
        </div>
    </div>
</template>
