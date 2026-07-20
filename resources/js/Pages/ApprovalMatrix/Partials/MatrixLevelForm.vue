<script setup lang="ts">
import SecondaryButton from '@/Components/SecondaryButton.vue';
import SelectInput from '@/Components/SelectInput.vue';
import { useLineItems } from '@/Composables/useLineItems';
import type { UserOption } from '@/types/models';
import { computed } from 'vue';

export interface DraftLevel {
    level: number;
    approver_id: number | '';
    is_required: boolean;
}

const props = defineProps<{
    levels: DraftLevel[];
    users: UserOption[];
    errors?: Record<string, string>;
}>();

const emit = defineEmits<{ 'update:levels': [DraftLevel[]] }>();

const levels = computed({
    get: () => props.levels,
    set: (value) => emit('update:levels', value),
});

const userOptions = computed(() => props.users.map((user) => ({ id: user.id, text: user.name })));

const { add, remove } = useLineItems<DraftLevel>(
    levels,
    () => ({ level: levels.value.length + 1, approver_id: '', is_required: true }),
    () => 0,
);
</script>

<template>
    <div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead>
                    <tr>
                        <th class="w-20 px-2 py-2 text-left text-xs font-medium uppercase text-gray-500">Level</th>
                        <th class="px-2 py-2 text-left text-xs font-medium uppercase text-gray-500">Approver</th>
                        <th class="w-24 px-2 py-2 text-left text-xs font-medium uppercase text-gray-500">Required</th>
                        <th class="w-10" />
                    </tr>
                </thead>
                <tbody v-auto-animate class="divide-y divide-gray-100">
                    <tr v-for="(level, index) in levels" :key="index">
                        <td class="px-2 py-2">
                            <input
                                v-model.number="level.level"
                                type="number"
                                min="1"
                                class="block w-full rounded-md border-gray-300 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            />
                        </td>
                        <td class="px-2 py-2">
                            <SelectInput
                                v-model="level.approver_id"
                                :options="userOptions"
                                placeholder="Select approver"
                                size="sm"
                                class="block w-full"
                            />
                        </td>
                        <td class="px-2 py-2 text-center">
                            <input v-model="level.is_required" type="checkbox" class="rounded border-gray-300 text-blue-600" />
                        </td>
                        <td class="px-2 py-2 text-right">
                            <button type="button" class="text-red-600 hover:underline" @click="remove(index)">&times;</button>
                        </td>
                    </tr>
                    <tr v-if="levels.length === 0">
                        <td colspan="4" class="px-2 py-4 text-center text-sm text-gray-400">No levels yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <p v-if="errors?.levels" class="mt-2 text-sm text-red-600">{{ errors.levels }}</p>

        <SecondaryButton type="button" class="mt-3" @click="add">Add Level</SecondaryButton>
    </div>
</template>
