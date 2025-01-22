<script setup>
import AddPaymentAccount from "@/Pages/Profile/PaymentAccount/AddPaymentAccount.vue";
import Badge from "@/Components/Badge.vue";
import {ref, watch, watchEffect} from "vue";
import Modal from "@/Components/Modal.vue";
import InputError from "@/Components/InputError.vue";
import Input from "@/Components/Input.vue";
import BaseListbox from "@/Components/BaseListbox.vue";
import Button from "@/Components/Button.vue";
import Label from "@/Components/Label.vue";
import {useForm, usePage} from "@inertiajs/vue3";
import VOtpInput from "vue3-otp-input";
import UserPin from "@/Pages/Profile/Partials/UserPin.vue";
import DeletePaymentAccount from "@/Pages/Profile/PaymentAccount/DeletePaymentAccount.vue";

const props = defineProps({
    paymentAccounts: Object,
    countries: Array,
    currencies: Array,
    bank_withdraw: Number,
})

const statusVariant = (transactionStatus) => {
    if (transactionStatus === 'Active') return 'success';
    if (transactionStatus === 'Inactive') return 'danger';
}

const accountModal = ref(false);
const accountDetail = ref(null);
const country = ref();
const currency = ref('');
const checkCurrentPin = ref(false);
const user = usePage().props.auth.user;

const openAccountModal = (account) => {
    accountModal.value = true;
    accountDetail.value = account;
    country.value = parseInt(account.country);
    currency.value = account.currency;

    if (account.payment_platform === 'Bank') {
        form.payment_account_name = usePage().props.auth.user.name;
    }
}

const closeModal = () => {
    accountModal.value = false;
}

watch(country, (newValue) => {
    if (newValue === 132 && accountDetail.value.payment_platform !== 'Crypto') {
        currency.value = 'MYR';
    }
    else if (newValue === 45 && accountDetail.value.payment_platform !== 'Crypto') {
        currency.value = 'CNY';
    }
    else if ((newValue !== 132 && newValue !== 45) && accountDetail.value.payment_platform !== 'Crypto') {
        currency.value = 'USD';
    }
});

const form = useForm({
    payment_account_id: '',
    payment_method: '',
    payment_account_name: '',
    payment_platform_name: '',
    account_no: '',
    bank_sub_branch: '',
    country: null,
    currency: '',
    bank_swift_code: '',
    bank_code: '',
    bank_code_type: '',
    security_pin: '',
})

const submit = () => {
    form.payment_account_id = accountDetail.value.id;
    form.payment_method = accountDetail.value.payment_method;
    form.payment_account_name = accountDetail.value.payment_account_name;
    form.payment_platform_name = accountDetail.value.payment_platform_name;
    form.account_no = accountDetail.value.account_no;
    form.bank_sub_branch = accountDetail.value.bank_sub_branch;
    form.country = country.value;
    form.currency = currency.value;
    form.bank_swift_code = accountDetail.value.bank_swift_code;
    form.bank_code = accountDetail.value.bank_code;
    form.bank_code_type = accountDetail.value.bank_code_type;

    if (form.payment_method === 'Bank') {
        form.payment_account_name = usePage().props.auth.user.name;
    }

    form.post(route('profile.editPaymentAccount'), {
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
        if (usePage().props.auth.user.security_pin) {
            checkCurrentPin.value = true;
        }
    }
});
</script>

