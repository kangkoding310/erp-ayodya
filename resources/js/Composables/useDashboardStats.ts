import { icons } from '@/Constants/icons';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import { computed, type Ref } from 'vue';

export interface DashboardSummary {
    my_purchase_requests: number;
    pending_my_approval: number;
    total_outstanding_bills: number;
    purchase_requests_in_rfq: number;
}

export interface MonthlyTotal {
    month: string;
    total: string;
}

export interface StatCardTone {
    bg: string;
    iconBg: string;
    iconFg: string;
}

export interface StatCard {
    label: string;
    value: string | number;
    icon: string;
    tone: StatCardTone;
}

const tones: StatCardTone[] = [
    { bg: 'bg-amber-50', iconBg: 'bg-amber-100', iconFg: 'text-amber-600' },
    { bg: 'bg-blue-50', iconBg: 'bg-blue-100', iconFg: 'text-blue-600' },
    { bg: 'bg-teal-50', iconBg: 'bg-teal-100', iconFg: 'text-teal-600' },
    { bg: 'bg-pink-50', iconBg: 'bg-pink-100', iconFg: 'text-pink-600' },
];

/**
 * Formats raw dashboard props (as sent by DashboardController) into
 * presentation-ready stat cards and chart series. Keeping this here means
 * Dashboard/Index.vue only has to render, never compute.
 */
export function useDashboardStats(
    summary: Ref<DashboardSummary>,
    monthlyPurchaseTotals: Ref<MonthlyTotal[]>,
    monthlyExpenseTotals: Ref<MonthlyTotal[]>,
) {
    const { format } = useCurrencyFormat();

    const cards = computed<StatCard[]>(() => [
        { label: 'My Purchase Requests', value: summary.value.my_purchase_requests, icon: icons.document, tone: tones[0] },
        { label: 'Pending My Approval', value: summary.value.pending_my_approval, icon: icons.check, tone: tones[1] },
        { label: 'Outstanding Bills', value: format(summary.value.total_outstanding_bills), icon: icons.cash, tone: tones[2] },
        { label: 'Requests in RFQ', value: summary.value.purchase_requests_in_rfq, icon: icons.truck, tone: tones[3] },
    ]);

    const purchaseChart = computed(() => ({
        labels: monthlyPurchaseTotals.value.map((row) => row.month),
        values: monthlyPurchaseTotals.value.map((row) => Number(row.total)),
    }));

    const expenseChart = computed(() => ({
        labels: monthlyExpenseTotals.value.map((row) => row.month),
        values: monthlyExpenseTotals.value.map((row) => Number(row.total)),
    }));

    return { cards, purchaseChart, expenseChart };
}
