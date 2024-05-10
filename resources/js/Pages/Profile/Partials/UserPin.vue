<script setup>
import VOtpInput from "vue3-otp-input";
import {ref, watchEffect} from "vue";
import Label from "@/Components/Label.vue";
import {useForm, usePage} from "@inertiajs/vue3";
import Button from "@/Components/Button.vue";
import InputError from "@/Components/InputError.vue";

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
            </div>
        </div>
        <div class="w-full mt-8">
            <div class="flex justify-end items-center gap-4">
                <Button :disabled="form.processing" @click.prevent="updatePassword">{{ $t('public.save') }}</Button>

                <Transition
                    enter-from-class="opacity-0"
                    leave-to-class="opacity-0"
                    class="transition ease-in-out"
                >
                    <p
                        v-if="form.recentlySuccessful"
                        class="text-sm text-gray-600 dark:text-gray-400"
                    >
                        {{ $t('public.saved') }}.
                    </p>
                </Transition>
            </div>
        </div>
    </form>
</template>
