<script setup lang="ts">
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Card from '@/Components/ui/Card.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Paginated, PurchaseInvoice } from '@/types/models';
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    invoices: Paginated<PurchaseInvoice>;
}>();

const { format } = useCurrencyFormat();
</script>

<template>
    <Head title="Purchase Invoices" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Purchase Invoices</h2>
                <Link :href="route('accounting.purchase-invoices.create')">
                    <PrimaryButton>Add Invoice</PrimaryButton>
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
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">PR Code</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Date</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Amount</th>
                                    <th class="px-4 py-3" />
                                </tr>
                            </thead>
                            <tbody v-auto-animate class="divide-y divide-gray-100">
                                <tr v-for="invoice in invoices.data" :key="invoice.id">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-800">{{ invoice.invoice_number }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ invoice.purchase_rfq?.purchase_request?.code ?? '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ invoice.invoice_date }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ format(invoice.amount) }}</td>
                                    <td class="px-4 py-3 text-right text-sm">
                                        <Link :href="route('accounting.purchase-invoices.show', invoice.id)" class="text-blue-600 hover:underline">
                                            View
                                        </Link>
                                    </td>
                                </tr>
                                <tr v-if="invoices.data.length === 0">
                                    <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-400">No invoices yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <Pagination :paginator="invoices" />
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
