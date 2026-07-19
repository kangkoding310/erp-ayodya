<script setup lang="ts">
import DangerButton from '@/Components/DangerButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ApprovalTimeline from '@/Components/approval/ApprovalTimeline.vue';
import StatusBadge from '@/Components/approval/StatusBadge.vue';
import Card from '@/Components/ui/Card.vue';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { PurchaseApproval } from '@/types/models';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    approval: PurchaseApproval;
}>();

const purchaseRequest = props.approval.purchase_request!;
const { format } = useCurrencyFormat(purchaseRequest.currency);

const form = useForm({ remarks: '' });

const approve = () => {
    form.post(route('purchase.approvals.approve', props.approval.id));
};

const reject = () => {
    if (!form.remarks) {
        alert('Remarks are required when rejecting.');
        return;
    }
    form.post(route('purchase.approvals.reject', props.approval.id));
};
</script>

<template>
    <Head :title="`Review ${purchaseRequest.code}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Review {{ purchaseRequest.code }}</h2>
                <StatusBadge :status="approval.status" />
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto grid max-w-6xl grid-cols-1 gap-6 px-4 sm:px-6 lg:grid-cols-3 lg:px-8">
                <div class="space-y-6 lg:col-span-2">
                    <Card>
                        <dl class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <dt class="text-gray-500">Purchase Type</dt>
                                <dd class="text-gray-800">{{ purchaseRequest.purchase_type?.name ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Employee</dt>
                                <dd class="text-gray-800">{{ purchaseRequest.employee?.name ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Division</dt>
                                <dd class="text-gray-800">{{ purchaseRequest.division?.name ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Total</dt>
                                <dd class="font-semibold text-gray-800">{{ format(purchaseRequest.total_amount) }}</dd>
                            </div>
                        </dl>
                    </Card>

                    <Card>
                        <h3 class="mb-3 text-sm font-medium text-gray-500">Line Items</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-2 py-2 text-left text-xs font-medium uppercase text-gray-500">Product</th>
                                        <th class="px-2 py-2 text-right text-xs font-medium uppercase text-gray-500">Qty</th>
                                        <th class="px-2 py-2 text-right text-xs font-medium uppercase text-gray-500">Price</th>
                                        <th class="px-2 py-2 text-right text-xs font-medium uppercase text-gray-500">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody v-auto-animate class="divide-y divide-gray-100">
                                    <tr v-for="line in purchaseRequest.lines" :key="line.id">
                                        <td class="px-2 py-2 text-sm text-gray-700">{{ line.product?.name }}</td>
                                        <td class="px-2 py-2 text-right text-sm text-gray-700">{{ line.qty }}</td>
                                        <td class="px-2 py-2 text-right text-sm text-gray-700">{{ format(line.price_estimate) }}</td>
                                        <td class="px-2 py-2 text-right text-sm text-gray-700">{{ format(line.subtotal) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </Card>

                    <Card v-if="approval.status === 'pending'">
                        <InputLabel for="remarks" value="Remarks (required to reject)" />
                        <textarea
                            id="remarks"
                            v-model="form.remarks"
                            rows="3"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <div class="mt-4 flex justify-end gap-3">
                            <DangerButton :disabled="form.processing" @click="reject">Reject</DangerButton>
                            <PrimaryButton :disabled="form.processing" @click="approve">Approve</PrimaryButton>
                        </div>
                    </Card>
                </div>

                <div>
                    <Card>
                        <h3 class="mb-3 text-sm font-medium text-gray-500">Approval Timeline</h3>
                        <ApprovalTimeline v-if="purchaseRequest.approvals?.length" :approvals="purchaseRequest.approvals" />
                    </Card>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
