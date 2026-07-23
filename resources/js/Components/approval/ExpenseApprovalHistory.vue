<script setup lang="ts">
import type { ExpenseHistoryEvent } from '@/types/models';
import { Check, X } from '@lucide/vue';
import { formatDate } from '@vueuse/core';
import { computed, ref } from 'vue';

const props = defineProps<{ events: ExpenseHistoryEvent[] }>();

const expanded = ref<Set<number>>(new Set());

const toggle = (index: number) => {
    if (expanded.value.has(index)) {
        expanded.value.delete(index);
    } else {
        expanded.value.add(index);
    }
    expanded.value = new Set(expanded.value);
};

type Kind = 'done' | 'rejected' | 'current' | 'future';

const kindOf = (type: ExpenseHistoryEvent['type']): Kind => {
    if (type === 'lines_rejected' || type === 'cancelled') return 'rejected';
    if (type === 'pending_approval') return 'current';
    if (type === 'future_approval') return 'future';
    return 'done';
};

const kinds = computed(() => props.events.map((event) => kindOf(event.type)));

const title = (event: ExpenseHistoryEvent): string => {
    switch (event.type) {
        case 'created':
            return `${event.actor?.name ?? 'Requester'} created this expense report`;
        case 'submitted':
            return 'Submitted for approval';
        case 'resubmitted':
            return 'Resubmitted for approval';
        case 'lines_approved':
            return `${event.approver.name} approved ${event.lines.length} item${event.lines.length > 1 ? 's' : ''} (Level ${event.level})`;
        case 'lines_rejected':
            return `${event.approver.name} rejected ${event.lines.length} item${event.lines.length > 1 ? 's' : ''} (Level ${event.level})`;
        case 'report_approved':
            return 'Expense report fully approved';
        case 'sent_to_accounting':
            return 'Sent to accounting';
        case 'cancelled':
            return 'Cancelled';
        case 'pending_approval':
            return `Pending Approval by ${event.approver.name} (Level ${event.level})`;
        case 'future_approval':
            return `Awaiting ${event.approver.name} (Level ${event.level})`;
    }
};

const hasLines = (event: ExpenseHistoryEvent): event is Extract<ExpenseHistoryEvent, { lines: unknown[] }> =>
    'lines' in event;
</script>

<template>
    <ol class="ol-approval-history relative">
        <li v-for="(event, index) in events" :key="index" class="flex gap-3">
            <div class="flex flex-col items-center">
                <span class="relative flex h-6 w-6 shrink-0 items-center justify-center">
                    <span v-if="kinds[index] === 'current'" class="absolute inline-flex h-full w-full animate-ping rounded-full bg-blue-400 opacity-60" />
                    <span
                        class="relative flex h-6 w-6 shrink-0 items-center justify-center rounded-full"
                        :class="{
                            'bg-green-600 text-white': kinds[index] === 'done',
                            'bg-red-600 text-white': kinds[index] === 'rejected',
                            'bg-white ring-2 ring-blue-500': kinds[index] === 'current',
                            'bg-white ring-2 ring-gray-200': kinds[index] === 'future',
                        }"
                    >
                        <Check v-if="kinds[index] === 'done'" class="h-3.5 w-3.5" />
                        <X v-else-if="kinds[index] === 'rejected'" class="h-3.5 w-3.5" />
                        <span v-else-if="kinds[index] === 'current'" class="h-2.5 w-2.5 rounded-full bg-blue-500" />
                    </span>
                </span>
                <span v-if="index < events.length - 1" class="w-px flex-1 bg-gray-200" />
            </div>
            <div class="pb-4">
                <p class="text-sm font-medium" :class="kinds[index] === 'future' ? 'text-gray-400' : 'text-gray-800'">
                    {{ title(event) }}
                </p>
                <p v-if="'at' in event" class="mt-1 text-xs text-gray-400">
                    {{ formatDate(new Date(event.at), 'DD/MM/YYYY hh:mm:ss A') }}
                </p>

                <template v-if="hasLines(event)">
                    <button
                        v-if="event.lines.length > 1"
                        type="button"
                        class="mt-1 text-xs font-medium text-blue-600 hover:underline"
                        @click="toggle(index)"
                    >
                        {{ expanded.has(index) ? 'Hide' : 'View' }} {{ event.lines.length }} item{{ event.lines.length > 1 ? 's' : '' }}
                    </button>

                    <div v-if="event.lines.length === 1 || expanded.has(index)" class="mt-2 space-y-1.5 overflow-auto max-h-[100px]">
                        <div v-for="line in event.lines" :key="line.id" class="rounded-md bg-gray-50 px-2.5 py-1.5 text-xs">
                            <p class="font-medium text-gray-700">{{ line.description ?? `Item #${line.id}` }}</p>
                            <p v-if="line.remarks" class="mt-0.5 text-gray-500">"{{ line.remarks }}"</p>
                        </div>
                    </div>
                </template>
            </div>
        </li>
    </ol>
</template>
