<script setup lang="ts">
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import { icons } from '@/Constants/icons';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

import logo from '@/Assets/images/logo.png'

const page = usePage();
const userName = computed(() => (page.props.auth as { user: { name: string } }).user.name);
const userInitial = computed(() => userName.value.charAt(0).toUpperCase());
</script>

<template>
    <div class="flex h-16 items-center gap-4 px-4 sm:px-6">
        <Link :href="route('dashboard')" class="flex shrink-0 items-center gap-2">
            <!-- <div
                class="flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-blue-500 to-purple-600 text-sm font-bold text-white"
            >
                ERP
            </div> -->
            <div class="flex p-[1px] h-9 w-9 items-center justify-center rounded-[13px] bg-gradient-to-br from-blue-500 to-purple-600 shadow-lg shadow-blue-500/20 overflow-hidden">
                <img :src="logo" alt="" class="w-full h-full rounded-xl">
            </div>
            <span class="hidden text-lg font-semibold text-gray-800 sm:block">Semesta Core System</span>
        </Link>

        <div class="mx-auto hidden max-w-md flex-1 md:block">
            <div class="relative">
                <svg
                    class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-gray-400"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="icons.search" />
                </svg>
                <input
                    type="text"
                    placeholder="Search Dashboard..."
                    class="w-full rounded-xl border-0 bg-gray-100 py-2 pl-10 pr-4 text-sm text-gray-700 placeholder-gray-400 focus:bg-white focus:ring-2 focus:ring-blue-500"
                />
            </div>
        </div>

        <div class="ml-auto flex items-center gap-3">
            <Dropdown align="right" width="48">
                <template #trigger>
                    <button type="button" class="flex items-center gap-3 rounded-xl px-2 py-1.5 transition-colors hover:bg-gray-50">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-blue-100 text-sm font-semibold text-blue-700">
                            {{ userInitial }}
                        </div>
                        <div class="hidden text-left sm:block">
                            <p class="text-sm text-gray-500">Welcome back,</p>
                            <p class="text-sm font-semibold text-gray-800">{{ userName }}</p>
                        </div>
                        <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="icons.chevronDown" />
                        </svg>
                    </button>
                </template>

                <template #content>
                    <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                    <DropdownLink :href="route('logout')" method="post" as="button">Log Out</DropdownLink>
                </template>
            </Dropdown>
        </div>
    </div>
</template>
