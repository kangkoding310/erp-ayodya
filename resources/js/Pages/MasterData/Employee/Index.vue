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
import type { Division, Employee, Paginated, UserOption } from '@/types/models';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps<{
    employees: Paginated<Employee>;
    divisions: Division[];
    users: UserOption[];
    filters: { search?: string };
}>();

const showModal = ref(false);
const editing = ref<Employee | null>(null);

const form = useForm({
    name: '',
    division_id: '' as number | '',
    position: '',
    user_id: '' as number | '',
});

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEdit = (employee: Employee) => {
    editing.value = employee;
    form.name = employee.name;
    form.division_id = employee.division_id;
    form.position = employee.position ?? '';
    form.user_id = employee.user_id ?? '';
    form.clearErrors();
    showModal.value = true;
};

const submit = () => {
    if (editing.value) {
        form.put(route('master-data.employees.update', editing.value.id), {
            onSuccess: () => (showModal.value = false),
        });
    } else {
        form.post(route('master-data.employees.store'), {
            onSuccess: () => (showModal.value = false),
        });
    }
};

const destroy = (employee: Employee) => {
    if (confirm(`Delete employee"${employee.name}"?`)) {
        router.delete(route('master-data.employees.destroy', employee.id));
    }
};
</script>

<template>
    <Head title="Employees" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Employees</h2>
                <PrimaryButton @click="openCreate">Add Employee</PrimaryButton>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <Card :padded="false">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Name</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Division</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Position</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">Linked User</th>
                                    <th class="px-4 py-3" />
                                </tr>
                            </thead>
                            <tbody v-auto-animate class="divide-y divide-gray-100">
                                <tr v-for="employee in employees.data" :key="employee.id">
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ employee.name }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ employee.division?.name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ employee.position ?? '-' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ employee.user?.name ?? '-' }}</td>
                                    <td class="px-4 py-3 text-right text-sm">
                                        <button class="mr-3 text-indigo-600 hover:underline" @click="openEdit(employee)">Edit</button>
                                        <button class="text-red-600 hover:underline" @click="destroy(employee)">Delete</button>
                                    </td>
                                </tr>
                                <tr v-if="employees.data.length === 0">
                                    <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-400">No employees yet.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <Pagination :paginator="employees" />
                </Card>
            </div>
        </div>

        <Modal :show="showModal" max-width="md" @close="showModal = false">
            <form class="p-6" @submit.prevent="submit">
                <h2 class="text-lg font-medium text-gray-900">
                    {{ editing ? 'Edit Employee' : 'Add Employee' }}
                </h2>

                <div class="mt-4">
                    <InputLabel for="name" value="Name" />
                    <TextInput id="name" v-model="form.name" class="mt-1 block w-full" />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="division_id" value="Division" />
                    <select
                        id="division_id"
                        v-model="form.division_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="" disabled>Select division</option>
                        <option v-for="division in divisions" :key="division.id" :value="division.id">{{ division.name }}</option>
                    </select>
                    <InputError :message="form.errors.division_id" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="position" value="Position (optional)" />
                    <TextInput id="position" v-model="form.position" class="mt-1 block w-full" />
                    <InputError :message="form.errors.position" class="mt-2" />
                </div>

                <div class="mt-4">
                    <InputLabel for="user_id" value="Linked User (optional)" />
                    <select
                        id="user_id"
                        v-model="form.user_id"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                        <option value="">None</option>
                        <option v-for="user in users" :key="user.id" :value="user.id">{{ user.name }} ({{ user.email }})</option>
                    </select>
                    <InputError :message="form.errors.user_id" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="showModal = false">Cancel</SecondaryButton>
                    <PrimaryButton :disabled="form.processing">Save</PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
