<script setup lang="ts">
import PrimaryButton from '@/Components/PrimaryButton.vue';
import Card from '@/Components/ui/Card.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Paginated, Product } from '@/types/models';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps<{
    products: Paginated<Product>;
    filters: { search?: string };
}>();

const { format } = useCurrencyFormat();

const destroy = (product: Product) => {
    if (confirm(`Delete product"${product.name}"?`)) {
        router.delete(route('master-data.products.destroy', product.id));
    }
};
</script>

<template>
    <Head title="Products" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Products</h2>
                <Link :href="route('master-data.products.create')">
                    <PrimaryButton>Add Product</PrimaryButton>
                </Link>
            </div>
        </template>

        <div class="py-1">
            <div class="mx-auto max-w-6xl ">
                <Card :padded="false">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Category</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Price</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Tax %</th>
                                    <th class="px-4 py-3" />
                                </tr>
                            </thead>
                            <tbody v-auto-animate class="divide-y divide-gray-100">
                                <tr v-for="product in products.data" :key="product.id">
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ product.name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ product.category?.name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ format(product.price) }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ product.tax_percentage }}%</td>
                                    <td class="px-4 py-3 text-right text-sm">
                                        <Link :href="route('master-data.products.edit', product.id)" class="mr-3 text-blue-600 hover:underline"
                                            >Edit</Link
                                        >
                                        <button class="text-red-600 hover:underline" @click="destroy(product)">Delete</button>
                                    </td>
                                </tr>
                                <tr v-if="products.data.length === 0">
                                    <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-400">No products yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <Pagination :paginator="products" />
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
