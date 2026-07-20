<script setup lang="ts">
import { onMounted, ref } from 'vue';

withDefaults(
    defineProps<{
        size?: 'default' | 'sm';
    }>(),
    {
        size: 'default',
    },
);

const model = defineModel<string | number>({ required: true });

const input = ref<HTMLInputElement | null>(null);

onMounted(() => {
    if (input.value?.hasAttribute('autofocus')) {
        input.value?.focus();
    }
});

defineExpose({ focus: () => input.value?.focus() });
</script>

<template>
    <input
        v-model="model"
        ref="input"
        class="ui-input block disabled:cursor-not-allowed"
        :class="{ 'ui-input-sm': size === 'sm' }"
    />
</template>
