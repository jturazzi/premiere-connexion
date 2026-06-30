<script setup>
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';
import FirstLoginLayout from '@/Components/FirstLoginLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const form = useForm({
    password: '',
    password_confirmation: '',
});

const checks = computed(() => ({
    length: form.password.length >= 12,
    uppercase: /\p{Lu}/u.test(form.password),
    lowercase: /\p{Ll}/u.test(form.password),
    number: /\p{N}/u.test(form.password),
    // aligné sur la règle backend Password::symbols() (\p{Z}|\p{S}|\p{P})
    symbol: /[\p{Z}\p{S}\p{P}]/u.test(form.password),
    match: form.password.length > 0 && form.password === form.password_confirmation,
}));

const rules = [
    { key: 'length', label: 'Au moins 12 caractères' },
    { key: 'uppercase', label: 'Une majuscule' },
    { key: 'lowercase', label: 'Une minuscule' },
    { key: 'number', label: 'Un chiffre' },
    { key: 'symbol', label: 'Un symbole' },
    { key: 'match', label: 'Les deux mots de passe sont identiques' },
];

const isPasswordValid = computed(() => Object.values(checks.value).every(Boolean));

function submit() {
    form.post(route('first-login.password.submit'));
}
</script>

<template>
    <FirstLoginLayout title="Définir votre mot de passe" :step="3">
        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel for="password">Nouveau mot de passe</InputLabel>
                <TextInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    autocomplete="new-password"
                    autofocus
                    class="mt-1.5"
                />
                <InputError :message="form.errors.password" />
            </div>

            <div>
                <InputLabel for="password_confirmation">Confirmer le mot de passe</InputLabel>
                <TextInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    class="mt-1.5"
                />
            </div>

            <ul class="grid grid-cols-1 gap-1.5 rounded-lg bg-gray-50 p-3 text-sm sm:grid-cols-2">
                <li
                    v-for="rule in rules"
                    :key="rule.key"
                    class="flex items-center gap-1.5"
                    :class="checks[rule.key] ? 'text-green-700' : 'text-gray-400'"
                >
                    <svg v-if="checks[rule.key]" class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                        <path
                            fill-rule="evenodd"
                            d="M16.704 5.29a1 1 0 010 1.42l-7.5 7.5a1 1 0 01-1.42 0l-3.5-3.5a1 1 0 111.42-1.42L8.5 12.08l6.79-6.79a1 1 0 011.42 0z"
                            clip-rule="evenodd"
                        />
                    </svg>
                    <svg v-else class="h-4 w-4 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                        <circle cx="10" cy="10" r="3" />
                    </svg>
                    {{ rule.label }}
                </li>
            </ul>

            <PrimaryButton :disabled="form.processing || !isPasswordValid" :loading="form.processing">
                Définir le mot de passe
            </PrimaryButton>
        </form>
    </FirstLoginLayout>
</template>
