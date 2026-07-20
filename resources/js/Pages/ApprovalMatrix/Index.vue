<script setup lang="ts">
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SelectInput from '@/Components/SelectInput.vue';
import TextInput from '@/Components/TextInput.vue';
import Card from '@/Components/ui/Card.vue';
import IconButton from '@/Components/ui/IconButton.vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import MatrixLevelForm, { type DraftLevel } from '@/Pages/ApprovalMatrix/Partials/MatrixLevelForm.vue';
import type { ApprovalMatrix, PurchaseType, UserOption } from '@/types/models';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Pencil, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';

const props = defineProps<{
    approvalMatrices: ApprovalMatrix[];
    purchaseTypes: PurchaseType[];
    users: UserOption[];
    modelTypes: { value: string; label: string }[];
}>();

const showModal = ref(false);
const editing = ref<ApprovalMatrix | null>(null);

const form = useForm<{
    name: string;
    purchase_type_id: number | '';
    model_type: string;
    is_active: boolean;
    levels: DraftLevel[];
}>({
    name: '',
    purchase_type_id: '',
    model_type: props.modelTypes[0]?.value ?? '',
    is_active: true,
    levels: [{ level: 1, approver_id: '', is_required: true }],
});

const purchaseTypeOptions = computed(() => props.purchaseTypes.map((type) => ({ id: type.id, text: type.name })));

const levelsRef = computed({
    get: () => form.levels,
    set: (value) => (form.levels = value),
});

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const openEdit = (matrix: ApprovalMatrix) => {
    editing.value = matrix;
    form.name = matrix.name;
    form.purchase_type_id = matrix.purchase_type_id ?? '';
    form.model_type = matrix.model_type;
    form.is_active = matrix.is_active;
    form.levels = (matrix.levels ?? []).map((level) => ({
        level: level.level,
        approver_id: level.approver_id,
        is_required: level.is_required,
    }));
    form.clearErrors();
    showModal.value = true;
};

const submit = () => {
    if (editing.value) {
        form.put(route('approval-matrix.update', editing.value.id), {
            onSuccess: () => (showModal.value = false),
        });
    } else {
        form.post(route('approval-matrix.store'), {
            onSuccess: () => (showModal.value = false),
        });
    }
};

const destroy = (matrix: ApprovalMatrix) => {
    if (confirm(`Delete approval matrix"${matrix.name}"?`)) {
        router.delete(route('approval-matrix.destroy', matrix.id));
    }
};
</script>

<template>
    <Head title="Approval Matrix" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Approval Matrix</h2>
                <PrimaryButton @click="openCreate">Add Matrix</PrimaryButton>
            </div>
        </template>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3" v-auto-animate>
            <Card v-for="matrix in approvalMatrices" :key="matrix.id" :padded="false">
                <div class="flex h-full flex-col">
                    <div class="flex items-start justify-between gap-2 p-5">
                        <div class="min-w-0">
                            <h3 class="truncate text-base font-semibold text-gray-800">{{ matrix.name }}</h3>
                            <p class="mt-0.5 text-sm text-gray-500">
                                {{ matrix.purchase_type?.name ?? 'All purchase types' }}
                            </p>
                        </div>
                        <span
                            class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium"
                            :class="matrix.is_active ? 'bg-green-50 text-green-600' : 'bg-red-50 text-red-600'"
                        >
                            {{ matrix.is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>

                    <ol class="flex items-center gap-4 flex-1 border-t border-gray-100 px-5 py-4">
                        <li v-for="level in matrix.levels" :key="level.id" class="flex items-center gap-2">
                            <span
                                class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-50 text-xs font-semibold text-blue-600"
                            >
                                {{ level.level }}
                            </span>
                            <p class="text-sm text-gray-700">
                                {{ level.approver?.name }}
                                <span v-if="!level.is_required" class="text-gray-400">(optional)</span>
                            </p>
                        </li>
                        <li v-if="matrix.levels?.length === 0" class="text-sm text-gray-400">No levels configured.</li>
                    </ol>

                    <div class="flex justify-end gap-1 border-t border-gray-100 p-2">
                        <IconButton title="Edit" @click="openEdit(matrix)">
                            <Pencil class="h-4 w-4" />
                        </IconButton>
                        <IconButton title="Delete" variant="delete" @click="destroy(matrix)">
                            <Trash2 class="h-4 w-4" />
                        </IconButton>
                    </div>
                </div>
            </Card>

            <p v-if="approvalMatrices.length === 0" class="col-span-full text-center text-sm text-gray-400">No approval matrices configured yet.</p>
        </div>

        <Modal :show="showModal" max-width="lg" @close="showModal = false">
            <form class="p-6" @submit.prevent="submit">
                <h2 class="text-lg font-medium text-gray-900">
                    {{ editing ? 'Edit Approval Matrix' : 'Add Approval Matrix' }}
                </h2>

                <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel for="name" value="Name" />
                        <TextInput id="name" v-model="form.name" class="mt-1 block w-full" />
                        <InputError :message="form.errors.name" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="purchase_type_id" value="Purchase Type (optional)" />
                        <SelectInput
                            id="purchase_type_id"
                            v-model="form.purchase_type_id"
                            :options="purchaseTypeOptions"
                            placeholder="All purchase types"
                            allow-clear
                            class="mt-1 block w-full"
                        />
                        <InputError :message="form.errors.purchase_type_id" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-2 sm:col-span-2">
                        <Checkbox id="is_active" v-model:checked="form.is_active" />
                        <InputLabel for="is_active" value="Active" />
                    </div>
                </div>

                <div class="mt-6">
                    <InputLabel value="Approval Levels" class="mb-2" />
                    <MatrixLevelForm v-model:levels="levelsRef" :users="users" :errors="form.errors" />
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <SecondaryButton @click="showModal = false">Cancel</SecondaryButton>
                    <PrimaryButton :disabled="form.processing">Save</PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
