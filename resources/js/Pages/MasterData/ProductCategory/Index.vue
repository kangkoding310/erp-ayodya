<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Card from '@/Components/ui/Card.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Paginated, ProductCategory } from '@/types/models';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps<{
    productCategories: Paginated<ProductCategory>;
    filters: { search?: string };
}>();

const showModal = ref(false);
const editing = ref<ProductCategory | null>(null);

const form = useForm({
    code: '',
    name: '',
});

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEdit = (category: ProductCategory) => {
    editing.value = category;
    form.code = category.code;
    form.name = category.name;
    form.clearErrors();
    showModal.value = true;
};

const submit = () => {
    if (editing.value) {
        form.put(route('master-data.product-categories.update', editing.value.id), {
            onSuccess: () => (showModal.value = false),
        });
    } else {
        form.post(route('master-data.product-categories.store'), {
            onSuccess: () => (showModal.value = false),
        });
    }
};

const destroy = (category: ProductCategory) => {
    if (confirm(`Delete category"${category.name}"?`)) {
        router.delete(route('master-data.product-categories.destroy', category.id));
    }
};
</script>

<template>
    <Head title="Product Categories" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Product Categories</h2>
                <PrimaryButton @click="openCreate">Add Category</PrimaryButton>
            </div>
        </template>

        <div class="py-1">
            <div class="mx-auto max-w-6xl ">
                <Card :padded="false">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Code</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Name</th>
                                    <th class="px-4 py-3" />
                                </tr>
                            </thead>
                            <tbody v-auto-animate class="divide-y divide-gray-100">
                                <tr v-for="category in productCategories.data" :key="category.id">
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ category.code }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ category.name }}</td>
                                    <td class="px-4 py-3 text-right text-sm">
                                        <button class="mr-3 text-blue-600 hover:underline" @click="openEdit(category)">Edit</button>
                                        <button class="text-red-600 hover:underline" @click="destroy(category)">Delete</button>
                                    </td>
                                </tr>
                                <tr v-if="productCategories.data.length === 0">
                                    <td colspan="3" class="px-4 py-6 text-center text-sm text-gray-400">No categories yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <Pagination :paginator="productCategories" />
                </Card>
            </div>
        </div>

        <Modal :show="showModal" max-width="md" @close="showModal = false">
            <form class="p-6" @submit.prevent="submit">
                <h2 class="text-lg font-medium text-gray-900">
                    {{ editing ? 'Edit Category' : 'Add Category' }}
                </h2>

                <div class="mt-4">
                    <InputLabel for="code" value="Code" />
                    <TextInput id="code" v-model="form.code" class="mt-1 block w-full" />
                    <InputError :message="form.errors.code" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="name" value="Name" />
                    <TextInput id="name" v-model="form.name" class="mt-1 block w-full" />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="showModal = false">Cancel</SecondaryButton>
                    <PrimaryButton :disabled="form.processing">Save</PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
