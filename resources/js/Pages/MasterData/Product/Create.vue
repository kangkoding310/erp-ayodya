<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Card from '@/Components/ui/Card.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { ProductCategory } from '@/types/models';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps<{
    categories: ProductCategory[];
}>();

const form = useForm({
    name: '',
    price: '',
    tax_percentage: '11',
    type: '',
    product_category_id: '' as number | '',
});

const submit = () => {
    form.post(route('master-data.products.store'));
};
</script>

<template>
    <Head title="Add Product" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Add Product</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                <Card>
                    <form class="space-y-4" @submit.prevent="submit">
                        <div>
                            <InputLabel for="name" value="Name" />
                            <TextInput id="name" v-model="form.name" class="mt-1 block w-full" />
                            <InputError :message="form.errors.name" class="mt-2" />
                        </div>

                        <div>
                            <InputLabel for="product_category_id" value="Category" />
                            <select
                                id="product_category_id"
                                v-model="form.product_category_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >
                                <option value="" disabled>Select category</option>
                                <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                            </select>
                            <InputError :message="form.errors.product_category_id" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="price" value="Price" />
                                <TextInput id="price" v-model="form.price" type="number" step="0.01" class="mt-1 block w-full" />
                                <InputError :message="form.errors.price" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="tax_percentage" value="Tax %" />
                                <TextInput id="tax_percentage" v-model="form.tax_percentage" type="number" step="0.01" class="mt-1 block w-full" />
                                <InputError :message="form.errors.tax_percentage" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <InputLabel for="type" value="Type (optional)" />
                            <TextInput id="type" v-model="form.type" class="mt-1 block w-full" />
                            <InputError :message="form.errors.type" class="mt-2" />
                        </div>

                        <div class="flex justify-end gap-3">
                            <Link :href="route('master-data.products.index')">
                                <SecondaryButton type="button">Cancel</SecondaryButton>
                            </Link>
                            <PrimaryButton :disabled="form.processing">Save</PrimaryButton>
                        </div>
                    </form>
                </Card>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
