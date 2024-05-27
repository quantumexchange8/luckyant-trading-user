<script setup>
import VOtpInput from "vue3-otp-input";
import Label from "@/Components/Label.vue";
import InputError from "@/Components/InputError.vue";
import {useForm, usePage} from "@inertiajs/vue3";
import Button from "@/Components/Button.vue";

const props = defineProps({
    otpRequested: Boolean,
    countdown: Number,
})

const inputClasses = ['rounded-lg w-full py-2.5 mt-1 bg-white dark:bg-gray-800 placeholder:text-gray-400 dark:placeholder:text-gray-500 text-gray-900 dark:text-gray-50 border border-gray-300 dark:border-gray-800 focus:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500']

const form = useForm({
    pin: '',
    pin_confirmation: '',
    otp: '',
})

const resetPin = () => {
    form.current_pin = undefined
    form.post(route('password.user_pin'), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            closeModal();
        },
    })
}

const emit = defineEmits([
    'update:resetPinModal',
    'update:otpRequested',
    'update:countdown',
    'request-otp',
    'resend-otp'
]);

const closeModal = () => {
    emit('update:resetPinModal', false);
}
</script>

<template>
    <form>
        <div class="w-full flex flex-col gap-5">
            <div class="space-y-6">
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

                <div>
                    <Label for="pin_confirmation" :value="$t('public.confirm_pin')" />
                    <VOtpInput
                        :input-classes="inputClasses"
                        class="flex gap-2"
                        separator=""
                        inputType="password"
                        :num-inputs="6"
                        v-model:value="form.pin_confirmation"
                        :should-auto-focus="false"
                        :should-focus-order="true"
                    />
                </div>

                <div>
                    <Label for="otp" :value="$t('public.otp_verification')" />
                    <div class="flex justify-start items-center gap-4 mt-1">
                        <Button
                            type="button"
                            size="sm"
                            v-if="!otpRequested"
                            :disabled="form.processing"
                            @click="emit('request-otp')"
                        >
                            {{ $t('public.request_otp') }}
                        </Button>
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
                    <div class="flex justify-between items-center gap-4 my-1 text-sm" v-if="otpRequested && countdown > 0">
                        <Transition
                            enter-from-class="opacity-0"
                            leave-to-class="opacity-0"
                            class="transition ease-in-out"
                        >
                            <p
                                class="text-sm text-success-500"
                            >
                                {{ $t('public.success_sent_otp')}}
                            </p>
                        </Transition>
                        {{ $t('public.remaining_time') }} {{ countdown }}s
                    </div>
                    <div class="flex justify-end items-center gap-4 my-1" v-if="otpRequested && countdown <= 0">
                        <span class="text-sm text-primary-500 dark:text-primary-600 underline cursor-pointer" @click="emit('resend-otp')">
                            {{ $t('public.resend_otp_request') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="pt-5 grid grid-cols-2 gap-4 w-full md:w-1/3 md:float-right">
            <Button variant="transparent" type="button" class="justify-center" @click.prevent="closeModal">
                {{$t('public.cancel')}}
            </Button>
            <Button class="justify-center" @click="resetPin" :disabled="form.processing">{{$t('public.confirm')}}</Button>
        </div>
    </form>
</template>
