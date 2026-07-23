<script setup lang="ts">
import DangerButton from '@/Components/DangerButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import Modal from '@/Components/Modal.vue';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import type { ExpenseReportLine } from '@/types/models';
import { computed, reactive, watch } from 'vue';

const props = defineProps<{
    show: boolean;
    lines: ExpenseReportLine[];
    processing?: boolean;
}>();

const emit = defineEmits<{
    close: [];
    confirm: [payload: { lines: { line_id: number; remarks: string }[]; bulk_remarks: string | null }];
}>();

const { format } = useCurrencyFormat();

const perLineRemarks = reactive<Record<number, string>>({});
const bulkRemark = reactive({ value: '' });

watch(
    () => props.show,
    (show) => {
        if (show) {
            for (const line of props.lines) {
                perLineRemarks[line.id] = '';
            }
            bulkRemark.value = '';
        }
    },
);

const missingLineIds = computed(() =>
    props.lines.filter((line) => !perLineRemarks[line.id]?.trim() && !bulkRemark.value.trim()).map((line) => line.id),
);

const canSubmit = computed(() => missingLineIds.value.length === 0);

const submit = () => {
    if (!canSubmit.value) return;

    emit('confirm', {
        lines: props.lines.map((line) => ({ line_id: line.id, remarks: perLineRemarks[line.id]?.trim() ?? '' })),
        bulk_remarks: bulkRemark.value.trim() || null,
    });
};
</script>

<template>
    <Modal :show="show" max-width="lg" @close="emit('close')">
        <div class="p-6">
            <h3 class="text-lg font-medium text-gray-900">Reject {{ lines.length }} item{{ lines.length > 1 ? 's' : '' }}</h3>
            <p class="mt-1 text-sm text-gray-500">Provide a remark for each item being rejected.</p>

            <div class="mt-4 max-h-80 space-y-4 overflow-y-auto pr-1">
                <div v-for="line in lines" :key="line.id" class="rounded-md border border-gray-200 p-3">
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-medium text-gray-800">{{ line.expense_category?.name ?? 'Line item' }}</span>
                        <span class="text-gray-500">{{ format(line.total) }}</span>
                    </div>
                    <p v-if="line.description" class="mt-0.5 text-xs text-gray-500">{{ line.description }}</p>
                    <textarea
                        v-model="perLineRemarks[line.id]"
                        rows="2"
                        placeholder="Remark for this item (optional if using the remark for all below)"
                        class="mt-2 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    />
                    <p v-if="missingLineIds.includes(line.id)" class="mt-1 text-xs text-red-600">A remark is required for this item.</p>
                </div>
            </div>

            <div v-if="lines.length > 1" class="mt-4">
                <InputLabel for="bulk_remark" value="Remark for all (optional)" />
                <textarea
                    id="bulk_remark"
                    v-model="bulkRemark.value"
                    rows="2"
                    placeholder="Used for any item left blank above"
                    class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <SecondaryButton type="button" @click="emit('close')">Cancel</SecondaryButton>
                <DangerButton type="button" :disabled="!canSubmit || processing" @click="submit">Confirm Reject</DangerButton>
            </div>
        </div>
    </Modal>
</template>
