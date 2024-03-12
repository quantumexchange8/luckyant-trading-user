<script setup>
import { useForm } from '@inertiajs/vue3'
import { MailIcon, LockClosedIcon } from '@heroicons/vue/outline'
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
        </form>
    </GuestLayout>
</template>
