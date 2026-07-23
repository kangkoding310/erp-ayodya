<script setup lang="ts">
import InputLabel from '@/Components/InputLabel.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextInput from '@/Components/TextInput.vue';
import StatusBadge from '@/Components/approval/StatusBadge.vue';
import Card from '@/Components/ui/Card.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import RangeSlider from '@/Components/ui/RangeSlider.vue';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Employee, ExpenseReport, Paginated } from '@/types/models';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, ArrowUpDown } from '@lucide/vue';
import { watchDebounced } from '@vueuse/core';
import { computed, reactive, ref } from 'vue';

interface ApprovalRow {
    expense_report: ExpenseReport;
    pending_line_count: number;
    current_approver_name: string | null;
}

const props = defineProps<{
    reports: Paginated<ApprovalRow>;
    employees: Employee[];
    statuses: { id: string; text: string }[];
    totalRange: { min: number; max: number };
    filters: {
        status?: string;
        employee_id?: string;
        search?: string;
        min_total?: string;
        max_total?: string;
        sort?: string;
        direction?: string;
    };
}>();

const { format } = useCurrencyFormat();

const employeeOptions = computed(() => props.employees.map((employee) => ({ id: employee.id, text: employee.name })));

const filters = reactive({
    search: props.filters.search ?? '',
    status: props.filters.status ?? '',
    employee_id: props.filters.employee_id ?? '',
});

const totalRangeModel = ref<[number, number]>([
    props.filters.min_total !== undefined ? Number(props.filters.min_total) : props.totalRange.min,
    props.filters.max_total !== undefined ? Number(props.filters.max_total) : props.totalRange.max,
]);

const sort = computed(() => props.filters.sort ?? 'code');
const direction = computed(() => props.filters.direction ?? 'asc');

const columns: { key: string; label: string }[] = [
    { key: 'code', label: 'Code' },
    { key: 'employee', label: 'Employee' },
    { key: 'total_expense', label: 'Total' },
    { key: 'status', label: 'Status' },
];

const linesColumnLabel = computed(() => (filters.status ? 'Lines' : 'Lines Pending You'));

const applyFilters = (overrides: Record<string, unknown> = {}) => {
    router.get(
        route('expense.approvals.index'),
        {
            search: filters.search || undefined,
            status: filters.status || undefined,
            employee_id: filters.employee_id || undefined,
            min_total: totalRangeModel.value[0] > props.totalRange.min ? totalRangeModel.value[0] : undefined,
            max_total: totalRangeModel.value[1] < props.totalRange.max ? totalRangeModel.value[1] : undefined,
            sort: sort.value,
            direction: direction.value,
            ...overrides,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const toggleSort = (key: string) => {
    const nextDirection = sort.value === key && direction.value === 'asc' ? 'desc' : 'asc';
    applyFilters({ sort: key, direction: nextDirection });
};

watchDebounced(() => filters.search, () => applyFilters(), { debounce: 400 });
watchDebounced([() => filters.status, () => filters.employee_id], () => applyFilters(), { debounce: 0 });
watchDebounced(totalRangeModel, () => applyFilters(), { debounce: 400, deep: true });
</script>

<template>
    <Head title="My Expense Approvals" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">My Expense Approvals</h2>
        </template>

        <Card class="mb-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <InputLabel for="search" value="Search Code / Employee" />
                    <TextInput id="search" v-model="filters.search" placeholder="Search..." class="mt-1 block w-full" />
                </div>

                <div>
                    <InputLabel for="employee_id" value="Employee" />
                    <SelectInput
                        id="employee_id"
                        v-model="filters.employee_id"
                        :options="employeeOptions"
                        placeholder="All employees"
                        allow-clear
                        class="mt-1 block w-full"
                    />
                </div>

                <div>
                    <InputLabel for="status" value="Status" />
                    <SelectInput
                        id="status"
                        v-model="filters.status"
                        :options="statuses"
                        placeholder="Needs My Action"
                        allow-clear
                        class="mt-1 block w-full"
                    />
                </div>

                <div>
                    <InputLabel value="Total" />
                    <div class="mt-1 flex items-center justify-between text-xs text-gray-500">
                        <span>{{ format(totalRangeModel[0]) }}</span>
                        <span>{{ format(totalRangeModel[1]) }}</span>
                    </div>
                    <RangeSlider
                        v-model="totalRangeModel"
                        :min="totalRange.min"
                        :max="totalRange.max"
                        :step="Math.max(Math.round((totalRange.max - totalRange.min) / 100), 1)"
                        class="mt-1"
                    />
                </div>
            </div>

            <div v-if="filters.search || filters.status || filters.employee_id" class="mt-4 flex justify-end">
                <SecondaryButton
                    type="button"
                    @click="
                        filters.search = '';
                        filters.status = '';
                        filters.employee_id = '';
                        totalRangeModel = [totalRange.min, totalRange.max];
                        applyFilters({ min_total: undefined, max_total: undefined });
                    "
                >
                    Clear filters
                </SecondaryButton>
            </div>
        </Card>

        <Card :padded="false">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th
                                v-for="column in columns"
                                :key="column.key"
                                class="cursor-pointer select-none px-4 py-3 text-left text-xs font-medium uppercase text-gray-500"
                                @click="toggleSort(column.key)"
                            >
                                <span class="inline-flex items-center gap-1">
                                    {{ column.label }}
                                    <ArrowUp v-if="sort === column.key && direction === 'asc'" class="h-3.5 w-3.5" />
                                    <ArrowDown v-else-if="sort === column.key && direction === 'desc'" class="h-3.5 w-3.5" />
                                    <ArrowUpDown v-else class="h-3.5 w-3.5 text-gray-300" />
                                </span>
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ linesColumnLabel }}</th>
                            <th class="px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody v-auto-animate class="divide-y divide-gray-100">
                        <tr v-for="row in reports.data" :key="row.expense_report.id">
                            <td class="px-4 py-3 text-sm font-medium text-gray-800">
                                {{ row.expense_report.code }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ row.expense_report.employee?.name ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">
                                {{ format(row.expense_report.total_expense) }}
                            </td>
                            <td class="px-4 py-3">
                                <StatusBadge
                                    :status="row.expense_report.status"
                                    :label="
                                        row.expense_report.status === 'in_approval' && row.current_approver_name
                                            ? `Pending Approval by ${row.current_approver_name}`
                                            : undefined
                                    "
                                />
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ row.pending_line_count }}</td>
                            <td class="px-4 py-3 text-right text-sm">
                                <Link :href="route('expense.approvals.show', row.expense_report.id)" class="text-blue-600 hover:underline">
                                    Review
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="reports.data.length === 0">
                            <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-400">No approvals to review.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="reports" />
        </Card>
    </AuthenticatedLayout>
</template>
