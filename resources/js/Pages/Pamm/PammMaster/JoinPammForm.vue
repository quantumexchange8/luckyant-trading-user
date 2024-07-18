<script setup>
import Button from "@/Components/Button.vue";
import {onMounted, ref, watch, watchEffect} from "vue";
import Modal from "@/Components/Modal.vue";
import {transactionFormat} from "@/Composables/index.js";
import BaseListbox from "@/Components/BaseListbox.vue";
import InputError from "@/Components/InputError.vue";
import Label from "@/Components/Label.vue";
import {useForm, usePage} from "@inertiajs/vue3";
import Input from "@/Components/Input.vue";
import Checkbox from "@/Components/Checkbox.vue";
import {RadioGroup, RadioGroupLabel, RadioGroupOption} from "@headlessui/vue";

const props = defineProps({
    masterAccount: Object,
    terms: Object,
})

const subscribeAccountModal = ref(false);
const tradingAccountsSel = ref();
const loading = ref('Loading..');
const { formatAmount } = transactionFormat();
const confirmModal = ref(false);
const amount = ref(null);
const amountReturned = ref(0);
const subscriptionPackages = ref(null);
const depositAmount = ref(null);

const openSubscribeAccountModal = () => {
    subscribeAccountModal.value = true;
};

const closeModal = () => {
    subscribeAccountModal.value = false;
}

const form = useForm({
    master_id: props.masterAccount.id,
    meta_login: '',
    amount: null,
    amount_package_id: '',
    package_product: '',
    delivery_address: '',
    terms: '',
})

const getTradingAccounts = async () => {
    try {
        const response = await axios.get('/account_info/getTradingAccounts?type=subscribe&meta_login=' + props.masterAccount.meta_login);
        tradingAccountsSel.value = response.data;
        form.meta_login = tradingAccountsSel.value.length > 0 ? tradingAccountsSel.value[0].value : null;
    } catch (error) {
        console.error('Error refreshing trading accounts data:', error);
    }
};

getTradingAccounts();

const getMasterSubscriptionPackages = async () => {
    try {
        const response = await axios.get('/pamm/getMasterSubscriptionPackages?master_id=' + props.masterAccount.id);
        subscriptionPackages.value = response.data;
    } catch (error) {
        console.error('Error refreshing trading accounts data:', error);
    }
};

getMasterSubscriptionPackages();

watch(depositAmount, (newAmount) => {
    amount.value = newAmount.amount
})

const submit = () => {
    const selectedTradingAccount = tradingAccountsSel.value.find(account => account.value === form.meta_login);
    if (selectedTradingAccount){
        const label = selectedTradingAccount.label;
        const amountString = label.split('(')[1].split(')')[0];
        amount.value = parseFloat(amountString.replace(/[^0-9.]/g, ''));
        amountReturned.value = (amount.value % 100).toFixed(2);
        amount.value = (amount.value - amountReturned.value).toFixed(2);
        amount.value = depositAmount.value.amount
    };
    confirmModal.value = true;
};


const confirmSubmit = () => {
    form.amount = amount.value;
    form.amount_package_id = depositAmount.value.value;

    form.post(route('pamm.followPammMaster'), {
        onSuccess: () => {
            closeModal();
            form.reset();
            confirmModal.value = false;
            subscribeAccountModal.value = false;
        },
        onError: () => {
            confirmModal.value = false;
        }
    });
};

const cancelSubmit = () => {
    confirmModal.value = false;
};

const currentLocale = ref(usePage().props.locale);
const termsModal = ref(false);

const openTermsModal = () => {
    termsModal.value = true
}

const closeTermsModal = () => {
    termsModal.value = false
}

watchEffect(() => {
    if (usePage().props.title !== null) {
        getTradingAccounts();
    }
});
</script>

