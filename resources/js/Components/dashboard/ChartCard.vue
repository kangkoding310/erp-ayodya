<script setup lang="ts">
defineProps<{
    title: string;
    subtitle?: string;
    legendColor?: string;
    legendLabel?: string;
}>();
</script>

<template>
    <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
        <div class="mb-4 flex items-start justify-between gap-3">
            <div>
                <h3 class="text-base font-semibold text-gray-800">{{ title }}</h3>
                <p v-if="subtitle" class="text-xs text-gray-400">{{ subtitle }}</p>
            </div>
            <div v-if="legendLabel" class="flex shrink-0 items-center gap-1.5 text-xs text-gray-500">
                <span class="h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: legendColor }" />
                {{ legendLabel }}
            </div>
        </div>

        <slot />
    </div>
</template>
