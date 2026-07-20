<script setup lang="ts">
import 'filepond/dist/filepond.min.css';
import FilePondPluginFileValidateSize from 'filepond-plugin-file-validate-size';
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import vueFilePond from 'vue-filepond';
import { computed } from 'vue';
import type { FilePondFile } from 'filepond';

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
        label: 'Drop Attachment or browse',
        size: 'md',
    },
);

const emit = defineEmits<{
    'update:modelValue': [file: File | null];
}>();

const labelIdle = computed(() => props.label.replace('browse', "<span class='filepond--label-action'>browse</span>"));

const onUpdateFiles = (files: FilePondFile[]) => {
    emit('update:modelValue', (files[0]?.file as File) ?? null);
};
</script>

<template>
    <div class="fileupload" :class="`fileupload--${size}`">
        <FilePond
            allow-multiple="false"
            credits="false"
            :accepted-file-types="props.acceptedFileTypes"
            :max-file-size="props.maxFileSize"
            :label-idle="labelIdle"
            @updatefiles="onUpdateFiles"
        />
    </div>
</template>

<style scoped>
.fileupload--sm :deep(.filepond--drop-label) {
    min-height: 35px;
    font-size: 0.75rem;
}

.fileupload--md :deep(.filepond--drop-label) {
    min-height: 4rem;
    font-size: 0.875rem;
}

.fileupload--lg :deep(.filepond--drop-label) {
    min-height: 7rem;
    font-size: 1rem;
}
</style>
