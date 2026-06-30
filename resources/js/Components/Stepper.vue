<script setup>
defineProps({
    current: { type: Number, required: true },
});

const steps = ['Identification', 'Vérification', 'Mot de passe', 'Terminé'];
</script>

<template>
    <ol class="mb-8 flex items-center">
        <li v-for="(label, index) in steps" :key="label" class="flex flex-1 items-center last:flex-none">
            <div class="flex flex-col items-center gap-1">
                <span
                    class="flex h-7 w-7 items-center justify-center rounded-full text-xs font-semibold transition"
                    :class="
                        index + 1 < current
                            ? 'bg-indigo-600 text-white'
                            : index + 1 === current
                              ? 'border-2 border-indigo-600 text-indigo-600'
                              : 'border-2 border-gray-200 text-gray-400'
                    "
                >
                    <svg v-if="index + 1 < current" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                        <path
                            fill-rule="evenodd"
                            d="M16.704 5.29a1 1 0 010 1.42l-7.5 7.5a1 1 0 01-1.42 0l-3.5-3.5a1 1 0 111.42-1.42L8.5 12.08l6.79-6.79a1 1 0 011.42 0z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    <span v-else>{{ index + 1 }}</span>
                </span>
                <span
                    class="hidden text-[11px] font-medium text-gray-500 sm:block"
                    :class="{ 'text-indigo-600': index + 1 === current }"
                >
                    {{ label }}
                </span>
            </div>
            <div
                v-if="index < steps.length - 1"
                class="mx-2 h-0.5 flex-1"
                :class="index + 1 < current ? 'bg-indigo-600' : 'bg-gray-200'"
            />
        </li>
    </ol>
</template>
