<script setup>
import Button from "@/Components/Button.vue";
import {ref, watchEffect} from "vue";
import Modal from "@/Components/Modal.vue";
import UserPin from "@/Pages/Profile/Partials/UserPin.vue";
import InputError from "@/Components/InputError.vue";
import VOtpInput from "vue3-otp-input";
import {useForm, usePage} from "@inertiajs/vue3";
import Label from "@/Components/Label.vue";

const props = defineProps({
    paymentAccount: Object,
    user: Object,
})

const deleteAccountModal = ref(false);
const checkCurrentPin = ref(false);

const openDeleteAccountModal = () => {
    deleteAccountModal.value = true;
}

const closeModal = () => {
    deleteAccountModal.value = false;
}

const form = useForm({
    payment_account_id: props.paymentAccount.id,
    security_pin: '',
})

const submit = () => {
    form.post(route('profile.deletePaymentAccount'), {
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    });
}

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
    <div
        class="flex justify-end text-sm text-error-500 hover:cursor-pointer underline"
        @click="openDeleteAccountModal"
    >
        {{ $t('public.delete_account') }}?
    </div>

    <Modal :show="deleteAccountModal" :title="$t('public.delete_account')" @close="closeModal">
        <form>
            <div class="space-y-2">
                <div class="text-gray-900 dark:text-gray-50">
                    {{ $t('public.delete_account_confirmation') }}
                </div>
                <div v-if="checkCurrentPin || user.security_pin" class="space-y-2">
                    <Label
                        for="security_pin"
                        :value="$t('public.security_pin')"
                    />
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
                    <InputError :message="form.errors.security_pin" />
                </div>

                <div v-else class="space-y-2">
                    <Label
                        for="security_pin"
                        :value="$t('public.security_pin')"
                    />
                    <Button
                        type="button"
                        class="flex justify-center w-full sm:w-fit"
                        @click="openSetupModal"
                    >
                        {{ $t('public.setup_security_pin') }}
                    </Button>

                    <InputError :message="form.errors.security_pin" />
                </div>
            </div>
            <div class="pt-5 grid grid-cols-2 gap-4 w-full md:w-1/3 md:float-right">
                <Button
                    variant="transparent"
                    type="button"
                    class="justify-center"
                    @click.prevent="closeModal"
                >
                    {{$t('public.cancel')}}
                </Button>
                <Button
                    class="justify-center"
                    @click="submit"
                    :disabled="form.processing"
                >
                    {{$t('public.confirm')}}
                </Button>
            </div>
        </form>
    </Modal>

    <Modal :show="setupSecurityPinModal" :title="$t('public.setup_security_pin')" @close="closeSetupModal">
        <div class="flex flex-col gap-5">
            <UserPin
                @update:setupSecurityPinModal="setupSecurityPinModal = $event"
            />
        </div>
    </Modal>
</template>
