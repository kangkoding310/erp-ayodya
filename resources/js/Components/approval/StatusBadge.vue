<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{ status: string }>();

const colorMap: Record<string, string> = {
    draft: 'bg-gray-100 text-gray-700',
    submitted: 'bg-blue-100 text-blue-700',
    in_approval: 'bg-yellow-100 text-yellow-700',
    pending: 'bg-yellow-100 text-yellow-700',
    approved: 'bg-green-100 text-green-700',
    rejected: 'bg-red-100 text-red-700',
    cancelled: 'bg-red-100 text-red-700',
    in_rfq: 'bg-purple-100 text-purple-700',
    sent: 'bg-purple-100 text-purple-700',
    sent_to_accounting: 'bg-indigo-100 text-indigo-700',
    unpaid: 'bg-yellow-100 text-yellow-700',
    paid: 'bg-green-100 text-green-700',
};

const classes = computed(() => colorMap[props.status] ?? 'bg-gray-100 text-gray-700');
const label = computed(() => props.status.replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase()));
</script>

<template>
    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium capitalize" :class="classes">
        {{ label }}
    </span>
</template>
