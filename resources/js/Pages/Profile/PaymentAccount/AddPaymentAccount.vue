<script setup>
import {ref, watch, computed} from "vue";
import {useForm, usePage} from "@inertiajs/vue3";
import Dialog from "primevue/dialog";
import Select from "primevue/select";
import InputLabel from "@/Components/Label.vue";
import InputText from "primevue/inputtext";
import InputError from "@/Components/InputError.vue";
import Button from "primevue/button";
import {IconCircleCheckFilled} from "@tabler/icons-vue";
import {useLangObserver} from "@/Composables/localeObserver.js";

const props = defineProps({
    paymentAccounts: Array,
})

const visible = ref(false);
const selectedPaymentMethod = ref('');
const {locale} = useLangObserver();

const openDialog = () => {
    visible.value = true;
    getCountries();
}

const paymentMethods = ref([]);
const beneficialNames = ref(
    [usePage().props.auth.user.name, usePage().props.auth.user?.beneficiary_name].filter(name => name !== null && name !== undefined)
);

// Conditionally initialize payment methods
if (usePage().props.auth.user.enable_bank_withdrawal) {
    paymentMethods.value.push('bank')
}

// Conditionally add Crypto (only if user doesn’t already have one)
if (!props.paymentAccounts.some(acc => acc.payment_platform === 'Crypto')) {
    paymentMethods.value.push('crypto')
}

// Set initial selection
if (paymentMethods.value.length > 0) {
    selectedPaymentMethod.value = paymentMethods.value[0]
}

const selectPaymentMethod = (newMethod) => {
    selectedPaymentMethod.value = newMethod;
};

const cryptoWallet = ref('USDT (TRC20)');
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

        const initialCountry = countries.value.find(country => country.id === usePage().props.auth.user.country);
        if (initialCountry) {
            selectedCountry.value = initialCountry;
        } else {
            console.warn('Country with id 45 not found');
        }
    } catch (error) {
        console.error('Error fetching countries:', error);
    } finally {
        loadingCountries.value = false;
    }
};

const form = useForm({
    payment_method: '',
    payment_account_name: '',
    payment_platform_name: '',
    account_no: '',
    country: null,
    currency: '',
    bank_region: '',
    bank_sub_branch: '',
    bank_swift_code: '',
    bank_code: '',
    bank_code_type: '',
});

const submitForm = () => {
    form.payment_method = selectedPaymentMethod.value;
    if (form.payment_method === 'bank') {
        form.country = selectedCountry.value.id;
        form.currency = selectedCountry.value.currency;
    } else if (form.payment_method === 'crypto') {
        form.payment_platform_name = cryptoWallet.value;
    }

    form.post(route('profile.addPaymentAccount'), {
        onSuccess: () => {
            closeDialog();
        },
    })
}

const closeDialog = () => {
    visible.value = false;
}
</script>

