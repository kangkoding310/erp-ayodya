<script setup lang="ts">
import Card from '@/Components/ui/Card.vue';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { PurchasePayment } from '@/types/models';
import { Head } from '@inertiajs/vue3';

const props = defineProps<{
    payment: PurchasePayment;
}>();

const { format } = useCurrencyFormat();
</script>

<template>
    <Head :title="`Payment #${payment.id}`" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Payment #{{ payment.id }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                <Card>
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-gray-500">Invoice</dt>
                            <dd class="text-gray-800">{{ payment.purchase_invoice?.invoice_number ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Purchase Request</dt>
                            <dd class="text-gray-800">
                                {{ payment.purchase_invoice?.purchase_rfq?.purchase_request?.code ?? '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Bank</dt>
                            <dd class="text-gray-800">{{ payment.bank?.bank_name ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Payment Date</dt>
                            <dd class="text-gray-800">{{ payment.payment_date }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Amount</dt>
                            <dd class="font-semibold text-gray-800">{{ format(payment.amount) }}</dd>
                        </div>
                    </dl>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
