<script setup lang="ts">
import $ from 'jquery';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useSelect2 } from '@/Composables/useSelect2';

export interface SelectOption {
    id: string | number;
    text: string;
}

const props = withDefaults(
    defineProps<{
        options: SelectOption[];
        placeholder?: string;
        disabled?: boolean;
        id?: string;
        name?: string;
        size?: 'default' | 'sm';
        allowClear?: boolean;
    }>(),
    {
        placeholder: '',
        disabled: false,
        id: '',
        name: '',
        size: 'default',
        allowClear: false,
    },
);

const emit = defineEmits<{
    change: [option: SelectOption | null];
}>();

const model = defineModel<string | number>({ default: '' });

const selectRef = ref<HTMLSelectElement | null>(null);
// select2's jQuery typings aren't installed; treat the plugin-augmented jQuery object as `any`.
let $select: any = null;

const findOption = (id: string) => props.options.find((option) => String(option.id) === id) ?? null;

const buildSettings = () => {
    // Native <dialog> (used by Modal.vue) renders in the browser's top layer, above regular
    // z-index stacking — select2's dropdown (appended to <body> by default) would otherwise
    // render behind an open dialog. Re-parent it into the dialog instead.
    const openDialog = document.querySelector('dialog[open]');

    return {
        width: '100%',
        placeholder: props.placeholder,
        allowClear: props.allowClear,
        data: props.options.map((option) => ({ id: String(option.id), text: option.text })),
        ...(openDialog ? { dropdownParent: $(openDialog) } : {}),
    };
};

const applyValue = () => {
    if (!$select) return;
    const value = model.value === null || model.value === undefined ? '' : String(model.value);
    $select.val(value).trigger('change');
};

onMounted(async () => {
    await useSelect2();

    if (!selectRef.value) return;

    $select = $(selectRef.value);
    $select.select2(buildSettings());
    applyValue();

    $select.on('select2:select select2:unselect', () => {
        const value = $select?.val();
        const raw = Array.isArray(value) ? value[0] : value;

        if (!raw) {
            model.value = '';
            emit('change', null);
            return;
        }

        const matched = findOption(String(raw));
        model.value = matched ? matched.id : raw;
        emit('change', matched);
    });
});

onBeforeUnmount(() => {
    $select?.select2('destroy');
    $select = null;
});

watch(
    () => props.options,
    () => {
        if (!$select) return;
        $select.empty();
        $select.select2(buildSettings());
        applyValue();
    },
    { deep: true },
);

watch(
    () => props.disabled,
    (value) => {
        $select?.prop('disabled', value);
    },
);

watch(model, applyValue);
</script>

<template>
    <div class="app-select2" :class="{ 'app-select2--sm': size === 'sm' }">
        <select :id="id" ref="selectRef" :name="name" :disabled="disabled"></select>
    </div>
</template>
