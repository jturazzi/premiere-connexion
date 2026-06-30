<script setup>
import { useForm } from '@inertiajs/vue3';
import FirstLoginLayout from '@/Components/FirstLoginLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const form = useForm({
    samaccountname: '',
});

function submit() {
    form.post(route('first-login.identify.submit'));
}
</script>

<template>
    <FirstLoginLayout title="Première connexion" :step="1">
        <p class="mb-6 text-sm text-gray-500">
            Saisissez votre identifiant pour commencer la définition de votre mot de passe.
        </p>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel for="samaccountname">Identifiant</InputLabel>
                <TextInput
                    id="samaccountname"
                    v-model="form.samaccountname"
                    type="text"
                    autocomplete="username"
                    autofocus
                    placeholder="jean.dupont"
                    class="mt-1.5"
                />
                <InputError :message="form.errors.samaccountname" />
            </div>

            <PrimaryButton :disabled="form.processing" :loading="form.processing">
                Continuer
            </PrimaryButton>
        </form>
    </FirstLoginLayout>
</template>
