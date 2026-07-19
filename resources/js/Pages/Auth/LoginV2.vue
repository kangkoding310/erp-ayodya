<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import { icons } from '@/Constants/icons';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

import bgAuth from '@/Assets/images/bg-auth.png';

defineProps<{
    canResetPassword?: boolean;
    status?: string;
}>();

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const showPassword = ref(false);

const submit = () => {
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
        },
    });
};

const eyeIcon = 'M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z';
const eyeSlashIcon = 'M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88';

const modules = [
    { icon: icons.dashboard, label: 'Dashboard' },
    { icon: icons.purchase, label: 'Purchasing' },
    { icon: icons.accounting, label: 'Accounting' },
    { icon: icons.approval, label: 'Approvals' },
];
</script>

<template>
    <Head title="Log in" />

    <div class="relative flex min-h-screen w-full items-center justify-start overflow-hidden bg-gradient-to-br from-slate-950 via-blue-950 to-blue-600 px-32 lg:justify-start">
        <img :src="bgAuth" alt="" class="absolute left-0 top-0 w-screen h-[100dvh] object-cover">

        <!-- form card -->
        <div
            class="relative z-10 w-full max-w-md shrink-0 rounded-[2rem] border border-white/10 bg-white p-8 opacity-0 shadow-2xl shadow-black/40 [animation:fade-in-up_0.5s_ease-out_forwards] sm:p-10"
        >
            <div class="mb-7 flex flex-col items-center text-center">
                <div class="mb-1 flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600 to-blue-800 text-sm font-bold text-white shadow-lg shadow-blue-600/30">
                    SCS
                </div>
                <h1 class="text-lg font-bold tracking-tight text-gray-900">Semesta Core System</h1>
            </div>

            <div class="mb-7 text-center">
                <h2 class="text-2xl font-semibold text-gray-900">Sign in to your account</h2>
                <p class="mt-1.5 text-sm text-gray-500">Welcome! Please enter your details.</p>
            </div>

            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="-translate-y-2 opacity-0"
                enter-to-class="translate-y-0 opacity-100"
            >
                <div v-if="status" class="mb-6 flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="icons.check" />
                    </svg>
                    {{ status }}
                </div>
            </Transition>

            <form class="space-y-5" @submit.prevent="submit">
                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700">Email</label>
                    <input
                        id="email"
                        v-model="form.email"
                        type="email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="admin@semesta.com"
                        class="block w-full rounded-xl border bg-gray-50 px-4 py-2.5 text-sm text-gray-900 outline-none ring-blue-500/10 transition-all duration-200 placeholder:text-gray-400 focus:border-blue-500 focus:bg-white focus:ring-0"
                        :class="form.errors.email ? 'border-red-300 focus:border-red-500 focus:ring-red-500/10' : 'border-gray-200'"
                    />
                    <InputError class="mt-1.5" :message="form.errors.email" />
                </div>

                <div>
                    <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700">Password</label>
                    <div class="relative">
                        <input
                            id="password"
                            v-model="form.password"
                            :type="showPassword ? 'text' : 'password'"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                            class="block w-full rounded-xl border bg-gray-50 py-2.5 pl-4 pr-11 text-sm text-gray-900 outline-none ring-blue-500/10 transition-all duration-200 placeholder:text-gray-400 focus:border-blue-500 focus:bg-white focus:ring-0"
                            :class="form.errors.password ? 'border-red-300 focus:border-red-500 focus:ring-red-500/10' : 'border-gray-200'"
                        />
                        <button
                            type="button"
                            tabindex="-1"
                            class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-gray-400 transition-colors hover:text-gray-600"
                            @click="showPassword = !showPassword"
                        >
                            <svg v-if="showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="eyeSlashIcon" />
                            </svg>
                            <svg v-else class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" :d="eyeIcon" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>
                    </div>
                    <InputError class="mt-1.5" :message="form.errors.password" />
                </div>

                <div class="flex items-center justify-between pt-1">
                    <label class="inline-flex cursor-pointer select-none items-center gap-2">
                        <input v-model="form.remember" type="checkbox" name="remember" class="peer sr-only" />
                        <span class="flex h-5 w-5 items-center justify-center rounded-md border border-gray-300 bg-white transition-colors duration-200 peer-checked:border-blue-600 peer-checked:bg-blue-600 peer-focus-visible:ring-2 peer-focus-visible:ring-blue-500 peer-focus-visible:ring-offset-2">
                            <svg class="h-3.5 w-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                        </span>
                        <span class="text-sm text-gray-600">Remember me</span>
                    </label>

                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-sm font-medium text-blue-600 transition-colors hover:text-blue-800"
                    >
                        Forgot Password
                    </Link>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="mt-2 flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-600/25 transition-all duration-200 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-70"
                >
                    <svg v-if="form.processing" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                    </svg>
                    <span>{{ form.processing ? 'Signing in…' : 'Sign in' }}</span>
                </button>
            </form>

            <div class="mt-8 flex flex-col items-center gap-1.5 border-t border-gray-100 pt-6 text-center">
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-blue-600 to-blue-800 text-[10px] font-bold text-white">
                    SCS
                </div>
                <p class="text-xs font-medium text-gray-400">Semesta Core System</p>
                <p class="text-[11px] text-gray-400">&copy; {{ new Date().getFullYear() }} Semesta Core System. All rights reserved.</p>
            </div>
        </div>
    </div>
</template>

<style>
@keyframes blob-float {
    0%,
    100% {
        transform: translate(0, 0) scale(1);
    }
    33% {
        transform: translate(20px, -30px) scale(1.06);
    }
    66% {
        transform: translate(-15px, 15px) scale(0.96);
    }
}

@keyframes float-y {
    0%,
    100% {
        transform: translateY(0);
    }
    50% {
        transform: translateY(-8px);
    }
}

@keyframes fade-in-up {
    from {
        opacity: 0;
        transform: translateY(16px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>
