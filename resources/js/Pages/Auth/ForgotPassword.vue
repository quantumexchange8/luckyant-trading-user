<script setup>
import { useForm } from '@inertiajs/vue3'
import { MailIcon, PaperAirplaneIcon } from '@heroicons/vue/outline'
import InputIconWrapper from '@/Components/InputIconWrapper.vue'
import Button from '@/Components/Button.vue'
import GuestLayout from '@/Layouts/Guest.vue'
import Input from '@/Components/Input.vue'
import Label from '@/Components/Label.vue'
import ValidationErrors from '@/Components/ValidationErrors.vue'

defineProps({
    status: String
})

const form = useForm({
    email: ''
})

const submit = () => {
    form.post(route('password.email'))
}
</script>

<template>
    <GuestLayout :title="$t('public.forgot_password')">
        <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
            {{ $t('public.forgot_your_password_message') }}
        </div>

        <div v-if="status" class="mb-4 font-medium text-sm text-green-600">
            {{ status }}
        </div>

        <ValidationErrors class="mb-4" />

        <form @submit.prevent="submit">
            <div class="grid gap-6">
                <div class="space-y-2">
                    <Label for="email" :value="$t('public.email')" />
                    <InputIconWrapper>
                        <template #icon>
                            <MailIcon aria-hidden="true" class="w-5 h-5" />
                        </template>
                        <Input withIcon id="email" type="email" class="block w-full" :placeholder="$t('public.email')" v-model="form.email" required autofocus autocomplete="username" />
                    </InputIconWrapper>
                </div>

                <div>
                    <Button class="justify-center gap-2 w-full" :disabled="form.processing" v-slot="{ iconSizeClasses }">
                        <PaperAirplaneIcon aria-hidden="true" :class="iconSizeClasses" />
                        <span>{{ $t('public.email_password_reset_link') }}</span>
                    </Button>
                </div>
            </div>
        </form>
    </GuestLayout>
</template>
