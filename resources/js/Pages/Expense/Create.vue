<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Card from '@/Components/ui/Card.vue';
import FileUpload from '@/Components/ui/FileUpload.vue';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import { useLineItems } from '@/Composables/useLineItems';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Employee, ExpenseCategory } from '@/types/models';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps<{
    employees: Employee[];
    expenseCategories: ExpenseCategory[];
}>();

interface DraftLine {
    expense_date: string;
    expense_category_id: number | '';
    description: string;
    total: number;
    attachment: File | null;
}

const form = useForm<{
    employee_id: number | '';
    summary: string;
    lines: DraftLine[];
}>({
    employee_id: '',
    summary: '',
    lines: [{ expense_date: '', expense_category_id: '', description: '', total: 0, attachment: null }],
});

const { format } = useCurrencyFormat();
const linesRef = computed({
    get: () => form.lines,
    set: (value) => (form.lines = value),
});

const { add, remove, total } = useLineItems<DraftLine>(
    linesRef,
    () => ({ expense_date: '', expense_category_id: '', description: '', total: 0, attachment: null }),
    (line) => Number(line.total),
);

const submit = () => {
    form.post(route('expense.reports.store'));
};
</script>

<template>
    <Head title="New Expense Report" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">New Expense Report</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <Card>
                    <form class="space-y-6" @submit.prevent="submit">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel for="employee_id" value="Employee" />
                                <select
                                    id="employee_id"
                                    v-model="form.employee_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                                    <option value="" disabled>Select employee</option>
                                    <option v-for="employee in employees" :key="employee.id" :value="employee.id">{{ employee.name }}</option>
                                </select>
                                <InputError :message="form.errors.employee_id" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="summary" value="Summary (optional)" />
                                <TextInput id="summary" v-model="form.summary" class="mt-1 block w-full" />
                                <InputError :message="form.errors.summary" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <InputLabel value="Expense Lines" class="mb-2" />
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr>
                                            <th class="px-2 py-2 text-left text-xs font-medium uppercase text-gray-500">Date</th>
                                            <th class="px-2 py-2 text-left text-xs font-medium uppercase text-gray-500">Category</th>
                                            <th class="px-2 py-2 text-left text-xs font-medium uppercase text-gray-500">Description</th>
                                            <th class="w-36 px-2 py-2 text-left text-xs font-medium uppercase text-gray-500">Total</th>
                                            <th class="px-2 py-2 text-left text-xs font-medium uppercase text-gray-500">Attachment</th>
                                            <th class="w-10" />
                                        </tr>
                                    </thead>
                                    <tbody v-auto-animate class="divide-y divide-gray-100">
                                        <tr v-for="(line, index) in form.lines" :key="index">
                                            <td class="px-2 py-2">
                                                <input
                                                    v-model="line.expense_date"
                                                    type="date"
                                                    class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                />
                                            </td>
                                            <td class="px-2 py-2">
                                                <select
                                                    v-model="line.expense_category_id"
                                                    class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                >
                                                    <option value="" disabled>Select category</option>
                                                    <option v-for="category in expenseCategories" :key="category.id" :value="category.id">
                                                        {{ category.name }}
                                                    </option>
                                                </select>
                                            </td>
                                            <td class="px-2 py-2">
                                                <input
                                                    v-model="line.description"
                                                    type="text"
                                                    class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                />
                                            </td>
                                            <td class="px-2 py-2">
                                                <input
                                                    v-model.number="line.total"
                                                    type="number"
                                                    min="0"
                                                    step="0.01"
                                                    class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                />
                                            </td>
                                            <td class="px-2 py-2" style="min-width: 220px">
                                                <FileUpload v-model="line.attachment" />
                                            </td>
                                            <td class="px-2 py-2 text-right">
                                                <button type="button" class="text-red-600 hover:underline" @click="remove(index)">&times;</button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <p v-if="form.errors.lines" class="mt-2 text-sm text-red-600">{{ form.errors.lines }}</p>
                            <div class="mt-3 flex items-center justify-between">
                                <SecondaryButton type="button" @click="add">Add Line</SecondaryButton>
                                <p class="text-sm font-semibold text-gray-800">Total: {{ format(total) }}</p>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 border-t border-gray-100 pt-4">
                            <Link :href="route('expense.reports.index')">
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
