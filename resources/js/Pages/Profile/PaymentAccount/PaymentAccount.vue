<script setup>
import AddPaymentAccount from "@/Pages/Profile/PaymentAccount/AddPaymentAccount.vue";
import Badge from "@/Components/Badge.vue";
import {ref, watchEffect} from "vue";
import InputError from "@/Components/InputError.vue";
import Button from "primevue/button";
import Label from "@/Components/Label.vue";
import {useForm, usePage} from "@inertiajs/vue3";
import VOtpInput from "vue3-otp-input";
import UserPin from "@/Pages/Profile/Partials/UserPin.vue";
import DeletePaymentAccount from "@/Pages/Profile/PaymentAccount/DeletePaymentAccount.vue";
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import InputLabel from "@/Components/Label.vue";
import Select from "primevue/select";
import {useLangObserver} from "@/Composables/localeObserver.js";

const props = defineProps({
    paymentAccounts: Object,
})

const statusVariant = (transactionStatus) => {
    if (transactionStatus === 'Active') return 'success';
    if (transactionStatus === 'Inactive') return 'danger';
}

const visible = ref(false);
const accountDetail = ref(null);
const checkCurrentPin = ref(false);
const user = usePage().props.auth.user;
const {locale} = useLangObserver();

const beneficialNames = ref(
    [user.name, user?.beneficiary_name].filter(name => name !== null && name !== undefined)
);

const openDialog = (account) => {
    visible.value = true;
    accountDetail.value = account;
    getCountries();
}

const countries = ref([]);
const selectedCountry = ref();
const loadingCountries = ref(false);

const getCountries = async () => {
    loadingCountries.value = true;
    try {
        const response = await axios.get('/getCountries');
        countries.value = response.data;

        countries.value = response.data.map(country => ({
            ...country,
            translations: JSON.parse(country.translations)
        }))

        selectedCountry.value = countries.value.find(
            country => country.id === Number(accountDetail.value.country)
        );
    } catch (error) {
        console.error('Error fetching countries:', error);
    } finally {
        loadingCountries.value = false;
    }
};

const closeDialog = () => {
    visible.value = false;
}

const form = useForm({
    payment_account_id: '',
    payment_method: '',
    payment_account_name: '',
    payment_platform_name: '',
    account_no: '',
    bank_sub_branch: '',
    country: null,
    currency: '',
    bank_region: '',
    bank_swift_code: '',
    bank_code: '',
    bank_code_type: '',
    security_pin: '',
})

const submitForm = () => {
    form.payment_account_id = accountDetail.value.id;
    form.payment_method = accountDetail.value.payment_platform === 'Bank' ? 'bank' : 'crypto';
    form.payment_platform_name = accountDetail.value.payment_platform_name;
    form.payment_account_name = accountDetail.value.payment_account_name;
    form.account_no = accountDetail.value.account_no;
    form.bank_sub_branch = accountDetail.value.bank_sub_branch;
    form.country = selectedCountry.value?.id;
    form.currency = accountDetail.value.payment_platform === 'Bank' ? selectedCountry.value?.currency : 'USDT';
    form.bank_region = accountDetail.value.bank_region;
    form.bank_swift_code = accountDetail.value.bank_swift_code;
    form.bank_code = accountDetail.value.bank_code;
    form.bank_code_type = accountDetail.value.bank_code_type;

    form.post(route('profile.editPaymentAccount'), {
        onSuccess: () => {
            closeDialog();
            form.reset();
        },
    });
}

const inputClasses = ['rounded-lg w-full py-2.5 bg-white dark:bg-gray-800 placeholder:text-gray-400 dark:placeholder:text-gray-500 text-gray-900 dark:text-gray-50 border border-gray-300 dark:border-gray-800 focus:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500']

const setupSecurityPinModal = ref(false);

const openSetupModal = () => {
    setupSecurityPinModal.value = true
}

watchEffect(() => {
    if (usePage().props.toast !== null) {
        if (usePage().props.auth.user.security_pin) {
            checkCurrentPin.value = true;
        }
    }
});
</script>

