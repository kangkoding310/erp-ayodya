<script setup lang="ts">
withDefaults(defineProps<{ padded?: boolean }>(), { padded: true });
</script>

<template>
    <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">
        <div :class="padded ? 'p-6' : ''">
            <slot />
        </div>
    </div>
</template>
