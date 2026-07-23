<script setup lang="ts">
import { icons } from '@/Constants/icons';
import type { NavModule } from '@/Composables/useModuleNav';
import { Link } from '@inertiajs/vue3';

defineProps<{
    module: NavModule;
    open: boolean;
    collapsed: boolean;
    collapsible?: boolean;
}>();

defineEmits<{ close: []; 'toggle-collapse': [] }>();
</script>

<template>
    <div v-if="open" class="fixed inset-0 z-20 bg-black/30 lg:hidden" @click="$emit('close')" />

    <aside
        class="fixed inset-y-0 left-0 z-30 w-64 transform overflow-y-auto rounded-r-2xl border border-gray-100 bg-white p-4 shadow-lg transition-all duration-200 lg:sticky lg:top-6 lg:z-auto lg:shrink-0 lg:translate-x-0 lg:rounded-2xl lg:shadow-sm"
        :class="[open ? 'translate-x-0' : '-translate-x-full', collapsed ? 'lg:w-16' : 'lg:w-64']"
    >
        <div class="mb-3 flex items-center gap-2 px-2" :class="{ 'lg:justify-center lg:px-0': collapsed }">
            <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-blue-600">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="icons.chartBar" />
                </svg>
            </span>
            <h3 class="text-sm font-semibold text-gray-800" :class="{ 'lg:hidden': collapsed }">{{ module.label }}</h3>
        </div>

        <nav class="space-y-1" v-auto-animate>
            <Link
                v-for="child in module.children"
                :key="child.href"
                :href="child.href"
                :title="collapsed ? child.label : undefined"
                class="flex items-center justify-between rounded-xl px-3 py-2 text-sm transition-colors"
                :class="[
                    child.active ? 'bg-blue-50 font-medium text-blue-700' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900',
                    { 'lg:justify-center lg:px-2': collapsed },
                ]"
                @click="$emit('close')"
            >
                <span v-if="collapsed" class="hidden lg:flex lg:h-5 lg:w-5 lg:shrink-0 lg:items-center lg:justify-center">
                    <svg v-if="child.icon" class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="child.icon" />
                    </svg>
                    <span v-else class="text-xs font-semibold uppercase">{{ child.label.charAt(0) }}</span>
                </span>
                <span :class="{ 'lg:hidden': collapsed }">{{ child.label }}</span>
                <svg v-if="child.active" class="h-4 w-4 shrink-0 text-blue-500" :class="{ 'lg:hidden': collapsed }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="icons.chevronRight" />
                </svg>
            </Link>
        </nav>

        <button
            v-if="collapsible"
            type="button"
            class="mt-3 hidden w-full items-center justify-center gap-1.5 rounded-xl py-2 text-xs font-medium text-gray-400 transition-colors hover:bg-gray-50 hover:text-gray-700 lg:flex"
            :title="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
            @click="$emit('toggle-collapse')"
        >
            <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="collapsed ? icons.chevronRight : icons.chevronLeft" />
            </svg>
            <span v-if="!collapsed">Collapse</span>
        </button>
    </aside>
</template>
