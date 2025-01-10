<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { MailIcon, LockClosedIcon, LoginIcon } from '@heroicons/vue/outline'
import InputError from '@/Components/InputError.vue'
import Button from '@/Components/Button.vue'
import Checkbox from '@/Components/Checkbox.vue'
import GuestLayout from '@/Layouts/Guest.vue'
import InputText from 'primevue/inputtext'
import Label from '@/Components/Label.vue'
import ValidationErrors from '@/Components/ValidationErrors.vue'
import {onMounted, ref} from "vue";
import IconField from 'primevue/iconfield';
import InputIcon from 'primevue/inputicon';
import Password from 'primevue/password';

defineProps({
    canResetPassword: Boolean,
    status: String,
})

const form = useForm({
    email: '',
    password: '',
    remember: false
})

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    })
}

const currentDomain = window.location.hostname;
const canRegister = ref(true);

onMounted(() => {
    if (currentDomain === 'member.luckyantmallvn.com') {
        canRegister.value = false
    }
})
</script>

<template>
    <GuestLayout :title="$t('public.log_in')">
        <form @submit.prevent="submit">
            <div class="grid gap-6">
                <div class="space-y-2">
                    <Label for="email" :value="$t('public.email')" />
                    <InputText
                        id="email"
                        type="email"
                        class="block w-full"
                        v-model="form.email"
                        autofocus
                        :placeholder="$t('public.email')"
                        :invalid="!!form.errors.email"
                        autocomplete="username"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="space-y-2">
                    <Label for="password" :value="$t('public.password')" />
                    <IconField>
                        <InputIcon>
                            <LockClosedIcon aria-hidden="true" class="w-5 h-5 text-gray-400" />
                        </InputIcon>
                        <Password
                            v-model="form.password"
                            toggleMask
                            placeholder="••••••••"
                            :invalid="!!form.errors.password"
                            :feedback="false"
                        />
                    </IconField>
                    <InputError :message="form.errors.password" />
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center">
                        <Checkbox name="remember" v-model:checked="form.remember" />
                        <span class="ml-2 text-sm text-gray-600">{{ $t('public.remember') }}</span>
                    </label>

                    <Link v-if="canResetPassword" :href="route('password.request')" class="text-sm text-primary-600 hover:underline">
                        {{ $t('public.forgot_your_password') }}
                    </Link>
                </div>

                <div>
                    <Button class="justify-center gap-2 w-full" :disabled="form.processing" v-slot="{iconSizeClasses}">
                        <LoginIcon aria-hidden="true" :class="iconSizeClasses" />
                        <span>{{ $t('public.log_in') }}</span>
                    </Button>
                </div>

                <p v-if="canRegister" class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $t('public.not_have_account') }}
                    <Link :href="route('register')" class="text-primary-600 hover:underline">
                        {{ $t('public.register') }}
                    </Link>
                </p>
            </div>
        </form>
    </GuestLayout>
</template>
