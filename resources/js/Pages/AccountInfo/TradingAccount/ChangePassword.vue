<script setup>
import {useForm, usePage} from "@inertiajs/vue3";
import Label from "@/Components/Label.vue";
import Input from "@/Components/Input.vue";
import InputError from "@/Components/InputError.vue";
import Button from "@/Components/Button.vue";
import BaseListbox from "@/Components/BaseListbox.vue";
import { EyeIcon, EyeOffIcon, CheckIcon, UserAddIcon } from '@heroicons/vue/outline'
import {ref, watchEffect} from "vue";
import VOtpInput from "vue3-otp-input";
import UserPin from "@/Pages/Profile/Partials/UserPin.vue";
import Modal from "@/Components/Modal.vue";

const props = defineProps({
    account: Object,
})
const showPassword = ref(false);
const showPassword2 = ref(false);
const showPassword3 = ref(false);
const showPassword4 = ref(false);
const emit = defineEmits(['update:accountActionModal']);
const form = useForm({
    meta_login: props.account.meta_login,
    master_password: '',
    confirm_master_password: '',
    investor_password: '',
    confirm_investor_password: '',
    security_pin: '',
})

const closeModal = () => {
    emit('update:accountActionModal', false);
}

const submit = () => {
    form.post(route('account_info.changePassword'), {
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    });
}

const toggleMasterPasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};

const toggleMasterPasswordVisibilityConfirm = () => {
    showPassword2.value = !showPassword2.value;
}

const toggleInvestorPasswordVisibility = () => {
    showPassword3.value = !showPassword3.value;
}

const toggleInvestorPasswordVisibilityConfirm = () => {
    showPassword4.value = !showPassword4.value;
}

