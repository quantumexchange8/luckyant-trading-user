<script setup>
import { ref } from 'vue'
import { useForm } from '@inertiajs/vue3'
import Label from '@/Components/Label.vue'
import Input from '@/Components/Input.vue'
import InputError from '@/Components/InputError.vue'
import Button from '@/Components/Button.vue'
import { CheckIcon } from '@heroicons/vue/outline'
import VOtpInput from "vue3-otp-input";

const passwordInput = ref(null)
const currentPasswordInput = ref(null)
const otpRequested = ref(false);
const countdown = ref(60);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
    otp: '',
})

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation')
                passwordInput.value.focus()
            }
            if (form.errors.current_password) {
                form.reset('current_password')
                currentPasswordInput.value.focus()
            }
        },
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

const inputClasses = ['rounded-lg w-full py-2.5 mt-1 border border-gray-300 dark:border-gray-800 focus:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500']

let countdownIntervalId;

const requestOTP = () => {
    otpRequested.value = true;
    event.preventDefault(); // Prevent default form submission behavior
    form.post(route('profile.sendOtp'))
    startCountdown();
}

const resendOTP = () => {
    countdown.value = 60;
    clearInterval(countdownIntervalId);
    event.preventDefault(); // Prevent default form submission behavior
    form.post(route('profile.sendOtp'))
    startCountdown();
}

const startCountdown = () => {
    countdownIntervalId = setInterval(() => {
        countdown.value -= 1;
        if (countdown.value === 0) {
            clearInterval(countdownIntervalId);
        }
    }, 1000);
}

</script>

<template>
    <div class="w-full">
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ $t('public.update_password') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ $t('public.update_password_message') }}
            </p>
        </header>
    </div>

    <form>
        <div class="w-full flex flex-col gap-5">
            <div class="space-y-6">
                <div>
                    <Label for="current_password" :value="$t('public.current_password')" />

                    <Input
                        id="current_password"
                        ref="currentPasswordInput"
                        v-model="form.current_password"
                        type="password"
                        class="mt-1 block w-full"
                        autocomplete="current-password"
                        :invalid="form.errors.current_password"
                    />

                    <InputError
                        :message="form.errors.current_password"
                        class="mt-2"
                    />
                </div>

                <div>
                    <Label for="password" :value="$t('public.new_password')" />

                    <Input
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-full"
                        autocomplete="new-password"
                        :invalid="form.errors.password"
                    />

                    <InputError :message="form.errors.password" class="mt-2" />
                </div>

                <div>
                    <Label for="password_confirmation" :value="$t('public.confirm_password')" />

                    <Input
                        id="password_confirmation"
                        v-model="form.password_confirmation"
                        type="password"
                        class="mt-1 block w-full"
                        autocomplete="new-password"
                        :invalid="form.errors.password_confirmation"
                    />

                    <InputError
                        :message="form.errors.password_confirmation"
                        class="mt-2"
                    />
                </div>

                <div>
                    <Label for="otp" :value="$t('public.otp_verification')" />
                    <div class="flex justify-start items-center gap-4 mt-1">
                        <Button size="sm" v-if="!otpRequested" :disabled="form.processing" @click.prevent="requestOTP">{{ $t('public.request_otp') }}</Button>
                    </div>

                    <VOtpInput v-if="otpRequested"
                            :input-classes="inputClasses"
                            class="flex gap-2"
                            separator=""
                            inputType="password"
                            :num-inputs="6"
                            v-model:value="form.otp"
                            :should-auto-focus="false"
                            :should-focus-order="true"
                    />
                    <InputError :message="form.errors.otp" class="mt-2" />
                    <div class="flex justify-end items-center gap-4 my-1 text-sm" v-if="otpRequested && countdown > 0">
                        {{ $t('public.remaining_time') }} {{ countdown }}s
                    </div>
                    <div class="flex justify-end items-center gap-4 my-1" v-if="otpRequested && countdown <= 0">
                        <span class="text-sm text-primary-500 dark:text-primary-600 underline cursor-pointer" @click="resendOTP()">
                            {{ $t('public.resend_otp_request') }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="flex flex-col items-start gap-3 self-stretch">
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
        </div>

        <div class="w-full mt-8">
            <div class="flex justify-end items-center gap-4">
                <Button :disabled="form.processing" @click.prevent="updatePassword">{{ $t('public.save') }}</Button>
            </div>
        </div>
    </form>
</template>