<template>
    <div
        class="grid grid-cols-1 sm:grid-cols-3 gap-5"
    >
        <div
            v-for="paymentAccount in paymentAccounts"
            class="card text-gray-300 w-full hover:brightness-90 transition-all cursor-pointer group bg-gradient-to-tl from-gray-600 to-gray-800 hover:from-gray-800 hover:to-gray-700 dark:from-gray-900 dark:to-gray-950 dark:hover:from-gray-800 dark:hover:to-gray-950 border-r-2 border-t-2 border-gray-600 dark:border-gray-900 rounded-lg overflow-hidden relative"
            @click="openAccountModal(paymentAccount)"
        >
            <div class="px-8 py-5">
                <div class="flex justify-between items-center mb-4">
                    <div class="bg-orange-500 w-10 h-10 rounded-full rounded-tl-none group-hover:-translate-y-1 group-hover:shadow-xl group-hover:shadow-red-900 transition-all"></div>
                    <Badge :variant="statusVariant(paymentAccount.status)">{{ paymentAccount.status }}</Badge>
                </div>

                <div class="uppercase font-bold text-xl">
                    {{ paymentAccount.payment_platform_name }}
                </div>
                <div class="text-gray-300 uppercase tracking-widest">
                    {{ paymentAccount.payment_account_name }}
                </div>
                <div class="text-gray-400 mt-6">
                    <p class="font-bold">{{ paymentAccount.currency }}</p>
                    <p>{{ paymentAccount.account_no }}</p>
                </div>
            </div>


            <div class="h-2 w-full bg-gradient-to-l via-yellow-500 group-hover:blur-xl blur-2xl m-auto rounded transition-all absolute bottom-0"></div>
            <div class="h-0.5 group-hover:w-full bg-gradient-to-l via-yellow-700 dark:via-yellow-950 group-hover:via-yellow-500 w-[70%] m-auto rounded transition-all"></div>
        </div>
        <AddPaymentAccount
            :countries="countries"
            :currencies="currencies"
            :bank_withdraw="bank_withdraw"
        />
    </div>

    <Modal :show="accountModal" :title="$t('public.payment_accounts')" @close="closeModal">
        <form action="">
            <div class="mb-2">
                <DeletePaymentAccount
                    :paymentAccount="accountDetail"
                    :user="user"
                />
            </div>
            <div v-if="accountDetail.payment_platform === 'Bank'" class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <!-- <BankSetting/> -->

                <div class="space-y-2 md:col-span-2">
                    <Label
                        for="bank_name"
                        :value="$t('public.bank_name')"
                    />
                    <Input
                        id="bank_name"
                        type="text"
                        class="block w-full"
                        v-model="accountDetail.payment_platform_name"
                        :invalid="form.errors.payment_platform_name"
                    />
                    <InputError :message="form.errors.payment_platform_name" />
                </div>
                <div class="space-y-2">
                    <Label
                        for="bank_sub_branch"
                        :value="$t('public.bank_sub_branch')"
                    />
                    <Input
                        id="bank_sub_branch"
                        type="text"
                        class="block w-full"
                        v-model="accountDetail.bank_sub_branch"
                        :invalid="form.errors.bank_sub_branch"
                    />
                    <InputError :message="form.errors.bank_sub_branch" />
                </div>
                <div class="space-y-2">
                    <Label
                        for="bank_account_name"
                        :value="$t('public.bank_account_name')"
                    />
                    <Input
                        id="bank_account_name"
                        type="text"
                        class="block w-full"
                        v-model="accountDetail.payment_account_name"
                        :invalid="form.errors.payment_account_name"
                        disabled
                    />
                    <InputError :message="form.errors.payment_account_name" />
                </div>
                <div class="space-y-2">
                    <Label
                        for="account_number"
                        :value="$t('public.account_number')"
                    />
                    <Input
                        id="account_number"
                        type="text"
                        class="block w-full"
                        v-model="accountDetail.account_no"
                        :invalid="form.errors.account_no"
                    />
                    <InputError :message="form.errors.account_no" />
                </div>
                <div class="space-y-2">
                    <Label
                        for="bank_swift"
                        :value="$t('public.bank_swift_code')"
                    />
                    <Input
                        id="bank_swift"
                        type="text"
                        class="block w-full"
                        v-model="accountDetail.bank_swift_code"
                        :invalid="form.errors.bank_swift_code"
                    />
                    <InputError :message="form.errors.bank_swift_code" />
                </div>
                <div class="space-y-2">
                    <Label
                        for="bank_code"
                        :value="$t('public.bank_code')"
                    />
                    <Input
                        id="bank_code"
                        type="text"
                        class="block w-full"
                        :placeholder="$t('public.optional')"
                        v-model="accountDetail.bank_code"
                        :invalid="form.errors.bank_code"
                    />
                    <InputError :message="form.errors.bank_code" />
                </div>
                <div class="space-y-2">
                    <Label
                        for="bank_code_type"
                        :value="$t('public.bank_code_type')"
                    />
                    <Input
                        id="bank_code_type"
                        type="text"
                        class="block w-full"
                        :placeholder="$t('public.optional')"
                        v-model="accountDetail.bank_code_type"
                        :invalid="form.errors.bank_code_type"
                    />
                </div>
                <div class="space-y-2">
                    <Label
                        for="country"
                        :value="$t('public.country')"
                    />
                    <BaseListbox
                        :options="countries"
                        v-model="country"
                        :error="!!form.errors.country"
                    />
                </div>
                <div class="space-y-2">
                    <Label
                        for="currency"
                        :value="$t('public.currency')"
                    />
                    <BaseListbox
                        :options="currencies"
                        v-model="currency"
                        :error="!!form.errors.currency"
                    />
                </div>
            </div>

            <div v-else-if="accountDetail.payment_platform === 'Crypto'" class="space-y-2">
                <!-- <CryptoSetting/> -->
                <div class="space-y-2">
                    <Label
                        for="crypto_name"
                        :value="$t('public.payment_service')"
                    />
                    <Input
                        id="crypto_name"
                        type="text"
                        class="block w-full"
                        v-model="accountDetail.payment_platform_name"
                        readonly
                        :invalid="form.errors.payment_platform_name"
                    />
                    <InputError :message="form.errors.payment_platform_name" />
                </div>
                <div class="space-y-2">
                    <Label
                        for="crypto_account_name"
                        :value="$t('public.crypto_wallet_name')"
                    />
                    <Input
                        id="crypto_account_name"
                        type="text"
                        class="block w-full"
                        v-model="accountDetail.payment_account_name"
                        :invalid="form.errors.payment_account_name"
                    />
                    <InputError :message="form.errors.payment_account_name" />
                </div>
                <div class="space-y-2">
                    <Label
                        for="account_number"
                        :value="$t('public.wallet_address')"
                    />
                    <Input
                        id="account_number"
                        type="text"
                        min="0"
                        class="block w-full"
                        v-model="accountDetail.account_no"
                        :invalid="form.errors.account_no"
                    />
                    <InputError :message="form.errors.account_no" />
                </div>
            </div>

            <div class="mt-2">
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
                    type="button"
                    class="justify-center"
                    @click="submit"
                    :disabled="form.processing"
                >
                    {{$t('public.save')}}
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
