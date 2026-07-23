<script setup lang="ts">
import { useSplashScreen } from '@/Composables/useSplashScreen';
import { computed, onMounted, ref, watch } from 'vue';

import logo from '@/Assets/images/logo.png'

const { isBooting, isNavigating, finishBooting } = useSplashScreen();

const visible = computed(() => isBooting.value || isNavigating.value);
const dialog = ref<HTMLDialogElement>();

// Native <dialog>.showModal() promotes the element to the browser's "top layer",
// which always paints above every other <dialog> already open (e.g. Modal.vue).
// Re-invoking showModal() on every visibility change keeps the splash on top of
// whatever modal happens to be open when navigation starts.
watch(visible, (value) => {
    if (value) {
        dialog.value?.showModal();
    } else {
        dialog.value?.close();
    }
});

onMounted(() => {
    finishBooting();

    if (visible.value) {
        dialog.value?.showModal();
    }
});
</script>

<template>
    <dialog ref="dialog" class="z-[100] m-0 min-h-full min-w-full overflow-visible bg-transparent p-0 backdrop:bg-transparent">
        <Transition name="splash-fade">
            <div v-if="visible" class="fixed inset-0 flex items-center justify-center bg-white/90 backdrop-blur-sm">
                <div class="flex flex-col items-center gap-4">
                    <div class="relative flex h-16 w-16 items-center justify-center">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-2xl bg-blue-400 opacity-40" />
                        <!-- <div
                            class="relative flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-purple-600 text-lg font-bold text-white shadow-lg"
                        >
                            SCS
                        </div> -->
                        <div class="flex p-[1px] h-16 w-16 items-center justify-center rounded-[13px] bg-gradient-to-br from-blue-500 to-purple-600 shadow-lg shadow-blue-500/20 overflow-hidden">
                            <img :src="logo" alt="" class="w-full h-full rounded-xl">
                        </div>
                    </div>
                    <p class="animate-pulse text-sm font-medium text-gray-500">Loading...</p>
                </div>
            </div>
        </Transition>
    </dialog>
</template>

<style scoped>
.splash-fade-enter-active,
.splash-fade-leave-active {
    transition: opacity 0.25s ease;
}
.splash-fade-enter-from,
.splash-fade-leave-to {
    opacity: 0;
}
</style>
