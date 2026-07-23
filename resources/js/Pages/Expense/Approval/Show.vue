<script setup lang="ts">
import Checkbox from '@/Components/Checkbox.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import RejectLinesModal from '@/Components/expense/RejectLinesModal.vue';
import ExpenseApprovalHistory from '@/Components/approval/ExpenseApprovalHistory.vue';
import StatusBadge from '@/Components/approval/StatusBadge.vue';
import Card from '@/Components/ui/Card.vue';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { ExpenseHistoryEvent, ExpenseReport, ExpenseReportLine } from '@/types/models';
import { Head, router, usePage } from '@inertiajs/vue3';
import { formatDate } from '@vueuse/core';
import { computed, reactive, ref } from 'vue';

const props = defineProps<{
    expenseReport: ExpenseReport;
    myTurnLineIds: number[];
    events: ExpenseHistoryEvent[];
}>();

const { format } = useCurrencyFormat();

const authUserId = computed(() => usePage().props.auth.user.id);
const myTurnSet = computed(() => new Set(props.myTurnLineIds));
const selected = ref<Set<number>>(new Set());

const allEligibleSelected = computed(
    () => props.myTurnLineIds.length > 0 && props.myTurnLineIds.every((id) => selected.value.has(id)),
);

const toggleAll = () => {
    selected.value = allEligibleSelected.value ? new Set() : new Set(props.myTurnLineIds);
};

const toggleLine = (id: number) => {
    const next = new Set(selected.value);
    next.has(id) ? next.delete(id) : next.add(id);
    selected.value = next;
};

const selectedLines = computed(() => (props.expenseReport.lines ?? []).filter((line) => selected.value.has(line.id)));

// Status shown per line depends on the viewer: if it's their turn, "Your turn"
// takes priority; otherwise, if they already decided on this line as an approver,
// show their own decision (e.g. a level-2 approver who already approved sees
// "Approved" even while a lower level is still pending); everyone else (the
// requester, approvers at a level not yet reached, etc.) sees the line's overall status.
const lineStatus = (line: ExpenseReportLine) => {
    if (myTurnSet.value.has(line.id)) return null;

    // A line can go through the approval matrix more than once (rejected, then
    // resubmitted), so an approver may have a decision row in more than one cycle —
    // only the latest cycle's decision reflects where things actually stand now.
    const myApproval = (line.line_approvals ?? [])
        .filter((approval) => approval.approver_id === authUserId.value)
        .sort((a, b) => b.cycle - a.cycle)[0];
    if (myApproval && myApproval.status !== 'pending') {
        return myApproval.status;
    }

    return line.status;
};

const processing = ref(false);

// Approve flow: lightweight confirm with optional per-line remarks.
const showApproveModal = ref(false);
const approveRemarks = reactive<Record<number, string>>({});

const openApproveModal = () => {
    for (const line of selectedLines.value) {
        approveRemarks[line.id] = '';
    }
    showApproveModal.value = true;
};

const confirmApprove = () => {
    processing.value = true;
    router.post(
        route('expense.approvals.lines.approve', props.expenseReport.id),
        {
            lines: selectedLines.value.map((line) => ({ line_id: line.id, remarks: approveRemarks[line.id] || null })),
        },
        {
            preserveScroll: true,
            onFinish: () => {
                processing.value = false;
                showApproveModal.value = false;
                selected.value = new Set();
            },
        },
    );
};

// Reject flow: confirmation popup with per-line + bulk fallback remarks.
const showRejectModal = ref(false);

const confirmReject = (payload: { lines: { line_id: number; remarks: string }[]; bulk_remarks: string | null }) => {
    processing.value = true;
    router.post(route('expense.approvals.lines.reject', props.expenseReport.id), payload, {
        preserveScroll: true,
        onFinish: () => {
            processing.value = false;
            showRejectModal.value = false;
            selected.value = new Set();
        },
    });
};
</script>

