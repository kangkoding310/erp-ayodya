<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Card from '@/Components/ui/Card.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import LineItemTable, { type DraftLine } from '@/Pages/Purchase/Request/Partials/LineItemTable.vue';
import type { Division, Employee, Product, PurchaseType } from '@/types/models';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    purchaseTypes: PurchaseType[];
    employees: Employee[];
    divisions: Division[];
    products: Product[];
}>();

const form = useForm<{
    purchase_type_id: number | '';
    project_name: string;
    currency: string;
    employee_id: number | '';
    expected_date: string;
    division_id: number | '';
    lines: DraftLine[];
}>({
    purchase_type_id: '',
    project_name: '',
    currency: 'IDR',
    employee_id: '',
    expected_date: '',
    division_id: '',
    lines: [{ product_id: '', description: '', qty: 1, price_estimate: 0 }],
});

const submit = () => {
    form.post(route('purchase.requests.store'));
};
</script>

<template>
    <Head title="New Purchase Request" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">New Purchase Request</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <Card>
                    <form class="space-y-6" @submit.prevent="submit">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel for="purchase_type_id" value="Purchase Type" />
                                <select
                                    id="purchase_type_id"
                                    v-model="form.purchase_type_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="" disabled>Select purchase type</option>
                                    <option v-for="type in purchaseTypes" :key="type.id" :value="type.id">{{ type.name }}</option>
                                </select>
                                <InputError :message="form.errors.purchase_type_id" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="project_name" value="Project Name (optional)" />
                                <TextInput id="project_name" v-model="form.project_name" class="mt-1 block w-full" />
                                <InputError :message="form.errors.project_name" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="employee_id" value="Requested For (Employee)" />
                                <select
                                    id="employee_id"
                                    v-model="form.employee_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="" disabled>Select employee</option>
                                    <option v-for="employee in employees" :key="employee.id" :value="employee.id">{{ employee.name }}</option>
                                </select>
                                <InputError :message="form.errors.employee_id" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="division_id" value="Division" />
                                <select
                                    id="division_id"
                                    v-model="form.division_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <option value="" disabled>Select division</option>
                                    <option v-for="division in divisions" :key="division.id" :value="division.id">{{ division.name }}</option>
                                </select>
                                <InputError :message="form.errors.division_id" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="expected_date" value="Expected Date (optional)" />
                                <TextInput id="expected_date" v-model="form.expected_date" type="date" class="mt-1 block w-full" />
                                <InputError :message="form.errors.expected_date" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="currency" value="Currency" />
                                <TextInput id="currency" v-model="form.currency" maxlength="3" class="mt-1 block w-full uppercase" />
                                <InputError :message="form.errors.currency" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <InputLabel value="Line Items" class="mb-2" />
                            <LineItemTable v-model:lines="form.lines" :products="products" :errors="form.errors" />
                        </div>

                        <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
                            <Link :href="route('purchase.requests.index')">
                                <SecondaryButton type="button">Cancel</SecondaryButton>
                            </Link>
                            <PrimaryButton :disabled="form.processing">Save as Draft</PrimaryButton>
                        </div>
                    </form>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
