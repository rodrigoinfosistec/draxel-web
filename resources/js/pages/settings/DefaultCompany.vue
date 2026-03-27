<script setup lang="ts">
import DefaultCompanyController from '@/actions/App/Http/Controllers/Settings/DefaultCompanyController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { edit } from '@/routes/default-company';
import type { BreadcrumbItem } from '@/types';
import { Form, Head, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Empresa padrão',
        href: edit(),
    },
];

type Company = {
    id: number;
    name: string;
};

const page = usePage<{
    companies: Company[];
    defaultCompanyId: number | null;
}>();

const companies = computed(() => page.props.companies ?? []);
const defaultCompanyId = computed(() => page.props.defaultCompanyId ?? null);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Empresa padrão" />

        <h1 class="sr-only">Empresa padrão</h1>

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <Heading
                    variant="small"
                    title="Empresa padrão"
                    description="Define a empresa utilizada automaticamente ao iniciar uma nova sessão."
                />

                <Form
                    v-bind="DefaultCompanyController.update.form()"
                    class="space-y-6"
                    v-slot="{ errors, processing, recentlySuccessful }"
                >
                    <div class="grid gap-2">
                        <Label for="company_id">Empresa</Label>

                        <select
                            id="company_id"
                            name="company_id"
                            class="mt-1 flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground shadow-xs outline-none transition-colors ring-offset-background focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
                            :default-value="defaultCompanyId"
                            required
                        >
                            <option
                                v-for="company in companies"
                                :key="company.id"
                                :value="company.id"
                            >
                                {{ company.name }}
                            </option>
                        </select>

                        <InputError
                            class="mt-2"
                            :message="errors.company_id"
                        />
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="processing">
                            Salvar
                        </Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-show="recentlySuccessful"
                                class="text-sm text-muted-foreground"
                            >
                                Salvo.
                            </p>
                        </Transition>
                    </div>
                </Form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
