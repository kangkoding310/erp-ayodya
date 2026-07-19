<script setup lang="ts">
import type { PurchaseApproval } from '@/types/models';
import StatusBadge from '@/Components/approval/StatusBadge.vue';

defineProps<{ approvals: PurchaseApproval[] }>();
</script>

<template>
    <ol class="space-y-4">
        <li v-for="(approval, index) in approvals" :key="approval.id" class="flex items-start gap-3">
            <div class="flex flex-col items-center">
                <span
                    class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-semibold"
                    :class="
                        approval.status === 'approved'
                            ? 'bg-green-600 text-white'
                            : approval.status === 'rejected'
                              ? 'bg-red-600 text-white'
                              : 'bg-gray-300 text-gray-700'
                    "
                >
                    {{ approval.approval_matrix_level?.level ?? index + 1 }}
                </span>
                <span v-if="index < approvals.length - 1" class="mt-1 h-full w-px flex-1 bg-gray-200" />
            </div>
            <div class="pb-4">
                <p class="text-sm font-medium text-gray-800">
                    {{ approval.approver?.name ?? 'Unknown approver' }}
                </p>
                <StatusBadge :status="approval.status" class="mt-1" />
                <p v-if="approval.remarks" class="mt-1 text-sm text-gray-500">"{{ approval.remarks }}"</p>
                <p v-if="approval.approved_at" class="mt-1 text-xs text-gray-400">
                    {{ new Date(approval.approved_at).toLocaleString() }}
                </p>
            </div>
        </li>
    </ol>
</template>
