<script setup lang="ts">
import ContextSidebar from '@/Components/layout/ContextSidebar.vue';
import ModuleNav from '@/Components/layout/ModuleNav.vue';
import TopBar from '@/Components/layout/TopBar.vue';
import Toaster from '@/Components/ui/Toaster.vue';
import { useModuleNav } from '@/Composables/useModuleNav';
import { ref } from 'vue';

const { modules, activeModule } = useModuleNav();
const sidebarOpen = ref(false);
</script>

<template>
    <Toaster />

    <div class="min-h-screen bg-white">
        <div class="mx-auto max-w-[1600px] space-y-4 p-3 sm:p-4 lg:space-y-6 lg:p-6">
            <div class="overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-sm">
                <TopBar />
                <div class="border-t border-gray-100" />
                <ModuleNav
                    :modules="modules"
                    :show-sidebar-toggle="!!activeModule && activeModule.children.length > 1"
                    @toggle-sidebar="sidebarOpen = !sidebarOpen"
                />
            </div>

            <div class="flex items-start gap-4 lg:gap-6">
                <ContextSidebar
                    v-if="activeModule && activeModule.children.length > 1"
                    :module="activeModule"
                    :open="sidebarOpen"
                    @close="sidebarOpen = false"
                />

                <main class="min-w-0 flex-1 space-y-4 lg:space-y-6">
                    <div v-if="$slots.header">
                        <slot name="header" />
                    </div>

                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