const passwordRules = [
    { message: 'register_terms_1_8', regex: /.{8,}/ },
    { message: 'register_terms_2', regex: /[A-Z]+/ },
    { message: 'register_terms_3', regex: /[a-z]+/ },
    { message: 'register_terms_4', regex: /[0-9]+/ },
    { message: 'register_terms_5', regex: /[!@#$%^&*()_+{}\[\]:;<>,.?~\\-]+/ }
];

const passwordValidation = () => {
    let masterValid = false;
    let investorValid = false;
    let masterMessages = [];
    let investorMessages = [];

    // Check if either master_password or investor_password has data
    const hasMasterPassword = form.master_password !== '';
    const hasInvestorPassword = form.investor_password !== '';

    // Validate master password
    if (hasMasterPassword) {
        for (let condition of passwordRules) {
            const isConditionValid = condition.regex.test(form.master_password);

            if (isConditionValid) {
                masterValid = true;
            }

            masterMessages.push({
                message: condition.message,
                valid: isConditionValid,
            });
        }
    }

    // Validate investor password
    if (hasInvestorPassword) {
        for (let condition of passwordRules) {
            const isConditionValid = condition.regex.test(form.investor_password);

            if (isConditionValid) {
                investorValid = true;
            }

            investorMessages.push({
                message: condition.message,
                valid: isConditionValid,
            });
        }
    }

    return { masterValid, masterMessages, investorValid, investorMessages };
};

const user = usePage().props.auth.user;
const checkCurrentPin = ref(false);

const inputClasses = ['rounded-lg w-full py-2.5 bg-white dark:bg-gray-800 placeholder:text-gray-400 dark:placeholder:text-gray-500 text-gray-900 dark:text-gray-50 border border-gray-300 dark:border-gray-800 focus:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500']

const setupSecurityPinModal = ref(false);

const openSetupModal = () => {
    setupSecurityPinModal.value = true
}

const closeSetupModal = () => {
    setupSecurityPinModal.value = false
}

watchEffect(() => {
    if (usePage().props.title !== null) {
        checkCurrentPin.value = true;
    }
});
 </script>

<template>
    <form class="space-y-2">
        <div class="flex flex-col sm:flex-row gap-4 py-2">
            <Label class="text-sm dark:text-white w-full md:w-1/4" for="master_password" :value="$t('public.master_password')" />
            <div class="flex flex-col w-full">
                <div class="relative">
                <Input
                    id="master_password"
                    :type="showPassword ? 'text' : 'password'"
                    :placeholder="$t('public.new_password')"
                    class="block w-full"
                    v-model="form.master_password"
                    :invalid="form.errors.master_password"
                />
                <div
                    class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer"
                    @click="toggleMasterPasswordVisibility"
                >
                    <template v-if="showPassword">
                        <EyeIcon aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                    </template>
                    <template v-else>
                        <EyeOffIcon aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                    </template>
                </div>
                </div>
                <InputError :message="form.errors.master_password" class="mt-2" />
            </div>
        </div>
        <div class="flex flex-col sm:flex-row gap-4 py-2">
            <Label class="text-sm dark:text-white w-full md:w-1/4" for="confirm_master_password" :value="$t('public.confirm_master_password')" />
            <div class="flex flex-col w-full">
                <div class="relative">
                <Input
                    id="confirm_master_password"
                    :type="showPassword2 ? 'text' : 'password'"
                    :placeholder="$t('public.confirm_password')"
                    class="block w-full"
                    v-model="form.confirm_master_password"
                    :invalid="form.errors.confirm_master_password"
                />
                <div
                    class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer"
                    @click="toggleMasterPasswordVisibilityConfirm"
                >
                    <template v-if="showPassword2">
                        <EyeIcon aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                    </template>
                    <template v-else>
                        <EyeOffIcon aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                    </template>
                </div>
                </div>
                <InputError :message="form.errors.confirm_master_password" class="mt-2" />
            </div>
        </div>

        <div v-if="form.master_password" class="flex flex-col items-start gap-3 self-stretch py-2">
            <div v-for="message in passwordValidation().masterMessages" :key="message.key" class="flex items-center gap-2 self-stretch">
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

        <div class="border-t border-gray-300 py-2"></div>
        <div class="flex flex-col sm:flex-row gap-4 py-2">
            <Label class="text-sm dark:text-white w-full md:w-1/4" for="investor_password" :value="$t('public.investor_password')" />
            <div class="flex flex-col w-full">
                <div class="relative">
                <Input
                    id="investor_password"
                    :type="showPassword3 ? 'text' : 'password'"
                    :placeholder="$t('public.new_password')"
                    class="block w-full"
                    v-model="form.investor_password"
                    :invalid="form.errors.investor_password"
                />
                <div
                    class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer"
                    @click="toggleInvestorPasswordVisibility"
                >
                    <template v-if="showPassword3">
                        <EyeIcon aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                    </template>
                    <template v-else>
                        <EyeOffIcon aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                    </template>
                </div>
                </div>
                <InputError :message="form.errors.investor_password" class="mt-2" />
            </div>
        </div>
        <div class="flex flex-col sm:flex-row gap-4 py-2">
            <Label class="text-sm dark:text-white w-full md:w-1/4" for="confirm_investor_password" :value="$t('public.confirm_investor_password')" />
            <div class="flex flex-col w-full">
                <div class="relative">
                <Input
                    id="confirm_investor_password"
                    :type="showPassword4 ? 'text' : 'password'"
                    :placeholder="$t('public.confirm_password')"
                    class="block w-full"
                    v-model="form.confirm_investor_password"
                    :invalid="form.errors.confirm_investor_password"
                />
                <div
                    class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer"
                    @click="toggleInvestorPasswordVisibilityConfirm"
                >
                    <template v-if="showPassword4">
                        <EyeIcon aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                    </template>
                    <template v-else>
                        <EyeOffIcon aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                    </template>
                </div>
                </div>
                <InputError :message="form.errors.confirm_investor_password" class="mt-2" />
            </div>
        </div>

        <div v-if="form.investor_password" class="flex flex-col items-start gap-3 self-stretch py-2">
            <div v-for="message in passwordValidation().investorMessages" :key="message.key" class="flex items-center gap-2 self-stretch">
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

        <div v-if="checkCurrentPin || user.security_pin" class="flex flex-col sm:flex-row gap-4 pb-5">
            <Label
                for="pin"
                class="text-sm dark:text-white w-full md:w-1/4 pt-0.5"
                :value="$t('public.security_pin')"
            />
            <div class="flex flex-col w-full">
                <VOtpInput
                    :input-classes="inputClasses"
                    class="flex gap-2"
                    separator=""
                    inputType="password"
                    :num-inputs="6"
                    v-model:value="form.security_pin"
                    :should-auto-focus="false"
                    :should-focus-order="true"
                />

                <InputError
                    :message="form.errors.security_pin"
                    class="mt-2"
                />
            </div>
        </div>

        <div v-else class="flex flex-col sm:flex-row gap-4 pb-5">
            <div class="text-sm dark:text-white w-full md:w-1/4 pt-0.5">
                {{ $t('public.security_pin') }}
            </div>
            <div class="flex flex-col w-full">
                <Button
                    type="button"
                    class="flex justify-center w-full sm:w-fit"
                    @click="openSetupModal"
                >
                    {{ $t('public.setup_security_pin') }}
                </Button>

                <InputError
                    :message="form.errors.security_pin"
                    class="mt-2"
                />
            </div>
        </div>

        <div class="pt-5 grid grid-cols-2 gap-4 w-full md:w-1/3 md:float-right">
            <Button variant="transparent" type="button" class="justify-center" @click.prevent="closeModal">
                {{$t('public.cancel')}}
            </Button>
            <Button class="justify-center" @click="submit" :disabled="form.processing">{{$t('public.confirm')}}</Button>
        </div>
    </form>

    <Modal :show="setupSecurityPinModal" :title="$t('public.setup_security_pin')" @close="closeSetupModal">
        <div class="flex flex-col gap-5">
            <UserPin
                @update:setupSecurityPinModal="setupSecurityPinModal = $event"
            />
        </div>
    </Modal>
</template>
