<script setup lang="ts">
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import ApprovalTimeline from '@/Components/approval/ApprovalTimeline.vue';
import StatusBadge from '@/Components/approval/StatusBadge.vue';
import Card from '@/Components/ui/Card.vue';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { PurchaseRequest } from '@/types/models';
import { Head, router, useForm } from '@inertiajs/vue3';

const props = defineProps<{
    purchaseRequest: PurchaseRequest;
}>();

const { format } = useCurrencyFormat(props.purchaseRequest.currency);

const messageForm = useForm({ message: '' });

const sendMessage = () => {
    messageForm.post(route('purchase.requests.messages.store', props.purchaseRequest.id), {
        preserveScroll: true,
        onSuccess: () => messageForm.reset(),
    });
};

const submitForApproval = () => {
    if (confirm('Submit this purchase request for approval?')) {
        router.post(route('purchase.requests.submit', props.purchaseRequest.id));
    }
};

const destroy = () => {
    if (confirm('Delete this draft purchase request?')) {
        router.delete(route('purchase.requests.destroy', props.purchaseRequest.id));
    }
};
</script>

<template>
    <Head :title="purchaseRequest.code" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    {{ purchaseRequest.code }}
                </h2>
                <StatusBadge :status="purchaseRequest.status" />
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
                                <dt class="text-gray-500">Project</dt>
                                <dd class="text-gray-800">{{ purchaseRequest.project_name ?? '-' }}</dd>
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
                                <dt class="text-gray-500">Expected Date</dt>
                                <dd class="text-gray-800">{{ purchaseRequest.expected_date ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Total</dt>
                                <dd class="font-semibold text-gray-800">{{ format(purchaseRequest.total_amount) }}</dd>
                            </div>
                        </dl>

                        <div v-if="purchaseRequest.status === 'draft'" class="mt-6 flex justify-end gap-3 border-t border-gray-100 pt-4">
                            <SecondaryButton @click="destroy">Delete</SecondaryButton>
                            <PrimaryButton @click="submitForApproval">Submit for Approval</PrimaryButton>
                        </div>
                    </Card>

                    <Card>
                        <h3 class="mb-3 text-sm font-medium text-gray-500">Line Items</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
                                        <th class="px-2 py-2 text-left text-xs font-medium uppercase text-gray-500">Product</th>
                                        <th class="px-2 py-2 text-left text-xs font-medium uppercase text-gray-500">Description</th>
                                        <th class="px-2 py-2 text-right text-xs font-medium uppercase text-gray-500">Qty</th>
                                        <th class="px-2 py-2 text-right text-xs font-medium uppercase text-gray-500">Price</th>
                                        <th class="px-2 py-2 text-right text-xs font-medium uppercase text-gray-500">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody v-auto-animate class="divide-y divide-gray-100">
                                    <tr v-for="line in purchaseRequest.lines" :key="line.id">
                                        <td class="px-2 py-2 text-sm text-gray-700">{{ line.product?.name }}</td>
                                        <td class="px-2 py-2 text-sm text-gray-500">{{ line.description ?? '-' }}</td>
                                        <td class="px-2 py-2 text-right text-sm text-gray-700">{{ line.qty }}</td>
                                        <td class="px-2 py-2 text-right text-sm text-gray-700">{{ format(line.price_estimate) }}</td>
                                        <td class="px-2 py-2 text-right text-sm text-gray-700">{{ format(line.subtotal) }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </Card>

                    <Card>
                        <h3 class="mb-3 text-sm font-medium text-gray-500">Discussion</h3>
                        <div class="max-h-80 space-y-3 overflow-y-auto">
                            <div v-for="message in purchaseRequest.messages" :key="message.id" class="rounded-md bg-gray-50 p-3">
                                <p class="text-xs font-medium text-gray-500">
                                    {{ message.user?.name }} &middot; {{ new Date(message.created_at).toLocaleString() }}
                                </p>
                                <p class="mt-1 text-sm text-gray-800">{{ message.message }}</p>
                            </div>
                            <p v-if="!purchaseRequest.messages?.length" class="text-sm text-gray-400">No messages yet.</p>
                        </div>

                        <form class="mt-4 flex gap-2" @submit.prevent="sendMessage">
                            <TextInput v-model="messageForm.message" placeholder="Write a message..." class="block w-full" />
                            <PrimaryButton :disabled="messageForm.processing">Send</PrimaryButton>
                        </form>
                    </Card>
                </div>

                <div>
                    <Card>
                        <h3 class="mb-3 text-sm font-medium text-gray-500">Approval Timeline</h3>
                        <ApprovalTimeline v-if="purchaseRequest.approvals?.length" :approvals="purchaseRequest.approvals" />
                        <p v-else class="text-sm text-gray-400">Not yet submitted for approval.</p>
                    </Card>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
