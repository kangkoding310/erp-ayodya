<script setup lang="ts">
import { BarElement, CategoryScale, Chart as ChartJS, Legend, LinearScale, Tooltip, type ChartData, type ChartOptions } from 'chart.js';
import { computed } from 'vue';
import { Bar } from 'vue-chartjs';

ChartJS.register(CategoryScale, LinearScale, BarElement, Tooltip, Legend);

const props = defineProps<{
    labels: string[];
    values: number[];
    label?: string;
}>();

const data = computed<ChartData<'bar'>>(() => ({
    labels: props.labels,
    datasets: [
        {
            label: props.label ?? 'Total',
            data: props.values,
            backgroundColor: '#6366f1',
            borderRadius: 4,
        },
    ],
}));

const options: ChartOptions<'bar'> = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
    },
    scales: {
        y: { beginAtZero: true },
    },
};
</script>

<template>
    <div class="h-64">
        <Bar :data="data" :options="options" />
    </div>
</template>
