<script setup lang="ts">
import DangerButton from '@/Components/DangerButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import StatusBadge from '@/Components/approval/StatusBadge.vue';
import Card from '@/Components/ui/Card.vue';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { ExpenseReport } from '@/types/models';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps<{
    expenseReport: ExpenseReport;
}>();

const { format } = useCurrencyFormat();

const submit = () => router.post(route('expense.reports.submit', props.expenseReport.id));
const approve = () => router.post(route('expense.reports.approve', props.expenseReport.id));
const reject = () => router.post(route('expense.reports.reject', props.expenseReport.id));
const sendToAccounting = () => router.post(route('expense.reports.send-to-accounting', props.expenseReport.id));
const destroy = () => {
    if (confirm('Delete this draft expense report?')) {
        router.delete(route('expense.reports.destroy', props.expenseReport.id));
    }
};
</script>

<template>
    <Head :title="expenseReport.code" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ expenseReport.code }}</h2>
                <StatusBadge :status="expenseReport.status" />
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <Card>
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-gray-500">Employee</dt>
                            <dd class="text-gray-800">{{ expenseReport.employee?.name ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Summary</dt>
                            <dd class="text-gray-800">{{ expenseReport.summary ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Total</dt>
                            <dd class="font-semibold text-gray-800">{{ format(expenseReport.total_expense) }}</dd>
                        </div>
                    </dl>

                    <div class="mt-6 flex justify-end gap-3 border-t border-gray-100 pt-4">
                        <template v-if="expenseReport.status === 'draft'">
                            <SecondaryButton @click="destroy">Delete</SecondaryButton>
                            <PrimaryButton @click="submit">Submit</PrimaryButton>
                        </template>
                        <template v-else-if="expenseReport.status === 'submitted'">
                            <DangerButton @click="reject">Reject</DangerButton>
                            <PrimaryButton @click="approve">Approve</PrimaryButton>
                        </template>
                        <template v-else-if="expenseReport.status === 'approved'">
                            <PrimaryButton @click="sendToAccounting">Send to Accounting</PrimaryButton>
                        </template>
                    </div>
                </Card>

                <Card>
                    <h3 class="mb-3 text-sm font-medium text-gray-500">Expense Lines</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-2 py-2 text-left text-xs font-medium uppercase text-gray-500">Date</th>
                                    <th class="px-2 py-2 text-left text-xs font-medium uppercase text-gray-500">Category</th>
                                    <th class="px-2 py-2 text-left text-xs font-medium uppercase text-gray-500">Description</th>
                                    <th class="px-2 py-2 text-right text-xs font-medium uppercase text-gray-500">Total</th>
                                </tr>
                            </thead>
                            <tbody v-auto-animate class="divide-y divide-gray-100">
                                <tr v-for="line in expenseReport.lines" :key="line.id">
                                    <td class="px-2 py-2 text-sm text-gray-700">{{ line.expense_date }}</td>
                                    <td class="px-2 py-2 text-sm text-gray-700">{{ line.expense_category?.name }}</td>
                                    <td class="px-2 py-2 text-sm text-gray-500">{{ line.description ?? '-' }}</td>
                                    <td class="px-2 py-2 text-right text-sm text-gray-700">{{ format(line.total) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
