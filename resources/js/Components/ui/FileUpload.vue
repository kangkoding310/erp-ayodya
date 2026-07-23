<script setup lang="ts">
import 'filepond/dist/filepond.min.css';
import FilePondPluginFileValidateSize from 'filepond-plugin-file-validate-size';
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import vueFilePond from 'vue-filepond';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import type { FilePondFile } from 'filepond';
import Modal from '@/Components/Modal.vue';
import { Eye, X } from '@lucide/vue';

const FilePond = vueFilePond(FilePondPluginFileValidateType, FilePondPluginFileValidateSize) as any;

const props = withDefaults(
    defineProps<{
        modelValue: File | null;
        acceptedFileTypes?: string[];
        maxFileSize?: string;
        label?: string;
        size?: 'sm' | 'md' | 'lg';
    }>(),
    {
        acceptedFileTypes: () => ['image/*', 'application/pdf'],
        maxFileSize: '5MB',
        label: 'Drop or browse',
        size: 'sm',
    },
);

const emit = defineEmits<{
    'update:modelValue': [file: File | null];
}>();

const labelIdle = computed(() => props.label.replace('browse', "<span class='filepond--label-action'>browse</span>"));

const filePondRef = ref<any>(null);

const onUpdateFiles = (files: FilePondFile[]) => {
    emit('update:modelValue', (files[0]?.file as File) ?? null);
};

const clearFile = () => {
    filePondRef.value?.removeFiles();
    emit('update:modelValue', null);
};

const previewUrl = ref<string | null>(null);
const showPreview = ref(false);

const revokePreviewUrl = () => {
    if (previewUrl.value) {
        URL.revokeObjectURL(previewUrl.value);
        previewUrl.value = null;
    }
};

watch(
    () => props.modelValue,
    (file) => {
        revokePreviewUrl();

        if (file) {
            previewUrl.value = URL.createObjectURL(file);
        }
    },
    { immediate: true },
);

onBeforeUnmount(revokePreviewUrl);

const isImage = computed(() => props.modelValue?.type.startsWith('image/') ?? false);
const isPdf = computed(() => props.modelValue?.type === 'application/pdf');
</script>

<template>
    <div class="fileupload flex items-center gap-2" :class="`fileupload--${size}`">
        <div class="min-w-0 flex-1">
            <!-- FilePond owns picking/dragging a file. Once one is chosen we render our
                 own compact chip instead of FilePond's file-item UI: that internal layout
                 (absolute-positioned buttons, a 3-slice background panel, JS-computed
                 transforms) assumes FilePond's own default row height, and fighting it
                 down to the app's compact input height kept surfacing new misalignment.
                 FilePond stays mounted (v-show, not v-if) so its internal state doesn't
                 get out of sync with modelValue. -->
            <div v-show="!modelValue">
                <FilePond
                    ref="filePondRef"
                    allow-multiple="false"
                    credits="false"
                    :accepted-file-types="props.acceptedFileTypes"
                    :max-file-size="props.maxFileSize"
                    :label-idle="labelIdle"
                    @updatefiles="onUpdateFiles"
                />
            </div>

            <div v-if="modelValue" class="flex h-[var(--fp-h,2.1875rem)] items-center gap-2 rounded-md bg-green-600 pl-1 pr-2.5 text-white">
                <button
                    type="button"
                    title="Remove file"
                    aria-label="Remove file"
                    class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-black/20 hover:bg-black/30"
                    @click="clearFile"
                >
                    <X class="h-3 w-3" />
                </button>
                <span class="min-w-0 flex-1 truncate text-xs font-medium max-w-[125px] line-clamp-1">{{ modelValue.name }}</span>
            </div>
        </div>

        <button
            v-if="modelValue"
            type="button"
            title="Preview file"
            aria-label="Preview file"
            class="inline-flex shrink-0 rounded p-1.5 text-blue-600 hover:bg-blue-50 hover:text-blue-700"
            @click="showPreview = true"
        >
            <Eye class="h-4 w-4" />
        </button>

        <Modal :show="showPreview" max-width="lg" @close="showPreview = false">
            <div class="p-4">
                <h3 class="mb-3 text-sm font-medium text-gray-700">{{ modelValue?.name }}</h3>
                <img v-if="isImage && previewUrl" :src="previewUrl" class="max-h-[70vh] w-full rounded-md object-contain" />
                <iframe v-else-if="isPdf && previewUrl" :src="previewUrl" class="h-[70vh] w-full rounded-md border border-gray-200" />
                <p v-else class="text-sm text-gray-500">No preview available for this file type.</p>
            </div>
        </Modal>
    </div>
</template>

<style scoped>
.fileupload--sm {
    --fp-h: 2.1875rem;
}

.fileupload--sm :deep(.filepond--root.filepond--root) {
    height: var(--fp-h) !important;
    margin-bottom: 0;
    font-size: 0.7rem;
}

.fileupload--sm :deep(.filepond--drop-label) {
    min-height: var(--fp-h);
    height: var(--fp-h);
}

.fileupload--sm :deep(.filepond--panel-root.filepond--panel-root) {
    height: var(--fp-h) !important;
}

.fileupload--sm :deep(.filepond--drop-label label) {
    padding: 0;
}

.fileupload--md {
    --fp-h: 4rem;
}

.fileupload--md :deep(.filepond--drop-label) {
    min-height: var(--fp-h);
    font-size: 0.875rem;
}

.fileupload--lg {
    --fp-h: 7rem;
}

.fileupload--lg :deep(.filepond--drop-label) {
    min-height: var(--fp-h);
    font-size: 1rem;
}
</style>
