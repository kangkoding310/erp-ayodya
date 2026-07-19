<script setup lang="ts">
import 'filepond/dist/filepond.min.css';
import FilePondPluginFileValidateSize from 'filepond-plugin-file-validate-size';
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import vueFilePond from 'vue-filepond';
import type { FilePondFile } from 'filepond';

const FilePond = vueFilePond(FilePondPluginFileValidateType, FilePondPluginFileValidateSize) as any;

const props = withDefaults(
    defineProps<{
        modelValue: File | null;
        acceptedFileTypes?: string[];
        maxFileSize?: string;
    }>(),
    {
        acceptedFileTypes: () => ['image/*', 'application/pdf'],
        maxFileSize: '5MB',
    },
);

const emit = defineEmits<{
    'update:modelValue': [file: File | null];
}>();

const onUpdateFiles = (files: FilePondFile[]) => {
    emit('update:modelValue', (files[0]?.file as File) ?? null);
};
</script>

<template>
    <FilePond
        allow-multiple="false"
        credits="false"
        :accepted-file-types="props.acceptedFileTypes"
        :max-file-size="props.maxFileSize"
        label-idle="Drop file or <span class='filepond--label-action'>browse</span>"
        @updatefiles="onUpdateFiles"
    />
</template>
