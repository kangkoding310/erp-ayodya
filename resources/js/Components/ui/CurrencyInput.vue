<script setup lang="ts">
import { ref, watch } from 'vue';

withDefaults(
    defineProps<{
        size?: 'default' | 'sm';
    }>(),
    {
        size: 'sm',
    },
);

const model = defineModel<number>({ required: true });

const formatter = new Intl.NumberFormat('id-ID');

const display = ref(model.value ? formatter.format(model.value) : '');

watch(
    () => model.value,
    (value) => {
        const formatted = value ? formatter.format(value) : '';
        if (formatted !== display.value) {
            display.value = formatted;
        }
    },
);

const onInput = (e: Event) => {
    const raw = (e.target as HTMLInputElement).value.replace(/[^\d]/g, '');
    const value = raw ? Number(raw) : 0;

    model.value = value;
    display.value = raw ? formatter.format(value) : '';
};

const allowedKeys = ['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown', 'Home', 'End'];

const onKeydown = (e: KeyboardEvent) => {
    // Block anything that isn't a digit or a navigation/editing key, so
    // letters and symbols never make it into the field in the first place
    // (only stripping them after the fact caused a visible flicker).
    if (e.ctrlKey || e.metaKey || allowedKeys.includes(e.key)) {
        return;
    }

    if (!/^\d$/.test(e.key)) {
        e.preventDefault();
    }
};

const onPaste = (e: ClipboardEvent) => {
    e.preventDefault();

    const pasted = e.clipboardData?.getData('text') ?? '';
    const raw = pasted.replace(/[^\d]/g, '');
    const value = raw ? Number(raw) : 0;

    model.value = value;
    display.value = raw ? formatter.format(value) : '';
};
</script>

<template>
    <input
        :value="display"
        inputmode="numeric"
        class="ui-input block text-right disabled:cursor-not-allowed"
        :class="{ 'ui-input-sm': size === 'sm' }"
        @input="onInput"
        @keydown="onKeydown"
        @paste="onPaste"
    />
</template>
