<script setup lang="ts">
import PrimaryButton from '@/Components/PrimaryButton.vue';
import StatusBadge from '@/Components/approval/StatusBadge.vue';
import Card from '@/Components/ui/Card.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { ExpenseReport, Paginated } from '@/types/models';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    expenseReports: Paginated<ExpenseReport>;
    filters: { status?: string };
}>();

const { format } = useCurrencyFormat();
</script>

<template>
    <Head title="Expense Reports" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Expense Reports</h2>
                <Link :href="route('expense.reports.create')">
                    <PrimaryButton>New Expense Report</PrimaryButton>
                </Link>
            </div>
        </template>

        <Card :padded="false">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Code</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Employee</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Total</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th>
                            <th class="px-4 py-3" />
                        </tr>
                    </thead>
                    <tbody v-auto-animate class="divide-y divide-gray-100">
                        <tr v-for="report in expenseReports.data" :key="report.id">
                            <td class="px-4 py-3 text-sm font-medium text-gray-800">{{ report.code }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ report.employee?.name ?? '-' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ format(report.total_expense) }}</td>
                            <td class="px-4 py-3"><StatusBadge :status="report.status" /></td>
                            <td class="px-4 py-3 text-right text-sm">
                                <Link :href="route('expense.reports.show', report.id)" class="text-blue-600 hover:underline">View</Link>
                            </td>
                        </tr>
                        <tr v-if="expenseReports.data.length === 0">
                            <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-400">No expense reports yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :paginator="expenseReports" />
        </Card>
    </AuthenticatedLayout>
</template>
