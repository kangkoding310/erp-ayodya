<script setup lang="ts">
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import { useLineItems } from '@/Composables/useLineItems';
import type { Product } from '@/types/models';
import { computed } from 'vue';

export interface DraftLine {
    product_id: number | '';
    description: string;
    qty: number;
    price_estimate: number;
}

const props = defineProps<{
    lines: DraftLine[];
    products: Product[];
    errors?: Record<string, string>;
}>();

const emit = defineEmits<{ 'update:lines': [DraftLine[]] }>();

const lines = computed({
    get: () => props.lines,
    set: (value) => emit('update:lines', value),
});

const { format } = useCurrencyFormat();

const { add, remove, total } = useLineItems<DraftLine>(
    lines,
    () => ({ product_id: '', description: '', qty: 1, price_estimate: 0 }),
    (line) => Number(line.qty) * Number(line.price_estimate),
);

const onProductChange = (line: DraftLine) => {
    const product = props.products.find((p) => p.id === line.product_id);
    if (product) {
        line.price_estimate = Number(product.price);
    }
};
</script>

<template>
    <div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase text-gray-500">Product</th>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase text-gray-500">Description</th>
                        <th class="w-24 px-2 py-2 text-left text-xs font-medium uppercase text-gray-500">Qty</th>
                        <th class="w-36 px-2 py-2 text-left text-xs font-medium uppercase text-gray-500">Price</th>
                        <th class="w-36 px-2 py-2 text-left text-xs font-medium uppercase text-gray-500">Subtotal</th>
                        <th class="w-10" />
                    </tr>
                </thead>
                <tbody v-auto-animate class="divide-y divide-gray-100">
                    <tr v-for="(line, index) in lines" :key="index">
                        <td class="px-2 py-2">
                            <select
                                v-model="line.product_id"
                                class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                @change="onProductChange(line)"
                            >
                                <option value="" disabled>Select product</option>
                                <option v-for="product in products" :key="product.id" :value="product.id">{{ product.name }}</option>
                            </select>
                        </td>
                        <td class="px-2 py-2">
                            <input
                                v-model="line.description"
                                type="text"
                                class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </td>
                        <td class="px-2 py-2">
                            <input
                                v-model.number="line.qty"
                                type="number"
                                min="0.01"
                                step="0.01"
                                class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </td>
                        <td class="px-2 py-2">
                            <input
                                v-model.number="line.price_estimate"
                                type="number"
                                min="0"
                                step="0.01"
                                class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </td>
                        <td class="px-2 py-2 text-sm text-gray-700">
                            {{ format(Number(line.qty) * Number(line.price_estimate)) }}
                        </td>
                        <td class="px-2 py-2 text-right">
                            <button type="button" class="text-red-600 hover:underline" @click="remove(index)">&times;</button>
                        </td>
                    </tr>
                    <tr v-if="lines.length === 0">
                        <td colspan="6" class="px-2 py-4 text-center text-sm text-gray-400">No line items yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p v-if="errors?.lines" class="mt-2 text-sm text-red-600">{{ errors.lines }}</p>

        <div class="mt-3 flex items-center justify-between">
            <SecondaryButton type="button" @click="add">Add Line</SecondaryButton>
            <p class="text-sm font-semibold text-gray-800">Total: {{ format(total) }}</p>
        </div>
    </div>
</template>
