<script setup>
import { useForm } from '@inertiajs/vue3';
import FirstLoginLayout from '@/Components/FirstLoginLayout.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';

const form = useForm({
    code: '',
});

function submit() {
    form.post(route('first-login.verify.submit'));
}
</script>

<template>
    <FirstLoginLayout title="Vérification d'identité" :step="2">
        <p class="mb-6 text-sm text-gray-500">
            Saisissez le mot de passe de vérification qui vous a été communiqué.
        </p>

        <form @submit.prevent="submit" class="space-y-5">
            <div>
                <InputLabel for="code">Mot de passe de vérification</InputLabel>
                <TextInput id="code" v-model="form.code" type="text" autofocus class="mt-1.5" />
                <InputError :message="form.errors.code" />
            </div>

            <PrimaryButton :disabled="form.processing" :loading="form.processing">
                Continuer
            </PrimaryButton>
        </form>
    </FirstLoginLayout>
</template>
