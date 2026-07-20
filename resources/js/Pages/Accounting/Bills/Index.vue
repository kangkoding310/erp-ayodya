<script setup lang="ts">
import StatusBadge from '@/Components/approval/StatusBadge.vue';
import Card from '@/Components/ui/Card.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { AccountingBill, Paginated } from '@/types/models';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    bills: Paginated<AccountingBill>;
    filters: { source_type?: string; status?: string };
}>();

const { format } = useCurrencyFormat();

const sourceCode = (bill: AccountingBill) => {
    const source = bill.source as { purchase_request?: { code: string }; code?: string } | undefined;
    return source?.purchase_request?.code ?? source?.code ?? '-';
};
</script>

<template>
    <Head title="Accounting Bills" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Accounting Bills</h2>
        </template>

        <div class="py-1">
            <div class="mx-auto max-w-6xl ">
                <Card :padded="false">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Source</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Reference</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Amount</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Due Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th>
                                    <th class="px-4 py-3" />
                                </tr>
                            </thead>
                            <tbody v-auto-animate class="divide-y divide-gray-100">
                                <tr v-for="bill in bills.data" :key="bill.id">
                                    <td class="px-4 py-3 text-sm capitalize text-gray-700">{{ bill.source_type }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ sourceCode(bill) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ format(bill.amount) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ bill.due_date ?? '-' }}</td>
                                    <td class="px-4 py-3"><StatusBadge :status="bill.status" /></td>
                                    <td class="px-4 py-3 text-right text-sm">
                                        <Link :href="route('accounting.bills.show', bill.id)" class="text-blue-600 hover:underline">View</Link>
                                    </td>
                                </tr>
                                <tr v-if="bills.data.length === 0">
                                    <td colspan="6" class="px-4 py-6 text-center text-sm text-gray-400">No bills yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <Pagination :paginator="bills" />
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
