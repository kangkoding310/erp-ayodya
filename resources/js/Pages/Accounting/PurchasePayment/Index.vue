<script setup lang="ts">
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Card from '@/Components/ui/Card.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Paginated, PurchasePayment } from '@/types/models';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    payments: Paginated<PurchasePayment>;
}>();

const { format } = useCurrencyFormat();
</script>

<template>
    <Head title="Purchase Payments" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Purchase Payments</h2>
                <Link :href="route('accounting.purchase-payments.create')">
                    <PrimaryButton>Record Payment</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <Card :padded="false">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Invoice #</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Bank</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Amount</th>
                                    <th class="px-4 py-3" />
                                </tr>
                            </thead>
                            <tbody v-auto-animate class="divide-y divide-gray-100">
                                <tr v-for="payment in payments.data" :key="payment.id">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-800">
                                        {{ payment.purchase_invoice?.invoice_number ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ payment.bank?.bank_name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ payment.payment_date }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ format(payment.amount) }}</td>
                                    <td class="px-4 py-3 text-right text-sm">
                                        <Link :href="route('accounting.purchase-payments.show', payment.id)" class="text-blue-600 hover:underline">
                                            View
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="payments.data.length === 0">
                                    <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-400">No payments yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <Pagination :paginator="payments" />
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
