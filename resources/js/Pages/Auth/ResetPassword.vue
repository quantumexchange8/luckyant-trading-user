<script setup>
import { useForm } from '@inertiajs/vue3'
import { MailIcon, LockClosedIcon, CheckIcon } from '@heroicons/vue/outline'
import InputIconWrapper from '@/Components/InputIconWrapper.vue'
import Button from '@/Components/Button.vue'
import GuestLayout from '@/Layouts/Guest.vue'
import Input from '@/Components/Input.vue'
import Label from '@/Components/Label.vue'
import ValidationErrors from '@/Components/ValidationErrors.vue'

const props = defineProps({
    email: String,
    token: String,
})

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
})

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    })
}

const passwordRules = [
    { message: 'register_terms_1', regex: /.{6,}/ },
    { message: 'register_terms_2', regex: /[A-Z]+/ },
    { message: 'register_terms_3', regex: /[a-z]+/ },
    { message: 'register_terms_4', regex: /[0-9]+/ },
    { message: 'register_terms_5', regex: /[!@#$%^&*()_+{}\[\]:;<>,.?~\\-]+/ }
];

const passwordValidation = () => {
    let valid = false;
    let messages = [];

    for (let condition of passwordRules) {
        const isConditionValid = condition.regex.test(form.password);

        if (isConditionValid) {
            valid = true;
        }

        messages.push({
            message: condition.message,
            valid: isConditionValid,
        });
    }

    // Check if the new password matches the confirm password
    const isMatch = form.password === form.password_confirmation;

    messages.push({
        message: 'register_terms_6',
        valid: isMatch && form.password !== '',
    });

    // Set valid to false if there's any condition that failed
    valid = valid && isMatch;

    return { valid, messages };
};

</script>

<template>
    <GuestLayout :title="$t('public.reset_password')">
        <ValidationErrors class="mb-4" />

        <form @submit.prevent="submit">
            <div class="grid gap-4">
                <div class="space-y-2">
                    <Label for="email" :value="$t('public.email')" />
                    <InputIconWrapper>
                        <template #icon>
                            <MailIcon aria-hidden="true" class="w-5 h-5" />
                        </template>
                        <Input withIcon id="email" type="email" :placeholder="$t('public.email')" class="block w-full" v-model="form.email" required autofocus autocomplete="username" />
                    </InputIconWrapper>
                </div>

                <div class="space-y-2">
                    <Label for="password" :value="$t('public.password')" />
                    <InputIconWrapper>
                        <template #icon>
                            <LockClosedIcon aria-hidden="true" class="w-5 h-5" />
                        </template>
                        <Input withIcon id="password" type="password" :placeholder="$t('public.password')" class="block w-full" v-model="form.password" required autocomplete="new-password" />
                    </InputIconWrapper>
                </div>

                <div class="space-y-2">
                    <Label for="password_confirmation" :value="$t('public.confirm_password')" />
                    <InputIconWrapper>
                        <template #icon>
                            <LockClosedIcon aria-hidden="true" class="w-5 h-5" />
                        </template>
                        <Input withIcon id="password_confirmation" type="password" :placeholder="$t('public.confirm_password')" class="block w-full" v-model="form.password_confirmation" required autocomplete="new-password" />
                    </InputIconWrapper>
                </div>

                <div>
                    <Button class="w-full justify-center" :disabled="form.processing">
                        {{ $t('public.reset_password') }}
                    </Button>
                </div>
            </div>
            <div class="flex flex-col mt-5 items-start gap-3 self-stretch">
                <div v-for="message in passwordValidation().messages" :key="message.key" class="flex items-center gap-2 self-stretch">
                    <div
                        :class="{
                                'bg-success-500': message.valid,
                                'bg-gray-400 dark:bg-dark-eval-3': !message.valid
                            }"
                        class="flex justify-center items-center w-5 h-5 rounded-full grow-0 shrink-0"
                    >
                        <CheckIcon aria-hidden="true" class="text-white" />
                    </div>
                    <div
                        class="text-sm"
                        :class="{
                                'text-gray-600 dark:text-gray-300': message.valid,
                                'text-gray-400 dark:text-gray-500': !message.valid
                            }"
                    >
                        {{ $t('public.' + message.message) }}
                    </div>
                </div>
            </div>
        </form>
    </GuestLayout>
</template>
