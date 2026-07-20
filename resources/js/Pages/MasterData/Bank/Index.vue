<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Card from '@/Components/ui/Card.vue';
import IconButton from '@/Components/ui/IconButton.vue';
import Pagination from '@/Components/ui/Pagination.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import type { Bank, Paginated } from '@/types/models';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Pencil, Trash2 } from '@lucide/vue';
import { ref } from 'vue';

defineProps<{
    banks: Paginated<Bank>;
    filters: { search?: string };
}>();

const showModal = ref(false);
const editing = ref<Bank | null>(null);

const form = useForm({
    bank_name: '',
    account_number: '',
    account_name: '',
});

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEdit = (bank: Bank) => {
    editing.value = bank;
    form.bank_name = bank.bank_name;
    form.account_number = bank.account_number;
    form.account_name = bank.account_name;
    form.clearErrors();
    showModal.value = true;
};

const submit = () => {
    if (editing.value) {
        form.put(route('master-data.banks.update', editing.value.id), {
            onSuccess: () => (showModal.value = false),
        });
    } else {
        form.post(route('master-data.banks.store'), {
            onSuccess: () => (showModal.value = false),
        });
    }
};

const destroy = (bank: Bank) => {
    if (confirm(`Delete bank"${bank.bank_name}"?`)) {
        router.delete(route('master-data.banks.destroy', bank.id));
    }
};
</script>

<template>
    <Head title="Banks" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Banks</h2>
                <PrimaryButton @click="openCreate">Add Bank</PrimaryButton>
            </div>
        </template>

        <div class="py-1">
            <div class="mx-auto max-w-6xl ">
                <Card :padded="false">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Bank Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Account Number</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Account Name</th>
                                    <th class="px-4 py-3" />
                                </tr>
                            </thead>
                            <tbody v-auto-animate class="divide-y divide-gray-100">
                                <tr v-for="bank in banks.data" :key="bank.id">
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ bank.bank_name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ bank.account_number }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ bank.account_name }}</td>
                                    <td class="px-4 py-3 text-right text-sm">
                                        <IconButton title="Edit" class="mr-2" @click="openEdit(bank)">
                                            <Pencil class="h-4 w-4" />
                                        </IconButton>
                                        <IconButton title="Delete" variant="delete" @click="destroy(bank)">
                                            <Trash2 class="h-4 w-4" />
                                        </IconButton>
                                    </td>
                                </tr>
                                <tr v-if="banks.data.length === 0">
                                    <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-400">No banks yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <Pagination :paginator="banks" />
                </Card>
            </div>
        </div>

        <Modal :show="showModal" max-width="md" @close="showModal = false">
            <form class="p-6" @submit.prevent="submit">
                <h2 class="text-lg font-medium text-gray-900">
                    {{ editing ? 'Edit Bank' : 'Add Bank' }}
                </h2>

                <div class="mt-4">
                    <InputLabel for="bank_name" value="Bank Name" />
                    <TextInput id="bank_name" v-model="form.bank_name" class="mt-1 block w-full" />
                    <InputError :message="form.errors.bank_name" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="account_number" value="Account Number" />
                    <TextInput id="account_number" v-model="form.account_number" class="mt-1 block w-full" />
                    <InputError :message="form.errors.account_number" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="account_name" value="Account Name" />
                    <TextInput id="account_name" v-model="form.account_name" class="mt-1 block w-full" />
                    <InputError :message="form.errors.account_name" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="showModal = false">Cancel</SecondaryButton>
                    <PrimaryButton :disabled="form.processing">Save</PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
