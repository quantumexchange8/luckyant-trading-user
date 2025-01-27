<script setup>
import Button from "primevue/button";
import {ref, watchEffect} from "vue";
import UserPin from "@/Pages/Profile/Partials/UserPin.vue";
import InputError from "@/Components/InputError.vue";
import VOtpInput from "vue3-otp-input";
import {useForm, usePage} from "@inertiajs/vue3";
import Label from "@/Components/Label.vue";
import Dialog from "primevue/dialog";

const props = defineProps({
    paymentAccount: Object,
    user: Object,
})

const visible = ref(false);
const checkCurrentPin = ref(false);

const openDeleteAccountModal = () => {
    visible.value = true;
}

const closeModal = () => {
    visible.value = false;
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
    visible.value = false;
    setupSecurityPinModal.value = true
}

watchEffect(() => {
    if (usePage().props.title !== null) {
        checkCurrentPin.value = true;
    }
});
</script>

<template>
    <Button
        type="button"
        severity="danger"
        @click="openDeleteAccountModal"
        class="px-6 w-full md:w-auto text-nowrap text-sm font-semibold"
    >
        {{ $t('public.delete_account') }}?
    </Button>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.delete_account')"
        class="dialog-xs md:dialog-sm"
    >
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
                        severity="info"
                        size="small"
                        class="flex justify-center w-full sm:w-fit"
                        @click="openSetupModal"
                    >
                        {{ $t('public.setup_security_pin') }}
                    </Button>

                    <InputError :message="form.errors.security_pin" />
                </div>
            </div>
            <div class="pt-5 flex justify-end">
                <Button
                    type="button"
                    severity="secondary"
                    text
                    class="justify-center w-full md:w-auto px-6"
                    @click="closeModal"
                    :disabled="form.processing"
                >
                    {{ $t('public.cancel') }}
                </Button>
                <Button
                    type="submit"
                    class="justify-center w-full md:w-auto px-6"
                    @click.prevent="submit"
                    :disabled="form.processing"
                >
                    {{ $t('public.confirm') }}
                </Button>
            </div>
        </form>
    </Dialog>

    <Dialog
        v-model:visible="setupSecurityPinModal"
        modal
        :header="$t('public.setup_security_pin')"
        class="dialog-xs md:dialog-sm"
    >
        <div class="flex flex-col gap-5">
            <UserPin
                @update:setupSecurityPinModal="setupSecurityPinModal = $event"
            />
        </div>
    </Dialog>
</template>
