<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextInput from '@/Components/TextInput.vue';
import Card from '@/Components/ui/Card.vue';
import CurrencyInput from '@/Components/ui/CurrencyInput.vue';
import FileUpload from '@/Components/ui/FileUpload.vue';
import IconButton from '@/Components/ui/IconButton.vue';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import { useLineItems } from '@/Composables/useLineItems';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Employee, ExpenseCategory, Project } from '@/types/models';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { computed } from 'vue';

const props = defineProps<{
    employees: Employee[];
    expenseCategories: ExpenseCategory[];
    projects: Project[];
}>();

const employeeOptions = computed(() => props.employees.map((employee) => ({ id: employee.id, text: employee.name })));
const expenseCategoryOptions = computed(() => props.expenseCategories.map((category) => ({ id: category.id, text: category.name })));
const projectOptions = computed(() => props.projects.map((project) => ({ id: project.id, text: project.name })));

interface DraftLine {
    expense_date: string;
    expense_category_id: number | '';
    project_id: number | '';
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
    lines: [{ expense_date: '', expense_category_id: '', project_id: '', description: '', total: 0, attachment: null }],
});

const { format } = useCurrencyFormat();
const linesRef = computed({
    get: () => form.lines,
    set: (value) => (form.lines = value),
});

const { add, remove, total } = useLineItems<DraftLine>(
    linesRef,
    () => ({ expense_date: '', expense_category_id: '', project_id: '', description: '', total: 0, attachment: null }),
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
            <div class="mx-auto ">
                <Card>
                    <form class="space-y-6" @submit.prevent="submit">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <InputLabel for="employee_id" value="Employee" required />
                                <SelectInput
                                    id="employee_id"
                                    v-model="form.employee_id"
                                    :options="employeeOptions"
                                    placeholder="Select employee"
                                    class="mt-1 block w-full"
                                />
                                <InputError :message="form.errors.employee_id" class="mt-2" />
                            </div>

                            <div>
                                <InputLabel for="summary" value="Summary (optional)" />
                                <TextInput id="summary" v-model="form.summary" size="sm" class="mt-1 block w-full" />
                                <InputError :message="form.errors.summary" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <InputLabel value="Expense Lines" class="mb-2" required />
                            <div class="max-h-[28rem] overflow-y-auto overflow-x-auto rounded-md border border-gray-100">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="sticky top-0 z-10 bg-gray-50">
                                        <tr>
                                            <th class="px-2 py-2 text-left text-xs font-medium uppercase text-gray-500">Date<span class="text-red-500">&nbsp;*</span></th>
                                            <th class="px-2 py-2 text-left text-xs font-medium uppercase text-gray-500 w-44">Category<span class="text-red-500">&nbsp;*</span></th>
                                            <th class="px-2 py-2 text-left text-xs font-medium uppercase text-gray-500 w-52">Project<span class="text-red-500">&nbsp;*</span></th>
                                            <th class="px-2 py-2 text-left text-xs font-medium uppercase text-gray-500">Description</th>
                                            <th class="w-36 px-2 py-2 text-left text-xs font-medium uppercase text-gray-500">Total<span class="text-red-500">&nbsp;*</span></th>
                                            <th class="px-2 py-2 text-left text-xs font-medium uppercase text-gray-500 w-52">Attachment<span class="text-red-500">&nbsp;*</span></th>
                                            <th class="w-10" />
                                        </tr>
                                    </thead>
                                    <tbody v-auto-animate class="divide-y divide-gray-100">
                                        <tr v-for="(line, index) in form.lines" :key="index">
                                            <td class="px-2 py-2">
                                                <TextInput v-model="line.expense_date" type="date" size="sm" class="w-full" />
                                            </td>
                                            <td class="px-2 py-2">
                                                <SelectInput
                                                    v-model="line.expense_category_id"
                                                    :options="expenseCategoryOptions"
                                                    placeholder="Select category"
                                                    size="sm"
                                                    class="block w-full"
                                                />
                                            </td>
                                            <td class="px-2 py-2">
                                                <SelectInput
                                                    v-model="line.project_id"
                                                    :options="projectOptions"
                                                    placeholder="Select project"
                                                    size="sm"
                                                    class="block w-full"
                                                />
                                            </td>
                                            <td class="px-2 py-2">
                                                <TextInput v-model="line.description" type="text" size="sm" class="w-full" />
                                            </td>
                                            <td class="px-2 py-2">
                                                <CurrencyInput v-model="line.total" size="sm" class="w-full" />
                                            </td>
                                            <td class="px-2 py-2" style="min-width: 220px">
                                                <FileUpload v-model="line.attachment" label="Drop or browse" size="sm" />
                                            </td>
                                            <td class="px-2 py-2 text-right">
                                                <IconButton title="Remove" variant="delete" @click="remove(index)">
                                                    <Trash2 class="h-4 w-4" />
                                                </IconButton>
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
