<script setup lang="ts">
import DangerButton from '@/Components/DangerButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ApprovalTimeline from '@/Components/approval/ApprovalTimeline.vue';
import StatusBadge from '@/Components/approval/StatusBadge.vue';
import Card from '@/Components/ui/Card.vue';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { ExpenseReportApproval } from '@/types/models';
import { Head, useForm } from '@inertiajs/vue3';
import { formatDate } from '@vueuse/core';
import { computed } from 'vue';

const props = defineProps<{
    approval: ExpenseReportApproval;
}>();

const expenseReport = props.approval.expense_report!;
const { format } = useCurrencyFormat();

// Approvals are processed from the highest matrix level down to the lowest,
// so the timeline should display them in that order too.
const orderedApprovals = computed(() =>
    [...(expenseReport.approvals ?? [])].sort((a, b) => (b.approval_matrix_level?.level ?? 0) - (a.approval_matrix_level?.level ?? 0))
);

const form = useForm({ remarks: '' });

const approve = () => {
    form.post(route('expense.approvals.approve', props.approval.id));
};

const reject = () => {
    if (!form.remarks) {
        alert('Remarks are required when rejecting.');
        return;
    }
    form.post(route('expense.approvals.reject', props.approval.id));
};
</script>

<template>
    <Head :title="`Review ${expenseReport.code}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Review {{ expenseReport.code }}</h2>
                <StatusBadge :status="approval.status" />
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto grid max-w-6xl grid-cols-1 gap-6 px-4 sm:px-6 lg:grid-cols-3 lg:px-8">
                <div class="space-y-6 lg:col-span-2">
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
                                        <th class="px-2 py-2 text-left text-xs font-medium uppercase text-gray-500">Attachment</th>
                                        <th class="px-2 py-2 text-right text-xs font-medium uppercase text-gray-500">Total</th>
                                    </tr>
                                </thead>
                                <tbody v-auto-animate class="divide-y divide-gray-100">
                                    <tr v-for="line in expenseReport.lines" :key="line.id">
                                        <td class="px-2 py-2 text-sm text-gray-700">{{ formatDate(new Date(line.expense_date), 'DD/MM/YYYY') }}</td>
                                        <td class="px-2 py-2 text-sm text-gray-700">{{ line.expense_category?.name }}</td>
                                        <td class="px-2 py-2 text-sm text-gray-500">{{ line.description ?? '-' }}</td>
                                        <td class="px-2 py-2 text-sm text-gray-700">
                                            <a
                                                v-if="line.media?.[0]"
                                                :href="line.media[0].original_url"
                                                target="_blank"
                                                class="text-blue-600 hover:underline"
                                            >
                                                {{ line.media[0].file_name }}
                                            </a>
                                            <span v-else>-</span>
                                        </td>
                                        <td class="px-2 py-2 text-right text-sm text-gray-700">{{ format(line.total) }}</td>
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
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
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
                        <ApprovalTimeline v-if="orderedApprovals.length" :approvals="orderedApprovals" />
                    </Card>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
