<script setup lang="ts">
import {
    CategoryScale,
    Chart as ChartJS,
    Legend,
    LineElement,
    LinearScale,
    PointElement,
    Tooltip,
    type ChartData,
    type ChartOptions,
} from 'chart.js';
import { computed } from 'vue';
import { Line } from 'vue-chartjs';

ChartJS.register(CategoryScale, LinearScale, PointElement, LineElement, Tooltip, Legend);

const props = defineProps<{
    labels: string[];
    values: number[];
    label?: string;
}>();

const data = computed<ChartData<'line'>>(() => ({
    labels: props.labels,
    datasets: [
        {
            label: props.label ?? 'Total',
            data: props.values,
            borderColor: '#6366f1',
            backgroundColor: '#6366f1',
            tension: 0.4,
            pointRadius: 3,
        },
    ],
}));

const options: ChartOptions<'line'> = {
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
        <Line :data="data" :options="options" />
    </div>
</template>
