<script setup lang="ts">
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import StatusBadge from '@/Components/approval/StatusBadge.vue';
import Card from '@/Components/ui/Card.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Paginated, PurchaseRequest } from '@/types/models';
import { Head, router } from '@inertiajs/vue3';

defineProps<{
    purchaseRequests: Paginated<PurchaseRequest>;
}>();

const { format } = useCurrencyFormat();

const moveToRfq = (purchaseRequest: PurchaseRequest) => {
    router.post(route('purchase.rfq.store', purchaseRequest.id));
};

const sendToAccounting = (purchaseRequest: PurchaseRequest) => {
    if (!purchaseRequest.rfq) return;
    router.post(route('purchase.rfq.send-to-accounting', purchaseRequest.rfq.id));
};
</script>

<template>
    <Head title="RFQ" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Request for Quotation</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <Card :padded="false">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Code</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Type</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Total</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th>
                                    <th class="px-4 py-3" />
                                </tr>
                            </thead>
                            <tbody v-auto-animate class="divide-y divide-gray-100">
                                <tr v-for="pr in purchaseRequests.data" :key="pr.id">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-800">{{ pr.code }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ pr.purchase_type?.name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ format(pr.total_amount) }}</td>
                                    <td class="px-4 py-3"><StatusBadge :status="pr.status" /></td>
                                    <td class="px-4 py-3 text-right text-sm">
                                        <SecondaryButton v-if="pr.status === 'approved'" @click="moveToRfq(pr)">Move to RFQ</SecondaryButton>
                                        <PrimaryButton v-else-if="pr.status === 'in_rfq' && pr.rfq" @click="sendToAccounting(pr)">
                                            Send to Accounting
                                        </PrimaryButton>
                                    </td>
                                </tr>
                                <tr v-if="purchaseRequests.data.length === 0">
                                    <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-400">No RFQ items yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <Pagination :paginator="purchaseRequests" />
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
