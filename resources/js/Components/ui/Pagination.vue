<script setup lang="ts">
import type { Paginated } from '@/types/models';
import { Link } from '@inertiajs/vue3';

defineProps<{ paginator: Paginated<unknown> }>();
</script>

<template>
    <div v-if="paginator.last_page > 1" class="flex flex-wrap items-center justify-between gap-2 border-t border-gray-100 px-4 py-3">
        <p class="text-sm text-gray-500">{{ paginator.total }} total</p>
        <div class="flex flex-wrap gap-1">
            <template v-for="(link, index) in paginator.links" :key="index">
                <span v-if="!link.url" class="rounded-md px-3 py-1 text-sm text-gray-300" v-html="link.label" />
                <Link
                    v-else
                    :href="link.url"
                    preserve-scroll
                    class="rounded-md px-3 py-1 text-sm"
                    :class="link.active ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:bg-gray-100'"
                    v-html="link.label"
                />
            </template>
        </div>
    </div>
</template>
