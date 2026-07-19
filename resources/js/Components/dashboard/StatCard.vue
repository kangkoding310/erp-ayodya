<script setup lang="ts">
import type { StatCard } from '@/Composables/useDashboardStats';

defineProps<{ card: StatCard }>();
</script>

<template>
    <div class="rounded-2xl p-5 shadow-sm transition-transform duration-200 hover:-translate-y-0.5" :class="card.tone.bg">
        <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl" :class="card.tone.iconBg">
                <svg class="h-6 w-6" :class="card.tone.iconFg" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="card.icon" />
                </svg>
            </div>
            <div class="min-w-0">
                <p class="truncate text-xl font-semibold text-gray-900">{{ card.value }}</p>
                <p class="truncate text-sm text-gray-500">{{ card.label }}</p>
            </div>
        </div>
    </div>
</template>
