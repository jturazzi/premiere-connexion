<script setup>
import { Head } from '@inertiajs/vue3';
import Stepper from '@/Components/Stepper.vue';

defineProps({
    title: { type: String, required: true },
    step: { type: Number, default: null },
});
</script>

<template>
    <Head :title="title" />

    <div class="flex min-h-screen flex-col items-center justify-center bg-gray-50 px-4 py-12">
        <div class="mb-8 flex items-center gap-2 text-gray-900">
            <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-white">
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="5" y="11" width="14" height="9" rx="2" stroke-linecap="round" stroke-linejoin="round" />
                    <path d="M8 11V7a4 4 0 118 0v4" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </span>
            <span class="text-base font-semibold">Première connexion</span>
        </div>

        <div class="w-full max-w-md rounded-2xl border border-gray-100 bg-white p-8 shadow-lg shadow-gray-200/50">
            <Stepper v-if="step" :current="step" />
            <h1 class="mb-6 text-xl font-semibold text-gray-900">{{ title }}</h1>
            <slot />
        </div>
    </div>
</template>