<template>
    <Button
        type="button"
        class="w-full flex justify-center"
        @click="openSubscribeAccountModal"
    >
        {{ $t('public.subscribe') }}
    </Button>

    <Modal :show="subscribeAccountModal" :title="$t('public.subscribe_master')" @close="closeModal">
        <form class="space-y-2">
            <div class="p-5 bg-gray-100 dark:bg-gray-800 rounded-lg">
                <div class="flex flex-col items-start gap-3 self-stretch">
                    <div>
                        <div v-if="currentLocale === 'en'" class="text-xl dark:text-white">
                            {{ masterAccount.trading_user.name }}
                        </div>
                        <div v-if="currentLocale === 'cn'" class="text-xl dark:text-white">
                            {{ masterAccount.trading_user.company ? masterAccount.trading_user.company : masterAccount.trading_user.name }}
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-2 self-stretch">
                        <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('public.account_number') }}
                        </div>
                        <div class="text-base text-gray-800 dark:text-white font-semibold">
                            {{ masterAccount.meta_login }}
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-2 self-stretch">
                        <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('public.minimum_equity_to_join') }}
                        </div>
                        <div class="text-base text-gray-800 dark:text-white font-semibold">
                            $ {{ formatAmount(masterAccount.min_join_equity) }}
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-2 self-stretch">
                        <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('public.percentage_of_sharing_profit') }}
                        </div>
                        <div class="text-base text-gray-800 dark:text-white font-semibold">
                            {{ formatAmount(masterAccount.sharing_profit, 0) }} %
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-2 self-stretch">
                        <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('public.estimated_monthly_returns') }}
                        </div>
                        <div class="text-base text-gray-800 dark:text-white font-semibold">
                            {{ masterAccount.estimated_monthly_returns }}
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-2 self-stretch">
                        <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('public.estimated_lot_size') }}
                        </div>
                        <div class="text-base text-gray-800 dark:text-white font-semibold">
                            {{ masterAccount.estimated_lot_size }}
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-2 self-stretch">
                        <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('public.roi_period') }}
                        </div>
                        <div class="text-base text-gray-800 dark:text-white font-semibold">
                            {{ masterAccount.roi_period }} {{ $t('public.days') }}
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-2 self-stretch">
                        <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('public.max_drawdown') }}
                        </div>
                        <div class="text-base text-gray-800 dark:text-white font-semibold">
                            {{ masterAccount.max_drawdown }}
                        </div>
                    </div>
                    <div class="flex flex-col gap-2 self-stretch">
                        <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('public.total_fund') }}
                        </div>
                        <div class="mb-1 flex h-2.5 w-full overflow-hidden rounded-full bg-gray-300 dark:bg-gray-400 text-xs">
                            <div
                                :style="{ width: `${masterAccount.totalFundWidth}%` }"
                                class="rounded-full bg-gradient-to-r from-primary-300 to-primary-600 dark:from-primary-500 dark:to-primary-800 transition-all duration-500 ease-out"
                            >
                            </div>
                        </div>
                        <div class="mb-2 flex items-center justify-between text-xs">
                            <div class="dark:text-gray-400">
                                $ 1
                            </div>
                            <div class="dark:text-gray-400">
                                $ {{ formatAmount(masterAccount.total_fund/2) }}
                            </div>
                            <div class="dark:text-gray-400">$ {{ masterAccount.total_fund }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div
                v-if="masterAccount.type === 'Standard'"
                class="space-y-2 mb-4"
            >
                <Label
                    for="leverage"
                    :value="$t('public.account_number')"
                />
                <div v-if="tradingAccountsSel">
                    <BaseListbox
                        :options="tradingAccountsSel"
                        v-model="form.meta_login"
                        :error="!!form.errors.meta_login"
                        :placeholder="$t('public.placeholder')"
                    />
                </div>
                <div v-else>
                    <Input
                        id="loading"
                        type="text"
                        class="block w-full"
                        v-model="loading"
                        readonly
                    />
                </div>
                <InputError :message="form.errors.meta_login" />
            </div>
            <div class="w-full space-y-2">
                <Label
                    for="amount"
                    :value="$t('public.subscription_package')"
                />
                <RadioGroup v-model="depositAmount">
                    <RadioGroupLabel class="sr-only">{{ $t('public.subscription_package') }}</RadioGroupLabel>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-start">
                        <RadioGroupOption
                            as="template"
                            v-for="(amountSel, index) in subscriptionPackages"
                            :key="index"
                            :value="amountSel"
                            v-slot="{ active, checked }"
                        >
                            <div
                                :class="[
                                        active
                                            ? 'ring-0 ring-white ring-offset-0'
                                            : '',
                                        checked ? 'border-primary-600 dark:border-white bg-primary-500 dark:bg-gray-600 text-white' : 'border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-primary-800 dark:text-white',
                                ]"
                                class="relative flex self-stretch cursor-pointer rounded-xl border p-3 focus:outline-none"
                            >
                                <div class="flex items-start w-full">
                                    <div class="text-sm flex flex-col gap-3 w-full">
                                        <RadioGroupLabel
                                            as="div"
                                            class="font-medium"
                                        >
                                            <div class="text-lg font-semibold text-center">
                                                $ {{ formatAmount(amountSel.amount) }}
                                            </div>
                                        </RadioGroupLabel>
                                    </div>
                                </div>
                            </div>
                        </RadioGroupOption>
                    </div>
                    <InputError :message="form.errors.amount" class="mt-2" />
                </RadioGroup>
            </div>
            <div
                v-if="depositAmount"
                class="w-full space-y-2"
            >
                <Label
                    for="amount"
                    :value="$t('public.select_product')"
                />
                <RadioGroup v-model="form.package_product">
                    <RadioGroupLabel class="sr-only">{{ $t('public.subscription_package') }}</RadioGroupLabel>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-start">
                        <RadioGroupOption
                            as="template"
                            v-for="(product, index) in depositAmount.label"
                            :key="index"
                            :value="product"
                            v-slot="{ active, checked }"
                        >
                            <div
                                :class="[
                                        active
                                            ? 'ring-0 ring-white ring-offset-0'
                                            : '',
                                        checked ? 'border-primary-600 dark:border-white bg-primary-500 dark:bg-gray-600 text-white' : 'border-gray-300 text-gray-600 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white',
                                ]"
                                class="relative flex self-stretch cursor-pointer rounded-xl border p-3 focus:outline-none"
                            >
                                <div class="flex items-start w-full">
                                    <div class="text-sm flex flex-col gap-3 w-full">
                                        <RadioGroupLabel
                                            as="div"
                                            class="font-medium"
                                        >
                                            <div class="text-center">
                                                {{ product }}
                                            </div>
                                        </RadioGroupLabel>
                                    </div>
                                </div>
                            </div>
                        </RadioGroupOption>
                    </div>
                    <InputError :message="form.errors.package_product" class="mt-2" />
                </RadioGroup>
            </div>
            <div
                v-if="masterAccount.delivery_requirement && depositAmount"
                class="w-full space-y-2"
            >
                <Label
                    for="delivery_address"
                    :value="$t('public.delivery_address')"
                />
                <Input
                    id="delivery_address"
                    type="text"
                    class="block w-full"
                    v-model="form.delivery_address"
                    :invalid="form.errors.delivery_address"
                />
                <InputError :message="form.errors.delivery_address" />
            </div>

            <Modal :show="confirmModal" :title="$t('public.confirm_submit')" @close="cancelSubmit">
                <div class="p-5 bg-gray-100 dark:bg-gray-600 rounded-lg">
                    <div class="flex flex-col items-start gap-3 self-stretch">
                        <div>
                            <div v-if="currentLocale === 'en'" class="text-xl dark:text-white">
                                {{ masterAccount.trading_user.name }}
                            </div>
                            <div v-if="currentLocale === 'cn'" class="text-xl dark:text-white">
                                {{ masterAccount.trading_user.company ? masterAccount.trading_user.company : masterAccount.trading_user.name }}
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-2 self-stretch">
                            <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                                {{ $t('public.account_number') }}
                            </div>
                            <div class="text-base text-gray-800 dark:text-white font-semibold">
                                {{ masterAccount.meta_login }}
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-2 self-stretch">
                            <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                                {{ $t('public.amount_return_cash_wallet') }}
                            </div>
                            <div class="text-base text-gray-800 dark:text-white font-semibold">
                                $ {{ amountReturned }}
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-2 self-stretch">
                            <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                                {{ $t('public.amount_to_follow_master') }}
                            </div>
                            <div class="text-base text-gray-800 dark:text-white font-semibold">
                                $ {{ amount }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="pt-5 grid grid-cols-2 gap-4 w-full md:w-1/3 md:float-right">
                    <Button variant="transparent" type="button" class="justify-center" @click.prevent="cancelSubmit">
                        {{$t('public.cancel')}}
                    </Button>
                    <Button class="justify-center" @click="confirmSubmit" :disabled="form.processing">{{$t('public.confirm')}}</Button>
                </div>
            </Modal>

            <div class="flex items-center">
                <div class="flex items-center h-5">
                    <Checkbox id="terms" v-model="form.terms"/>
                </div>
                <div class="ml-3">
                    <label for="terms" class="flex gap-1 text-gray-500 dark:text-gray-400 text-xs">
                        {{ $t('public.agreement') }}
                        <div
                            class="text-xs underline hover:cursor-pointer text-primary-500 hover:text-gray-700 dark:text-primary-600 dark:hover:text-primary-400"
                            @click="openTermsModal"
                        >
                            {{ $t('public.terms_and_conditions') }}
                        </div>
                    </label>
                </div>
            </div>
            <InputError :message="form.errors.terms" />

            <div class="pt-5 grid grid-cols-2 gap-4 w-full md:w-1/3 md:float-right">
                <Button variant="transparent" type="button" class="justify-center" @click.prevent="closeModal">
                    {{$t('public.cancel')}}
                </Button>
                <Button type="button" class="justify-center" @click="submit" :disabled="form.processing">{{$t('public.confirm')}}</Button>
            </div>
        </form>
    </Modal>

    <Modal :show="termsModal" :title="$t('public.terms_and_conditions')" @close="closeTermsModal">
        <div v-html="terms.contents" class="prose dark:text-white"></div>
        <div class="pt-4">
            <div class="text-gray-600 dark:text-gray-400">
                {{ $t('public.management_fee') }}
            </div>
            <div
                v-for="management_fee in masterAccount.master_management_fee"
                class="text-sm font-semibold dark:text-white"
            >
                {{ management_fee.penalty_days }} {{ $t('public.days') }} - {{ formatAmount(management_fee.penalty_percentage, 0) }} %
            </div>
        </div>
    </Modal>
</template>
