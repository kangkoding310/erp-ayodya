<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        min: number;
        max: number;
        step?: number;
    }>(),
    {
        step: 1,
    },
);

const model = defineModel<[number, number]>({ required: true });

const lower = computed({
    get: () => Math.min(model.value[0], model.value[1]),
    set: (value: number) => {
        model.value = [Math.min(value, model.value[1]), model.value[1]];
    },
});

const upper = computed({
    get: () => Math.max(model.value[0], model.value[1]),
    set: (value: number) => {
        model.value = [model.value[0], Math.max(value, model.value[0])];
    },
});

const range = computed(() => Math.max(props.max - props.min, 1));
const lowerPercent = computed(() => ((lower.value - props.min) / range.value) * 100);
const upperPercent = computed(() => ((upper.value - props.min) / range.value) * 100);
</script>

<template>
    <div class="relative h-6 w-full">
        <div class="absolute top-1/2 h-1.5 w-full -translate-y-1/2 rounded-full bg-gray-200"></div>
        <div
            class="absolute top-1/2 h-1.5 -translate-y-1/2 rounded-full bg-blue-500"
            :style="{ left: lowerPercent + '%', right: 100 - upperPercent + '%' }"
        ></div>
        <input
            v-model.number="lower"
            type="range"
            :min="min"
            :max="max"
            :step="step"
            class="range-thumb pointer-events-none absolute inset-x-0 top-1/2 h-1.5 w-full -translate-y-1/2 appearance-none bg-transparent"
        />
        <input
            v-model.number="upper"
            type="range"
            :min="min"
            :max="max"
            :step="step"
            class="range-thumb pointer-events-none absolute inset-x-0 top-1/2 h-1.5 w-full -translate-y-1/2 appearance-none bg-transparent"
        />
    </div>
</template>

<style scoped>
.range-thumb::-webkit-slider-thumb {
    pointer-events: auto;
    -webkit-appearance: none;
    appearance: none;
    height: 16px;
    width: 16px;
    border-radius: 9999px;
    background: #2563eb;
    border: 2px solid white;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
    cursor: pointer;
}

.range-thumb::-moz-range-thumb {
    pointer-events: auto;
    height: 16px;
    width: 16px;
    border-radius: 9999px;
    background: #2563eb;
    border: 2px solid white;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
    cursor: pointer;
    border-style: solid;
}

.range-thumb::-webkit-slider-runnable-track {
    background: transparent;
}

.range-thumb::-moz-range-track {
    background: transparent;
}
</style>
