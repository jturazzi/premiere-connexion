<script setup>
import FirstLoginLayout from '@/Components/FirstLoginLayout.vue';
</script>

<template>
    <FirstLoginLayout title="Mot de passe défini" :step="4">
        <div class="flex flex-col items-center text-center">
            <span class="mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-green-100 text-green-600">
                <svg class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor">
                    <path
                        fill-rule="evenodd"
                        d="M16.704 5.29a1 1 0 010 1.42l-7.5 7.5a1 1 0 01-1.42 0l-3.5-3.5a1 1 0 111.42-1.42L8.5 12.08l6.79-6.79a1 1 0 011.42 0z"
                        clip-rule="evenodd"
                    />
                </svg>
            </span>
            <p class="text-sm text-gray-600">
                Votre mot de passe a été défini avec succès. Vous pouvez désormais vous connecter avec vos
                nouveaux identifiants.
            </p>
        </div>
    </FirstLoginLayout>
</template>
