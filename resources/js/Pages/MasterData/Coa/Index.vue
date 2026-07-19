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
import type { Coa, Paginated, Product } from '@/types/models';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps<{
    coa: Paginated<Coa>;
    products: Product[];
    filters: { search?: string };
}>();

const showModal = ref(false);
const editing = ref<Coa | null>(null);

const form = useForm({
    code: '',
    name: '',
    product_id: '' as number | '',
    type: '',
});

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEdit = (account: Coa) => {
    editing.value = account;
    form.code = account.code;
    form.name = account.name;
    form.product_id = account.product_id ?? '';
    form.type = account.type ?? '';
    form.clearErrors();
    showModal.value = true;
};

const submit = () => {
    if (editing.value) {
        form.put(route('master-data.coa.update', editing.value.id), {
            onSuccess: () => (showModal.value = false),
        });
    } else {
        form.post(route('master-data.coa.store'), {
            onSuccess: () => (showModal.value = false),
        });
    }
};

const destroy = (account: Coa) => {
    if (confirm(`Delete COA"${account.name}"?`)) {
        router.delete(route('master-data.coa.destroy', account.id));
    }
};
</script>

<template>
    <Head title="Chart of Accounts" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Chart of Accounts</h2>
                <PrimaryButton @click="openCreate">Add Account</PrimaryButton>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <Card :padded="false">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Code</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Product</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Type</th>
                                    <th class="px-4 py-3" />
                                </tr>
                            </thead>
                            <tbody v-auto-animate class="divide-y divide-gray-100">
                                <tr v-for="account in coa.data" :key="account.id">
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ account.code }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ account.name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ account.product?.name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ account.type ?? '-' }}</td>
                                    <td class="px-4 py-3 text-right text-sm">
                                        <button class="mr-3 text-indigo-600 hover:underline" @click="openEdit(account)">Edit</button>
                                        <button class="text-red-600 hover:underline" @click="destroy(account)">Delete</button>
                                    </td>
                                </tr>
                                <tr v-if="coa.data.length === 0">
                                    <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-400">No accounts yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <Pagination :paginator="coa" />
                </Card>
            </div>
        </div>

        <Modal :show="showModal" max-width="md" @close="showModal = false">
            <form class="p-6" @submit.prevent="submit">
                <h2 class="text-lg font-medium text-gray-900">
                    {{ editing ? 'Edit Account' : 'Add Account' }}
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

                <div class="mt-4">
                    <InputLabel for="product_id" value="Product (optional)" />
                    <select
                        id="product_id"
                        v-model="form.product_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">None</option>
                        <option v-for="product in products" :key="product.id" :value="product.id">{{ product.name }}</option>
                    </select>
                    <InputError :message="form.errors.product_id" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="type" value="Type (optional)" />
                    <TextInput id="type" v-model="form.type" class="mt-1 block w-full" />
                    <InputError :message="form.errors.type" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="showModal = false">Cancel</SecondaryButton>
                    <PrimaryButton :disabled="form.processing">Save</PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