<template>
    <Head :title="`Review ${expenseReport.code}`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Review {{ expenseReport.code }}</h2>
                <StatusBadge :status="expenseReport.status" />
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto grid grid-cols-1 gap-6 px-4 sm:px-6 lg:grid-cols-3 lg:px-8">
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
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-sm font-medium text-gray-500">Expense Lines</h3>
                            <div v-if="selected.size > 0" class="flex items-center gap-2">
                                <span class="text-xs text-gray-500">{{ selected.size }} selected</span>
                                <DangerButton type="button" :disabled="processing" @click="showRejectModal = true">
                                    Reject {{ selected.size }}
                                </DangerButton>
                                <PrimaryButton type="button" :disabled="processing" @click="openApproveModal">
                                    Approve {{ selected.size }}
                                </PrimaryButton>
                            </div>
                        </div>
                        <div class="max-h-[28rem] overflow-y-auto overflow-x-auto rounded-md border border-gray-100">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="sticky top-0 z-10 bg-gray-50">
                                    <tr>
                                        <th class="w-8 px-2 py-2">
                                            <Checkbox v-if="myTurnLineIds.length" :checked="allEligibleSelected" @update:checked="toggleAll" />
                                        </th>
                                        <th class="px-2 py-2 text-left text-xs font-medium uppercase text-gray-500">Date</th>
                                        <th class="px-2 py-2 text-left text-xs font-medium uppercase text-gray-500">Category</th>
                                        <th class="px-2 py-2 text-left text-xs font-medium uppercase text-gray-500">Project</th>
                                        <th class="px-2 py-2 text-left text-xs font-medium uppercase text-gray-500">Description</th>
                                        <th class="px-2 py-2 text-left text-xs font-medium uppercase text-gray-500">Attachment</th>
                                        <th class="px-2 py-2 text-right text-xs font-medium uppercase text-gray-500">Total</th>
                                        <th class="px-2 py-2 text-left text-xs font-medium uppercase text-gray-500">Status</th>
                                    </tr>
                                </thead>
                                <tbody v-auto-animate class="divide-y divide-gray-100">
                                    <tr v-for="line in expenseReport.lines" :key="line.id">
                                        <td class="px-2 py-2">
                                            <Checkbox
                                                v-if="myTurnSet.has(line.id)"
                                                :checked="selected.has(line.id)"
                                                @update:checked="toggleLine(line.id)"
                                            />
                                        </td>
                                        <td class="px-2 py-2 text-sm text-gray-700">{{ formatDate(new Date(line.expense_date), 'DD/MM/YYYY') }}</td>
                                        <td class="px-2 py-2 text-sm text-gray-700">{{ line.expense_category?.name }}</td>
                                        <td class="px-2 py-2 text-sm text-gray-700">
                                            <div class="line-clamp-1 break-all">
                                                {{ line.project?.name ?? '-' }}
                                            </div>
                                        </td>
                                        <td class="px-2 py-2 text-sm text-gray-500">{{ line.description ?? '-' }}</td>
                                        <td class="px-2 py-2 text-sm text-gray-700">
                                            <a
                                                v-if="line.media?.[0]"
                                                :href="line.media[0].original_url"
                                                target="_blank"
                                                class="line-clamp-1 max-w-32 text-blue-600 hover:underline"
                                            >
                                                {{ line.media[0].file_name }}
                                            </a>
                                            <span v-else>-</span>
                                        </td>
                                        <td class="px-2 py-2 text-right text-sm text-gray-700">{{ format(line.total) }}</td>
                                        <td class="px-2 py-2">
                                            <StatusBadge v-if="lineStatus(line)" :status="lineStatus(line)!" />
                                            <span v-else class="text-xs text-gray-400 whitespace-nowrap">Your turn</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </Card>
                </div>

                <div>
                    <Card>
                        <h3 class="mb-3 text-sm font-medium text-gray-500">Approval History</h3>
                        <ExpenseApprovalHistory v-if="events.length" :events="events" />
                    </Card>
                </div>
            </div>
        </div>

        <Modal :show="showApproveModal" max-width="lg" @close="showApproveModal = false">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900">Approve {{ selectedLines.length }} item{{ selectedLines.length > 1 ? 's' : '' }}</h3>
                <p class="mt-1 text-sm text-gray-500">You may optionally add a remark for each item.</p>

                <div class="mt-4 max-h-80 space-y-3 overflow-y-auto pr-1">
                    <div v-for="line in selectedLines" :key="line.id" class="rounded-md border border-gray-200 p-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-800">{{ line.expense_category?.name ?? 'Line item' }}</span>
                            <span class="text-gray-500">{{ format(line.total) }}</span>
                        </div>
                        <textarea
                            v-model="approveRemarks[line.id]"
                            rows="2"
                            placeholder="Remark (optional)"
                            class="mt-2 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        />
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton type="button" @click="showApproveModal = false">Cancel</SecondaryButton>
                    <PrimaryButton type="button" :disabled="processing" @click="confirmApprove">Confirm Approve</PrimaryButton>
                </div>
            </div>
        </Modal>

        <RejectLinesModal
            :show="showRejectModal"
            :lines="selectedLines"
            :processing="processing"
            @close="showRejectModal = false"
            @confirm="confirmReject"
        />
    </AuthenticatedLayout>
</template>
