<script setup lang="ts">
import { useSplashScreen } from '@/Composables/useSplashScreen';
import { computed, onMounted } from 'vue';

const { isBooting, isNavigating, finishBooting } = useSplashScreen();

const visible = computed(() => isBooting.value || isNavigating.value);

onMounted(() => finishBooting());
</script>

<template>
    <Transition name="splash-fade">
        <div v-if="visible" class="fixed inset-0 z-[100] flex items-center justify-center bg-white/90 backdrop-blur-sm">
            <div class="flex flex-col items-center gap-4">
                <div class="relative flex h-16 w-16 items-center justify-center">
                    <span class="absolute inline-flex h-full w-full animate-ping rounded-2xl bg-indigo-400 opacity-40" />
                    <div
                        class="relative flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-500 to-purple-600 text-lg font-bold text-white shadow-lg"
                    >
                        ERP
                    </div>
                </div>
                <p class="animate-pulse text-sm font-medium text-gray-500">Loading...</p>
            </div>
        </div>
    </Transition>
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
