<script setup lang="ts">
import { icons } from '@/Constants/icons';
import type { NavModule } from '@/Composables/useModuleNav';
import { Link } from '@inertiajs/vue3';

defineProps<{
    module: NavModule;
    open: boolean;
}>();

defineEmits<{ close: [] }>();
</script>

<template>
    <div v-if="open" class="fixed inset-0 z-20 bg-black/30 lg:hidden" @click="$emit('close')" />

    <aside
        class="fixed inset-y-0 left-0 z-30 w-64 transform overflow-y-auto rounded-r-2xl border border-gray-100 bg-white p-4 shadow-lg transition-transform duration-200 lg:sticky lg:top-6 lg:z-auto lg:w-64 lg:shrink-0 lg:translate-x-0 lg:rounded-2xl lg:shadow-sm"
        :class="open ? 'translate-x-0' : '-translate-x-full'"
    >
        <div class="mb-3 flex items-center gap-2 px-2">
            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-indigo-100 text-indigo-600">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="icons.chartBar" />
                </svg>
            </span>
            <h3 class="text-sm font-semibold text-gray-800">{{ module.label }}</h3>
        </div>

        <nav class="space-y-1" v-auto-animate>
            <Link
                v-for="child in module.children"
                :key="child.href"
                :href="child.href"
                class="flex items-center justify-between rounded-xl px-3 py-2 text-sm transition-colors"
                :class="child.active ? 'bg-indigo-50 font-medium text-indigo-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'"
                @click="$emit('close')"
            >
                {{ child.label }}
                <svg v-if="child.active" class="h-4 w-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="icons.chevronRight" />
                </svg>
            </Link>
        </nav>
    </aside>
</template>
