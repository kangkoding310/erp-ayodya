<script setup lang="ts">
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextInput from '@/Components/TextInput.vue';
import ExpenseApprovalHistory from '@/Components/approval/ExpenseApprovalHistory.vue';
import StatusBadge from '@/Components/approval/StatusBadge.vue';
import Card from '@/Components/ui/Card.vue';
import CurrencyInput from '@/Components/ui/CurrencyInput.vue';
import FileUpload from '@/Components/ui/FileUpload.vue';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { ExpenseCategory, ExpenseHistoryEvent, ExpenseReport, Project } from '@/types/models';
import { Head, router, useForm } from '@inertiajs/vue3';
import { formatDate } from '@vueuse/core';
import { computed } from 'vue';

const props = defineProps<{
    expenseReport: ExpenseReport;
    events: ExpenseHistoryEvent[];
    expenseCategories: ExpenseCategory[];
    projects: Project[];
}>();

const { format } = useCurrencyFormat();

const expenseCategoryOptions = computed(() => props.expenseCategories.map((c) => ({ id: c.id, text: c.name })));
const projectOptions = computed(() => props.projects.map((p) => ({ id: p.id, text: p.name })));

const cancellableStatuses = ['draft', 'submitted', 'in_approval', 'needs_revision'];
const canCancel = computed(() => cancellableStatuses.includes(props.expenseReport.status));

const rejectedLines = computed(() => (props.expenseReport.lines ?? []).filter((line) => line.status === 'rejected'));

const latestRemark = (lineId: number) => {
    const line = (props.expenseReport.lines ?? []).find((l) => l.id === lineId);
    const rejected = (line?.line_approvals ?? []).filter((a) => a.status === 'rejected').sort((a, b) => (b.approved_at ?? '').localeCompare(a.approved_at ?? ''));
    return rejected[0]?.remarks ?? null;
};

const resubmitForm = useForm<{
    lines: {
        id: number;
        expense_date: string;
        expense_category_id: number | '';
        project_id: number | '';
        description: string;
        total: number;
        attachment: File | null;
    }[];
}>({
    lines: rejectedLines.value.map((line) => ({
        id: line.id,
        expense_date: formatDate(new Date(line.expense_date), 'YYYY-MM-DD'),
        expense_category_id: line.expense_category_id,
        project_id: line.project_id ?? '',
        description: line.description ?? '',
        total: Number(line.total),
        attachment: null,
    })),
});

const currentMedia = (lineId: number) => (props.expenseReport.lines ?? []).find((l) => l.id === lineId)?.media?.[0] ?? null;

const isImageMedia = (lineId: number) => /\.(png|jpe?g|gif|webp)$/i.test(currentMedia(lineId)?.file_name ?? '');

const resubmit = () => {
    resubmitForm.post(route('expense.reports.resubmit', props.expenseReport.id));
};

const submit = () => router.post(route('expense.reports.submit', props.expenseReport.id));
const sendToAccounting = () => router.post(route('expense.reports.send-to-accounting', props.expenseReport.id));
const destroy = () => {
    if (confirm('Delete this draft expense report?')) {
        router.delete(route('expense.reports.destroy', props.expenseReport.id));
    }
};
const cancel = () => {
    if (confirm('Cancel this expense report?')) {
        router.post(route('expense.reports.cancel', props.expenseReport.id));
    }
};
</script>

<template>
    <Head :title="expenseReport.code" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ expenseReport.code }}</h2>
                <StatusBadge :status="expenseReport.status" />
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto grid grid-cols-1 gap-6 px-4 sm:px-6 pl-0 lg:grid-cols-3 lg:px-8">
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

                        <div class="mt-6 flex justify-end gap-3 border-t border-gray-100 pt-4">
                            <template v-if="expenseReport.status === 'draft'">
                                <SecondaryButton @click="destroy">Delete</SecondaryButton>
                                <SecondaryButton v-if="canCancel" @click="cancel">Cancel</SecondaryButton>
                                <PrimaryButton @click="submit">Submit</PrimaryButton>
                            </template>
                            <template v-else-if="expenseReport.status === 'approved'">
                                <PrimaryButton @click="sendToAccounting">Send to Accounting</PrimaryButton>
                            </template>
                            <template v-else-if="canCancel">
                                <SecondaryButton @click="cancel">Cancel</SecondaryButton>
                            </template>
                        </div>
                    </Card>

                    <Card>
                        <h3 class="mb-3 text-sm font-medium text-gray-500">Expense Lines</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead>
                                    <tr>
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
                                            <StatusBadge :status="line.status" />
                                            <p v-if="line.status === 'rejected' && latestRemark(line.id)" class="mt-1 text-xs text-gray-500">
                                                "{{ latestRemark(line.id) }}"
                                            </p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </Card>

                    <Card v-if="expenseReport.status === 'needs_revision' && rejectedLines.length">
                        <h3 class="mb-1 text-sm font-medium text-gray-500">Revise Rejected Items</h3>
                        <p class="mb-3 text-xs text-gray-400">Update the items below, then resubmit for approval.</p>

                        <div class="space-y-4">
                            <div v-for="(line, index) in resubmitForm.lines" :key="line.id" class="rounded-md border border-gray-200 p-3">
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div>
                                        <TextInput v-model="line.expense_date" type="date" size="sm" class="w-full" />
                                    </div>
                                    <div>
                                        <SelectInput
                                            v-model="line.expense_category_id"
                                            :options="expenseCategoryOptions"
                                            placeholder="Select category"
                                            size="sm"
                                            class="block w-full"
                                        />
                                    </div>
                                    <div>
                                        <SelectInput
                                            v-model="line.project_id"
                                            :options="projectOptions"
                                            placeholder="Select project"
                                            size="sm"
                                            allow-clear
                                            class="block w-full"
                                        />
                                    </div>
                                    <div>
                                        <CurrencyInput v-model="line.total" size="sm" class="w-full" />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <TextInput v-model="line.description" type="text" size="sm" placeholder="Description" class="w-full" />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <div v-if="currentMedia(line.id)" class="mb-2 flex items-center gap-2 text-xs text-gray-500">
                                            <span>Current attachment:</span>
                                            <img
                                                v-if="isImageMedia(line.id)"
                                                :src="currentMedia(line.id)?.original_url"
                                                class="h-8 w-8 rounded border border-gray-200 object-cover"
                                            />
                                            <a :href="currentMedia(line.id)?.original_url" target="_blank" class="truncate text-blue-600 hover:underline">
                                                {{ currentMedia(line.id)?.file_name }}
                                            </a>
                                        </div>
                                        <FileUpload v-model="line.attachment" label="Drop replacement attachment or browse" size="sm" />
                                    </div>
                                </div>
                                <p v-if="(resubmitForm.errors as Record<string, string>)[`lines.${index}.expense_date`]" class="mt-2 text-xs text-red-600">
                                    {{ (resubmitForm.errors as Record<string, string>)[`lines.${index}.expense_date`] }}
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 flex justify-end">
                            <PrimaryButton :disabled="resubmitForm.processing" @click="resubmit">Resubmit for Approval</PrimaryButton>
                        </div>
                    </Card>
                </div>

                <div>
                    <Card v-if="events.length">
                        <h3 class="mb-3 text-sm font-medium text-gray-500">Approval History</h3>
                        <ExpenseApprovalHistory :events="events" />
                    </Card>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