<template>
    <div
        class="grid grid-cols-1 sm:grid-cols-3 gap-5 focus-visible:outline-none"
    >
        <div
            v-for="paymentAccount in paymentAccounts"
            class="card text-gray-300 w-full hover:brightness-90 transition-all cursor-pointer group bg-gradient-to-tl from-gray-400 to-gray-600 hover:from-gray-600 hover:to-gray-600 dark:from-gray-900 dark:to-gray-950 dark:hover:from-gray-800 dark:hover:to-gray-950 border-r-2 border-t-2 border-gray-200 dark:border-gray-900 rounded-lg overflow-hidden relative"
            :key="paymentAccount.id"
            @click="openDialog(paymentAccount)"
        >
            <div class="px-8 py-5">
                <div class="flex justify-between items-center mb-4">
                    <div class="bg-orange-500 w-10 h-10 rounded-full rounded-tl-none group-hover:-translate-y-1 group-hover:shadow-xl group-hover:shadow-red-900 transition-all"></div>
                    <Badge :variant="statusVariant(paymentAccount.status)">{{ paymentAccount.status }}</Badge>
                </div>

                <div class="uppercase font-bold text-xl">
                    {{ paymentAccount.payment_platform_name }}
                </div>
                <div class="text-white uppercase tracking-widest">
                    {{ paymentAccount.payment_account_name }}
                </div>
                <div class="text-gray-100 mt-6">
                    <p class="font-bold">{{ paymentAccount.currency }}</p>
                    <p>{{ paymentAccount.account_no }}</p>
                </div>
            </div>


            <div class="h-2 w-full bg-gradient-to-l via-yellow-500 group-hover:blur-xl blur-2xl m-auto rounded transition-all absolute bottom-0"></div>
            <div class="h-0.5 group-hover:w-full bg-gradient-to-l  via-yellow-400 dark:via-yellow-950 group-hover:via-yellow-500 w-[70%] m-auto rounded transition-all"></div>
        </div>
        <AddPaymentAccount />
    </div>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.payment_accounts')"
        class="dialog-xs md:dialog-md"
    >
        <form>
            <div
                v-if="accountDetail.payment_platform === 'Bank'"
                class="grid grid-cols-1 sm:grid-cols-2 gap-3 w-full"
            >
                <!-- Bank Setting -->
                <div class="flex flex-col items-start gap-1 self-stretch">
                    <InputLabel
                        for="bank_name"
                        :value="$t('public.bank_name')"
                    />
                    <InputText
                        id="payment_platform_name"
                        type="text"
                        class="block w-full"
                        v-model="accountDetail.payment_platform_name"
                        :invalid="!!form.errors.payment_platform_name"
                    />
                    <InputError :message="form.errors.payment_platform_name" />
                </div>

                <div class="flex flex-col items-start gap-1 self-stretch">
                    <InputLabel
                        for="bank_region"
                        :value="$t('public.region_of_bank')"
                    />
                    <InputText
                        id="bank_region"
                        type="text"
                        class="block w-full"
                        v-model="accountDetail.bank_region"
                        :invalid="!!form.errors.bank_region"
                    />
                    <InputError :message="form.errors.bank_region" />
                </div>

                <div class="flex flex-col items-start gap-1 self-stretch">
                    <InputLabel
                        for="payment_account_name"
                        :value="$t('public.bank_account_name')"
                    />
                    <Select
                        v-model="accountDetail.payment_account_name"
                        :options="beneficialNames"
                        :placeholder="$t('public.select_account')"
                        class="w-full"
                        :invalid="!!form.errors.payment_account_name"
                    >
                        <template #value="slotProps">
                            <div v-if="slotProps.value" class="flex items-center">
                                {{ slotProps.value }}
                            </div>
                            <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
                        </template>
                        <template #option="slotProps">
                            <div>{{ slotProps.option }}</div>
                        </template>
                    </Select>
                    <InputError :message="form.errors.payment_account_name" />
                </div>

                <div class="flex flex-col items-start gap-1 self-stretch">
                    <Label
                        for="account_number"
                        :value="$t('public.bank_account_number')"
                    />
                    <InputText
                        id="account_number"
                        type="text"
                        class="block w-full"
                        v-model="accountDetail.account_no"
                        :invalid="!!form.errors.account_no"
                    />
                    <InputError :message="form.errors.account_no" />
                </div>

                <div class="flex flex-col items-start gap-1 self-stretch">
                    <Label
                        for="bank_sub_branch"
                        :value="$t('public.bank_sub_branch')"
                    />
                    <InputText
                        id="bank_sub_branch"
                        type="text"
                        class="block w-full"
                        v-model="accountDetail.bank_sub_branch"
                        :invalid="!!form.errors.bank_sub_branch"
                    />
                    <InputError :message="form.errors.bank_sub_branch" />
                </div>

                <div class="flex flex-col items-start gap-1 self-stretch">
                    <Label
                        for="bank_swift"
                        :value="$t('public.bank_swift_code')"
                    />
                    <InputText
                        id="bank_swift"
                        type="text"
                        class="block w-full"
                        v-model="accountDetail.bank_swift_code"
                        :invalid="!!form.errors.bank_swift_code"
                    />
                    <InputError :message="form.errors.bank_swift_code" />
                </div>

                <div class="flex flex-col items-start gap-1 self-stretch">
                    <Label
                        for="bank_code"
                        :value="$t('public.bank_code')"
                    />
                    <InputText
                        id="bank_code"
                        type="text"
                        class="block w-full"
                        :placeholder="$t('public.optional')"
                        v-model="accountDetail.bank_code"
                        :invalid="!!form.errors.bank_code"
                    />
                    <InputError :message="form.errors.bank_code" />
                </div>

                <div class="flex flex-col items-start gap-1 self-stretch">
                    <Label
                        for="bank_code_type"
                        :value="$t('public.bank_code_type')"
                    />
                    <InputText
                        id="bank_code_type"
                        type="text"
                        class="block w-full"
                        :placeholder="$t('public.optional')"
                        v-model="accountDetail.bank_code_type"
                        :invalid="!!form.errors.bank_code_type"
                    />
                </div>

                <div class="flex flex-col items-start gap-1 self-stretch">
                    <Label
                        for="country"
                        :value="$t('public.country')"
                    />
                    <Select
                        v-model="selectedCountry"
                        :options="countries"
                        optionLabel="name"
                        :placeholder="$t('public.select_country')"
                        class="w-full"
                        filter
                        :filter-fields="['name', 'iso2', 'currency']"
                        :loading="loadingCountries"
                        :invalid="!!form.errors.country"
                    >
                        <template #value="slotProps">
                            <div v-if="slotProps.value" class="flex items-center">
                                {{ slotProps.value.translations[locale] ?? slotProps.value.name }}
                            </div>
                            <span v-else>{{ slotProps.placeholder }}</span>
                        </template>
                        <template #option="slotProps">
                            <div>{{ slotProps.option.translations[locale] ?? slotProps.option.name }}</div>
                        </template>
                    </Select>
                    <InputError :message="form.errors.country" />
                </div>

                <div class="flex flex-col items-start gap-1 self-stretch">
                    <Label
                        for="currency"
                        :value="$t('public.currency')"
                    />
                    <Select
                        v-model="selectedCountry"
                        :options="countries"
                        optionLabel="currency"
                        :placeholder="$t('public.select_currency')"
                        class="w-full"
                        filter
                        :filter-fields="['name', 'iso2', 'currency']"
                        :loading="loadingCountries"
                        :invalid="!!form.errors.currency"
                    >
                        <template #value="slotProps">
                            <div v-if="slotProps.value" class="flex items-center gap-1">
                                {{ slotProps.value.currency_symbol}} <span class="text-gray-400">({{ slotProps.value.currency }})</span>
                            </div>
                            <span v-else>{{ slotProps.placeholder }}</span>
                        </template>
                        <template #option="slotProps">
                            <div>
                                {{ slotProps.option.currency_symbol}} <span class="text-gray-400">({{ slotProps.option.currency }})</span>
                            </div>
                        </template>
                    </Select>
                    <InputError :message="form.errors.currency" />
                </div>
            </div>

            <div
                v-else-if="accountDetail.payment_platform === 'Crypto'"
                class="grid grid-cols-1 sm:grid-cols-2 gap-3 w-full"
            >
                <!-- CryptoSetting -->
                <div class="flex flex-col items-start gap-1 self-stretch">
                    <Label
                        for="crypto_name"
                        :value="$t('public.payment_service')"
                    />
                    <InputText
                        id="crypto_name"
                        type="text"
                        class="block w-full"
                        v-model="accountDetail.payment_platform_name"
                        readonly
                        :invalid="!!form.errors.payment_platform_name"
                    />
                    <InputError :message="form.errors.payment_platform_name" />
                </div>
                <div class="flex flex-col items-start gap-1 self-stretch">
                    <Label
                        for="crypto_account_name"
                        :value="$t('public.crypto_wallet_name')"
                    />
                    <InputText
                        id="crypto_account_name"
                        type="text"
                        class="block w-full"
                        v-model="accountDetail.payment_account_name"
                        :invalid="!!form.errors.payment_account_name"
                    />
                    <InputError :message="form.errors.payment_account_name" />
                </div>
                <div class="flex flex-col items-start gap-1 self-stretch">
                    <Label
                        for="account_number"
                        :value="$t('public.wallet_address')"
                    />
                    <InputText
                        id="account_number"
                        type="text"
                        min="0"
                        class="block w-full"
                        v-model="accountDetail.account_no"
                        :invalid="!!form.errors.account_no"
                    />
                    <InputError :message="form.errors.account_no" />
                </div>
            </div>

            <div class="mt-2">
                <div v-if="checkCurrentPin || user.security_pin" class="flex flex-col items-start gap-1 self-stretch">
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

                <div v-else class="flex flex-col items-start gap-1 self-stretch">
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

            <div class="flex flex-col-reverse gap-5 md:flex-row md:justify-between mt-5">
                <DeletePaymentAccount
                    :paymentAccount="accountDetail"
                    :user="user"
                    @update:visible="visible = $event"
                />
                <div class="flex w-full justify-end gap-3">
                    <Button
                        type="button"
                        severity="secondary"
                        text
                        class="justify-center w-full md:w-auto px-6"
                        @click="closeDialog"
                        :disabled="form.processing"
                    >
                        {{ $t('public.cancel') }}
                    </Button>
                    <Button
                        type="submit"
                        class="justify-center w-full md:w-auto px-6"
                        @click.prevent="submitForm"
                        :disabled="form.processing"
                    >
                        {{ $t('public.confirm') }}
                    </Button>
                </div>
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
