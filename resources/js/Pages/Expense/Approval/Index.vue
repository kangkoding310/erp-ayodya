<script setup lang="ts">
import StatusBadge from '@/Components/approval/StatusBadge.vue';
import Card from '@/Components/ui/Card.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { ExpenseReport, Paginated } from '@/types/models';
import { Head, Link } from '@inertiajs/vue3';

interface ApprovalRow {
    expense_report: ExpenseReport;
    pending_line_count: number;
}

defineProps<{
    reports: Paginated<ApprovalRow>;
    filters: { status?: string };
}>();

const { format } = useCurrencyFormat();
</script>

<template>
    <Head title="My Expense Approvals" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">My Expense Approvals</h2>
        </template>

        <div class="py-1">
            <div class="mx-auto max-w-6xl">
                <Card :padded="false">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Code</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Employee</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Total</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Lines Pending You</th>
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
                                    <td class="px-4 py-3"><StatusBadge :status="row.expense_report.status" /></td>
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
            </div>
        </div>
    </AuthenticatedLayout>
</template>
