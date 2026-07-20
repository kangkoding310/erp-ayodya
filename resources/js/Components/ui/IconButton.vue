<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

withDefaults(
    defineProps<{
        as?: 'button' | 'Link';
        variant?: 'edit' | 'delete';
        title: string;
        type?: 'button' | 'submit';
        href?: string;
    }>(),
    { as: 'button', variant: 'edit', type: 'button' },
);
</script>

<template>
    <component
        :is="as === 'Link' ? Link : 'button'"
        :type="as === 'Link' ? undefined : type"
        :href="as === 'Link' ? href : undefined"
        :title="title"
        :aria-label="title"
        class="inline-flex rounded p-1.5"
        :class="
            variant === 'delete'
                ? 'text-red-600 hover:bg-red-50 hover:text-red-700'
                : 'text-blue-600 hover:bg-blue-50 hover:text-blue-700'
        "
    >
        <slot />
    </component>
</template>
