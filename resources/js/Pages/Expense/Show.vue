<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextInput from '@/Components/TextInput.vue';
import ExpenseApprovalHistory from '@/Components/approval/ExpenseApprovalHistory.vue';
import StatusBadge from '@/Components/approval/StatusBadge.vue';
import Card from '@/Components/ui/Card.vue';
import CurrencyInput from '@/Components/ui/CurrencyInput.vue';
import FileUpload from '@/Components/ui/FileUpload.vue';
import IconButton from '@/Components/ui/IconButton.vue';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import { useLineItems } from '@/Composables/useLineItems';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { ExpenseCategory, ExpenseHistoryEvent, ExpenseReport, ExpenseReportLine, Project } from '@/types/models';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { formatDate } from '@vueuse/core';
import { computed, ref } from 'vue';

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

// Edit lines: available while the report is still a draft, letting the requester add,
// remove, or amend items before submitting for approval. Reuses the same table shape as
// Expense/Create.vue since it posts to the same `validated()` rules on the backend.
interface EditLine {
    id: number | null;
    expense_date: string;
    expense_category_id: number | '';
    project_id: number | '';
    description: string;
    total: number;
    attachment: File | null;
}

const buildEditLine = (line?: ExpenseReportLine): EditLine =>
    line
        ? {
              id: line.id,
              expense_date: formatDate(new Date(line.expense_date), 'YYYY-MM-DD'),
              expense_category_id: line.expense_category_id,
              project_id: line.project_id ?? '',
              description: line.description ?? '',
              total: Number(line.total),
              attachment: null,
          }
        : { id: null, expense_date: '', expense_category_id: '', project_id: '', description: '', total: 0, attachment: null };

const showEditModal = ref(false);

const editForm = useForm<{
    employee_id: number;
    summary: string;
    lines: EditLine[];
}>({
    employee_id: props.expenseReport.employee_id,
    summary: props.expenseReport.summary ?? '',
    lines: [],
});

const editLinesRef = computed({
    get: () => editForm.lines,
    set: (value) => (editForm.lines = value),
});

const { add: addEditLine, remove: removeEditLine, total: editTotal } = useLineItems<EditLine>(
    editLinesRef,
    () => buildEditLine(),
    (line) => Number(line.total),
);

const openEditModal = () => {
    editForm.clearErrors();
    editForm.employee_id = props.expenseReport.employee_id;
    editForm.summary = props.expenseReport.summary ?? '';
    editForm.lines = (props.expenseReport.lines ?? []).map((line) => buildEditLine(line));
    showEditModal.value = true;
};

const submitEdit = () => {
    editForm.put(route('expense.reports.update', props.expenseReport.id), {
        preserveScroll: true,
        onSuccess: () => {
            showEditModal.value = false;
        },
    });
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
                                <SecondaryButton @click="openEditModal">Edit Lines</SecondaryButton>
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

        <Modal :show="showEditModal" max-width="5xl" @close="showEditModal = false">
            <div class="p-6">
                <h3 class="text-lg font-medium text-gray-900">Edit Expense Lines</h3>
                <p class="mt-1 text-sm text-gray-500">Add, remove, or update items, then save your changes.</p>

                <div class="mt-4 max-h-[28rem] overflow-y-auto overflow-x-auto rounded-md border border-gray-100">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="sticky top-0 z-10 bg-gray-50">
                            <tr>
                                <th class="px-2 py-2 text-left text-xs font-medium uppercase text-gray-500">Date<span class="text-red-500">&nbsp;*</span></th>
                                <th class="w-44 px-2 py-2 text-left text-xs font-medium uppercase text-gray-500">Category<span class="text-red-500">&nbsp;*</span></th>
                                <th class="w-52 px-2 py-2 text-left text-xs font-medium uppercase text-gray-500">Project<span class="text-red-500">&nbsp;*</span></th>
                                <th class="px-2 py-2 text-left text-xs font-medium uppercase text-gray-500">Description</th>
                                <th class="w-36 px-2 py-2 text-left text-xs font-medium uppercase text-gray-500">Total<span class="text-red-500">&nbsp;*</span></th>
                                <th class="w-52 px-2 py-2 text-left text-xs font-medium uppercase text-gray-500">Attachment<span class="text-red-500">&nbsp;*</span></th>
                                <th class="w-10" />
                            </tr>
                        </thead>
                        <tbody v-auto-animate class="divide-y divide-gray-100">
                            <tr v-for="(line, index) in editForm.lines" :key="line.id ?? `new-${index}`">
                                <td class="px-2 py-2 align-top">
                                    <TextInput v-model="line.expense_date" type="date" size="sm" class="w-full" />
                                    <InputError :message="(editForm.errors as Record<string, string>)[`lines.${index}.expense_date`]" class="mt-1" />
                                </td>
                                <td class="px-2 py-2 align-top">
                                    <SelectInput
                                        v-model="line.expense_category_id"
                                        :options="expenseCategoryOptions"
                                        placeholder="Select category"
                                        size="sm"
                                        class="block w-full"
                                    />
                                    <InputError :message="(editForm.errors as Record<string, string>)[`lines.${index}.expense_category_id`]" class="mt-1" />
                                </td>
                                <td class="px-2 py-2 align-top">
                                    <SelectInput
                                        v-model="line.project_id"
                                        :options="projectOptions"
                                        placeholder="Select project"
                                        size="sm"
                                        class="block w-full"
                                    />
                                    <InputError :message="(editForm.errors as Record<string, string>)[`lines.${index}.project_id`]" class="mt-1" />
                                </td>
                                <td class="px-2 py-2 align-top">
                                    <TextInput v-model="line.description" type="text" size="sm" class="w-full" />
                                </td>
                                <td class="px-2 py-2 align-top">
                                    <CurrencyInput v-model="line.total" size="sm" class="w-full" />
                                    <InputError :message="(editForm.errors as Record<string, string>)[`lines.${index}.total`]" class="mt-1" />
                                </td>
                                <td class="px-2 py-2 align-top" style="min-width: 220px">
                                    <div v-if="line.id && currentMedia(line.id)" class="mb-2 flex items-center gap-2 text-xs text-gray-500">
                                        <img
                                            v-if="isImageMedia(line.id)"
                                            :src="currentMedia(line.id)?.original_url"
                                            class="h-6 w-6 rounded border border-gray-200 object-cover"
                                        />
                                        <span class="truncate">Current: {{ currentMedia(line.id)?.file_name }}</span>
                                    </div>
                                    <FileUpload v-model="line.attachment" label="Drop or browse" size="sm" />
                                    <InputError :message="(editForm.errors as Record<string, string>)[`lines.${index}.attachment`]" class="mt-1" />
                                </td>
                                <td class="px-2 py-2 text-right align-top">
                                    <IconButton title="Remove" variant="delete" @click="removeEditLine(index)">
                                        <Trash2 class="h-4 w-4" />
                                    </IconButton>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <p v-if="(editForm.errors as Record<string, string>).lines" class="mt-2 text-sm text-red-600">
                    {{ (editForm.errors as Record<string, string>).lines }}
                </p>

                <div class="mt-3 flex items-center justify-between">
                    <SecondaryButton type="button" @click="addEditLine">Add Line</SecondaryButton>
                    <p class="text-sm font-semibold text-gray-800">Total: {{ format(editTotal) }}</p>
                </div>

                <div class="mt-6 flex justify-end gap-3 border-t border-gray-100 pt-4">
                    <SecondaryButton type="button" @click="showEditModal = false">Cancel</SecondaryButton>
                    <PrimaryButton :disabled="editForm.processing" @click="submitEdit">Save Changes</PrimaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
