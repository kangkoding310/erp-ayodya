<script setup lang="ts">
import Card from '@/Components/ui/Card.vue';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { PurchaseInvoice } from '@/types/models';
import { Head } from '@inertiajs/vue3';

const props = defineProps<{
    invoice: PurchaseInvoice;
}>();

const { format } = useCurrencyFormat();
</script>

<template>
    <Head :title="invoice.invoice_number" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Invoice {{ invoice.invoice_number }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-2xl space-y-6 px-4 sm:px-6 lg:px-8">
                <Card>
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-gray-500">Purchase Request</dt>
                            <dd class="text-gray-800">{{ invoice.purchase_rfq?.purchase_request?.code ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Invoice Date</dt>
                            <dd class="text-gray-800">{{ invoice.invoice_date }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Amount</dt>
                            <dd class="font-semibold text-gray-800">{{ format(invoice.amount) }}</dd>
                        </div>
                    </dl>
                </Card>

                <Card>
                    <h3 class="mb-3 text-sm font-medium text-gray-500">Payments</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr>
                                    <th class="px-2 py-2 text-left text-xs font-medium uppercase text-gray-500">Date</th>
                                    <th class="px-2 py-2 text-left text-xs font-medium uppercase text-gray-500">Bank</th>
                                    <th class="px-2 py-2 text-right text-xs font-medium uppercase text-gray-500">Amount</th>
                                </tr>
                            </thead>
                            <tbody v-auto-animate class="divide-y divide-gray-100">
                                <tr v-for="payment in invoice.payments" :key="payment.id">
                                    <td class="px-2 py-2 text-sm text-gray-700">{{ payment.payment_date }}</td>
                                    <td class="px-2 py-2 text-sm text-gray-700">{{ payment.bank?.bank_name }}</td>
                                    <td class="px-2 py-2 text-right text-sm text-gray-700">{{ format(payment.amount) }}</td>
                                </tr>
                                <tr v-if="!invoice.payments?.length">
                                    <td colspan="3" class="px-2 py-4 text-center text-sm text-gray-400">No payments recorded.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
