<script setup lang="ts">
import StatusBadge from '@/Components/approval/StatusBadge.vue';
import Card from '@/Components/ui/Card.vue';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { AccountingBill } from '@/types/models';
import { Head } from '@inertiajs/vue3';

const props = defineProps<{
    bill: AccountingBill;
}>();

const { format } = useCurrencyFormat();
const source = props.bill.source as { purchase_request?: { code: string }; code?: string } | undefined;
</script>

<template>
    <Head :title="`Bill #${bill.id}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Bill #{{ bill.id }}</h2>
                <StatusBadge :status="bill.status" />
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                <Card>
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-gray-500">Source</dt>
                            <dd class="capitalize text-gray-800">{{ bill.source_type }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Reference</dt>
                            <dd class="text-gray-800">{{ source?.purchase_request?.code ?? source?.code ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Bill Number</dt>
                            <dd class="text-gray-800">{{ bill.bill_number ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Amount</dt>
                            <dd class="font-semibold text-gray-800">{{ format(bill.amount) }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Due Date</dt>
                            <dd class="text-gray-800">{{ bill.due_date ?? '-' }}</dd>
                        </div>
                    </dl>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
