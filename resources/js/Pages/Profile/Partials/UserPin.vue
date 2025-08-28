<script setup>
import VOtpInput from "vue3-otp-input";
import {onUnmounted, ref, watch, watchEffect} from "vue";
import Label from "@/Components/Label.vue";
import {useForm, usePage} from "@inertiajs/vue3";
import Button from "@/Components/Button.vue";
import InputError from "@/Components/InputError.vue";
import Modal from "@/Components/Modal.vue";
import ResetPin from "@/Pages/Profile/Partials/ResetPin.vue";

const emit = defineEmits(['update:setupSecurityPinModal'])
const user = usePage().props.auth.user;
const checkCurrentPin = ref(false);

const form = useForm({
    current_pin: '',
    pin: '',
    pin_confirmation: '',
})

const updatePassword = () => {
    if (user.security_pin === null) {
        form.current_pin = undefined
    }
    form.post(route('password.user_pin'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            emit('update:setupSecurityPinModal', false);
        },
    })
}

const inputClasses = ['rounded-lg w-full py-2.5 mt-1 bg-white dark:bg-gray-800 placeholder:text-gray-400 dark:placeholder:text-gray-500 text-gray-900 dark:text-gray-50 border border-gray-300 dark:border-gray-800 focus:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500']

watchEffect(() => {
    if (usePage().props.title !== null) {
        if (user.security_pin) {
            checkCurrentPin.value = true;
        }
    }
});

const resetPinModal = ref(false);
const otpRequested = ref(false);
const countdown = ref(60);
let countdownIntervalId;

const openResetPinModal = () => {
    resetPinModal.value = true
}

const startCountdown = () => {
    clearInterval(countdownIntervalId);
    countdownIntervalId = setInterval(() => {
        countdown.value -= 1;
        if (countdown.value === 0) {
            clearInterval(countdownIntervalId);
        }
    }, 1000);
};

// Watch to restart countdown if needed
watch(countdown, (newVal) => {
    if (newVal > 0 && !countdownIntervalId) {
        startCountdown();
    }
});

// Cleanup interval on unmount
onUnmounted(() => {
    clearInterval(countdownIntervalId);
});

const requestOTP = () => {
    otpRequested.value = true;
    axios.post('/profile/sendOtp')
    startCountdown();
};

const resendOTP = () => {
    countdown.value = 60;
    clearInterval(countdownIntervalId);
    axios.post('/profile/sendOtp')
    startCountdown();
};

const closeModal = () => {
    resetPinModal.value = false
}
</script>

<template>
    <div class="w-full">
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ $t('public.security_pin') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ $t('public.security_pin_caption') }}
            </p>
        </header>
    </div>

    <form>
        <div class="w-full flex flex-col gap-5">
            <div class="space-y-6">
                <div v-if="checkCurrentPin || user.security_pin">
                    <Label for="current_pin" :value="$t('public.current_pin')" />
                    <VOtpInput
                        :input-classes="inputClasses"
                        class="flex gap-2"
                        separator=""
                        inputType="password"
                        :num-inputs="6"
                        v-model:value="form.current_pin"
                        :should-auto-focus="false"
                        :should-focus-order="true"
                    />
                    <InputError
                        :message="form.errors.current_pin"
                        class="mt-2"
                    />
                </div>

                <div>
                    <Label for="pin" :value="$t('public.new_pin')" />
                    <VOtpInput
                        :input-classes="inputClasses"
                        class="flex gap-2"
                        separator=""
                        inputType="password"
                        :num-inputs="6"
                        v-model:value="form.pin"
                        :should-auto-focus="false"
                        :should-focus-order="true"
                    />
                    <InputError
                        :message="form.errors.pin"
                        class="mt-2"
                    />
                </div>

<!--                <div>-->
<!--                    <Label for="pin_confirmation" :value="$t('public.confirm_pin')" />-->
<!--                    <VOtpInput-->
<!--                        :input-classes="inputClasses"-->
<!--                        class="flex gap-2"-->
<!--                        separator=""-->
<!--                        inputType="password"-->
<!--                        :num-inputs="6"-->
<!--                        v-model:value="form.pin_confirmation"-->
<!--                        :should-auto-focus="false"-->
<!--                        :should-focus-order="true"-->
<!--                    />-->
<!--                </div>-->
            </div>
        </div>
        <div class="w-full flex justify-between mt-8">
            <div
                v-if="user.security_pin"
                class="text-primary-600 w-full dark:text-primary-400 text-sm hover:text-primary-400 dark:hover:text-primary-600 hover:cursor-pointer"
                @click="openResetPinModal"
            >
                {{ $t('public.forgot_security_pin') }}
            </div>
<!--            <div class="flex w-full justify-end items-center gap-4">-->
<!--                <Button :disabled="form.processing" @click.prevent="updatePassword">{{ $t('public.save') }}</Button>-->
<!--            </div>-->
        </div>

        <Modal :show="resetPinModal" :title="$t('public.reset_security_pin')" @close="closeModal">
            <ResetPin
                :otpRequested="otpRequested"
                :countdown="countdown"
                @update:resetPinModal="resetPinModal = $event"
                @update:otpRequested="otpRequested = $event"
                @update:countdown="countdown = $event"
                @request-otp="requestOTP"
                @resend-otp="resendOTP"
            />
        </Modal>
    </form>
</template>
