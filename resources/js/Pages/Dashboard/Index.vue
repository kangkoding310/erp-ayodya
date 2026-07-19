<script setup lang="ts">
import MonthlyBarChart from '@/Components/charts/MonthlyBarChart.vue';
import MonthlyLineChart from '@/Components/charts/MonthlyLineChart.vue';
import ChartCard from '@/Components/dashboard/ChartCard.vue';
import StatCard from '@/Components/dashboard/StatCard.vue';
import { useDashboardStats, type DashboardSummary, type MonthlyTotal } from '@/Composables/useDashboardStats';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import { toRef } from 'vue';

const props = defineProps<{
    summary: DashboardSummary;
    monthlyPurchaseTotals: MonthlyTotal[];
    monthlyExpenseTotals: MonthlyTotal[];
}>();

const { cards, purchaseChart, expenseChart } = useDashboardStats(
    toRef(props, 'summary'),
    toRef(props, 'monthlyPurchaseTotals'),
    toRef(props, 'monthlyExpenseTotals'),
);
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Dashboard</h2>
        </template>

        <div class="space-y-4 lg:space-y-6">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4" v-auto-animate>
                <StatCard v-for="card in cards" :key="card.label" :card="card" />
            </div>

            <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 lg:gap-6">
                <ChartCard title="Purchase Requests" subtitle="Last 6 months" legend-color="#6366f1" legend-label="Total Amount">
                    <MonthlyLineChart :labels="purchaseChart.labels" :values="purchaseChart.values" label="Total Amount" />
                </ChartCard>

                <ChartCard title="Expense Reports" subtitle="Last 6 months" legend-color="#6366f1" legend-label="Total Expense">
                    <MonthlyBarChart :labels="expenseChart.labels" :values="expenseChart.values" label="Total Expense" />
                </ChartCard>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