<template>
    <div
        v-if="
            // allow if no accounts
            paymentAccounts.length === 0 ||
            // allow if not already has a crypto account
            !paymentAccounts.some(acc => acc.payment_platform === 'Crypto') ||
            // allow if it's bank and user has permission
            (usePage().props.auth.user.enable_bank_withdrawal)
        "
        class="card text-gray-100 w-full hover:brightness-90 transition-all cursor-pointer group bg-gradient-to-tl from-primary-200 to-primary-600 dark:from-primary-900 dark:to-primary-950 hover:from-primary-600 hover:to-primary-300 dark:hover:from-primary-800 dark:hover:to-primary-950 border-r-2 border-t-2 border-primary-600 dark:border-primary-900 rounded-lg overflow-hidden relative duration-200"
        @click="openDialog"
    >
        <div class="px-8 py-5">
            <div class="bg-primary-700 dark:bg-blue-500 w-10 h-10 rounded-full rounded-tl-none group-hover:-translate-y-1 group-hover:shadow-xl group-hover:bg-primary-500 dark:group-hover:bg-blue-600 group-hover:shadow-blue-900 transition-all duration-200"></div>

            <div class="uppercase font-bold text-xl text-center my-8 text-white">
                {{ $t('public.click_add_account') }}
            </div>

            <div class="text-white dark:text-gray-300">
                <p class="font-bold">{{ $t('public.currency') }}</p>
                <p>{{ $t('public.account_no') }}</p>
            </div>
        </div>


        <div class="h-2 w-full bg-gradient-to-l via-sky-500 group-hover:blur-xl blur-2xl m-auto rounded transition-all absolute bottom-0"></div>
        <div class="h-0.5 group-hover:w-full bg-gradient-to-l via-sky-600 dark:via-sky-950 group-hover:via-sky-500 w-[70%] m-auto rounded transition-all"></div>
    </div>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.add_account')"
        class="dialog-xs md:dialog-md"
    >
        <form>
            <div class="flex flex-col items-center gap-8 self-stretch">
                <div class="flex flex-col items-center gap-5 self-stretch">
                    <div class="flex flex-col items-start gap-1 self-stretch">
                        <InputLabel
                            for="accountType"
                            :value="$t('public.payment_methods')"
                        />
                        <div class="grid grid-cols-1 md:grid-cols-2 items-start gap-3 self-stretch">
                            <div
                                v-for="method in paymentMethods"
                                @click="selectPaymentMethod(method)"
                                class="group flex flex-col items-start py-3 px-4 gap-1 self-stretch rounded-lg border shadow-input transition-colors duration-300 select-none cursor-pointer w-full"
                                :class="{
                                    'bg-primary-50 dark:bg-gray-800 border-primary-500': selectedPaymentMethod === method,
                                    'bg-white dark:bg-gray-950 border-gray-300 dark:border-gray-700 hover:bg-primary-50 hover:border-primary-500': selectedPaymentMethod !== method,
                                }"
                            >
                                <div class="flex items-center gap-3 self-stretch">
                                <span
                                    class="flex-grow text-sm font-semibold transition-colors duration-300 group-hover:text-primary-700 dark:group-hover:text-primary-500"
                                    :class="{
                                        'text-primary-700 dark:text-primary-300': selectedPaymentMethod === method,
                                        'text-gray-950 dark:text-white': selectedPaymentMethod !== method
                                    }"
                                >
                                    {{ $t(`public.${method}`) }}
                                </span>
                                    <IconCircleCheckFilled v-if="selectedPaymentMethod === method" size="20" stroke-width="1.25" color="#2970FF" />
                                </div>
                            </div>
                        </div>

                        <InputError :message="form.errors.payment_method" />
                    </div>

                    <div
                        v-if="selectedPaymentMethod === 'bank'"
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
                                :placeholder="$t('public.bank_name')"
                                v-model="form.payment_platform_name"
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
                                v-model="form.bank_region"
                                :placeholder="$t('public.region_of_bank_placeholder')"
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
                                v-model="form.payment_account_name"
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
                            <InputLabel
                                for="account_number"
                                :value="$t('public.bank_account_number')"
                            />
                            <InputText
                                id="account_number"
                                type="text"
                                class="block w-full"
                                v-model="form.account_no"
                                :placeholder="$t('public.bank_account_number')"
                                :invalid="!!form.errors.account_no"
                            />
                            <InputError :message="form.errors.account_no" />
                        </div>

                        <div class="flex flex-col items-start gap-1 self-stretch">
                            <InputLabel
                                for="bank_sub_branch"
                                :value="$t('public.bank_sub_branch')"
                            />
                            <InputText
                                id="bank_sub_branch"
                                type="text"
                                class="block w-full"
                                v-model="form.bank_sub_branch"
                                :placeholder="$t('public.bank_sub_branch')"
                                :invalid="!!form.errors.bank_sub_branch"
                            />
                            <InputError :message="form.errors.bank_sub_branch" />
                        </div>

                        <div class="flex flex-col items-start gap-1 self-stretch">
                            <InputLabel
                                for="bank_swift"
                                :value="$t('public.bank_swift_code')"
                            />
                            <InputText
                                id="bank_swift"
                                type="text"
                                class="block w-full"
                                v-model="form.bank_swift_code"
                                :placeholder="$t('public.bank_swift_code')"
                                :invalid="!!form.errors.bank_swift_code"
                            />
                            <InputError :message="form.errors.bank_swift_code" />
                        </div>
                        <div class="flex flex-col items-start gap-1 self-stretch">
                            <InputLabel
                                for="bank_code"
                                :value="$t('public.bank_code')"
                            />
                            <InputText
                                id="bank_code"
                                type="text"
                                class="block w-full"
                                :placeholder="$t('public.optional')"
                                v-model="form.bank_code"
                                :invalid="!!form.errors.bank_code"
                            />
                            <InputError :message="form.errors.bank_code" />
                        </div>
                        <div class="flex flex-col items-start gap-1 self-stretch">
                            <InputLabel
                                for="bank_code_type"
                                :value="$t('public.bank_code_type')"
                            />
                            <InputText
                                id="bank_code_type"
                                type="text"
                                class="block w-full"
                                :placeholder="$t('public.optional')"
                                v-model="form.bank_code_type"
                                :invalid="!!form.errors.bank_code_type"
                            />
                            <InputError :message="form.errors.bank_code_type" />
                        </div>
                        <div class="flex flex-col items-start gap-1 self-stretch">
                            <InputLabel
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
                            <InputLabel
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
                        v-else-if="selectedPaymentMethod === 'crypto'"
                        class="grid grid-cols-1 sm:grid-cols-2 gap-3 w-full"
                    >
                        <!-- CryptoSetting -->
                        <div class="flex flex-col items-start gap-1 self-stretch">
                            <InputLabel
                                for="crypto_name"
                                :value="$t('public.payment_service')"
                            />
                            <InputText
                                id="crypto_name"
                                type="text"
                                class="block w-full"
                                v-model="cryptoWallet"
                                readonly
                                :invalid="!!form.errors.payment_platform_name"
                            />
                            <InputError :message="form.errors.payment_platform_name" />
                        </div>
                        <div class="flex flex-col items-start gap-1 self-stretch">
                            <InputLabel
                                for="crypto_account_name"
                                :value="$t('public.crypto_wallet_name')"
                            />
                            <InputText
                                id="crypto_account_name"
                                type="text"
                                class="block w-full"
                                v-model="form.payment_account_name"
                                :invalid="!!form.errors.payment_account_name"
                            />
                            <InputError :message="form.errors.payment_account_name" />
                        </div>
                        <div class="flex flex-col items-start gap-1 self-stretch sm:col-span-2">
                            <InputLabel
                                for="account_number"
                                :value="$t('public.wallet_address')"
                            />
                            <InputText
                                id="account_number"
                                type="text"
                                min="0"
                                class="block w-full"
                                v-model="form.account_no"
                                :invalid="!!form.errors.account_no"
                            />
                            <InputError :message="form.errors.account_no" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex w-full justify-end gap-3 mt-5">
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
        </form>
    </Dialog>
</template>
