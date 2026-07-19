<script setup lang="ts">
import { useNotificationStore } from '@/Stores/notifications';
import { usePage } from '@inertiajs/vue3';
import { storeToRefs } from 'pinia';
import { watch } from 'vue';

const store = useNotificationStore();
const { items } = storeToRefs(store);
const page = usePage();

watch(
    () => page.props.flash,
    (flash) => {
        if (flash?.success) store.push('success', flash.success);
        if (flash?.error) store.push('error', flash.error);
    },
    { deep: true, immediate: true },
);
</script>

<template>
    <div class="pointer-events-none fixed right-4 top-4 z-50 flex flex-col gap-2">
        <div
            v-for="item in items"
            :key="item.id"
            class="pointer-events-auto rounded-md px-4 py-3 text-sm font-medium shadow-lg"
            :class="item.type === 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'"
        >
            {{ item.message }}
        </div>
    </div>
</template>
