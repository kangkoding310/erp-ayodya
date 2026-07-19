<script setup lang="ts">
import { icons } from '@/Constants/icons';
import type { NavModule } from '@/Composables/useModuleNav';
import { Link } from '@inertiajs/vue3';

defineProps<{
    modules: NavModule[];
    showSidebarToggle: boolean;
}>();

defineEmits<{ 'toggle-sidebar': [] }>();
</script>

<template>
    <nav class="flex items-center gap-1.5 overflow-x-auto px-4 py-3 sm:px-6" v-auto-animate>
        <button
            v-if="showSidebarToggle"
            class="mr-1 shrink-0 rounded-lg p-2 text-gray-500 transition-colors hover:bg-gray-100 lg:hidden"
            @click="$emit('toggle-sidebar')"
        >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="icons.menu" />
            </svg>
        </button>

        <Link
            v-for="mod in modules"
            :key="mod.key"
            :href="mod.href"
            class="flex shrink-0 items-center gap-2 rounded-2xl py-2 pl-2 pr-3 text-sm font-medium transition-all duration-200"
            :class="mod.active ? 'bg-blue-50 text-blue-900 shadow-sm' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-800'"
        >
            <span
                class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full transition-colors"
                :class="mod.active ? 'bg-white text-blue-600 shadow-sm' : 'bg-gray-100 text-gray-500'"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="mod.icon" />
                </svg>
            </span>
            <span :class="mod.active ? 'font-semibold' : ''">{{ mod.label }}</span>
        </Link>
    </nav>
</template>
