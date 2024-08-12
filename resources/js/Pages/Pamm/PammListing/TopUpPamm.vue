<script setup>
import Button from "@/Components/Button.vue";
import Modal from "@/Components/Modal.vue";
import {ref, watch} from "vue";
import {useForm, usePage} from "@inertiajs/vue3";
import {transactionFormat} from "@/Composables/index.js";
import Label from "@/Components/Label.vue";
import Input from "@/Components/Input.vue";
import InputError from "@/Components/InputError.vue";
import BaseListbox from "@/Components/BaseListbox.vue";
import {MinusIcon, PlusIcon} from "@heroicons/vue/outline";
import InputNumber from "primevue/inputnumber";
import Checkbox from "@/Components/Checkbox.vue";

const props = defineProps({
    pamm: Object,
    terms: Object,
    walletSel: Object
})

const topUpModal = ref(false);
const currentLocale = ref(usePage().props.locale);
const { formatDateTime, formatAmount } = transactionFormat();

const openTopUpModal = () => {
    topUpModal.value = true;
}

const closeModal = () => {
    topUpModal.value = false;
}

const form = useForm({
    id: '',
    wallet_id: props.walletSel[0].value,
    pamm_id: props.pamm.id,
    top_up_amount: 0,
    eWalletAmount: 0,
    cashWalletAmount: 0,
    maxEWalletAmount: 0,
    minEWalletAmount: 0,
    terms: ''
})

const top_up_amount = ref(null);
const productQuantity = ref();
const eWalletAmount = ref();
const cashWalletAmount = ref();
const maxEWalletAmount = ref();
const minEWalletAmount = ref();
const selectedPercentage = ref(20); // Default to 20%

watch(top_up_amount, (new_top_up_amount) => {
    const percentage = selectedPercentage.value / 100; // Convert percentage to decimal

    eWalletAmount.value = (new_top_up_amount * percentage).toString();
    cashWalletAmount.value = new_top_up_amount - eWalletAmount.value;
    maxEWalletAmount.value = eWalletAmount.value;
    minEWalletAmount.value = maxEWalletAmount.value * 0.05;
    productQuantity.value = new_top_up_amount / 1000;
})

watch(eWalletAmount, (newEWalletAmount) => {
    const parseEWalletAmount = parseFloat(newEWalletAmount); // Convert to number
    // Check if newEWalletAmount is within the range
    if (parseEWalletAmount >= minEWalletAmount.value && parseEWalletAmount <= maxEWalletAmount.value) {
        cashWalletAmount.value = top_up_amount.value - parseEWalletAmount;
    }
});

const submit = () => {
    form.top_up_amount = parseFloat(top_up_amount.value);
    form.eWalletAmount = parseFloat(eWalletAmount.value);
    form.cashWalletAmount = parseFloat(cashWalletAmount.value);
    form.maxEWalletAmount = maxEWalletAmount.value;
    form.minEWalletAmount = parseFloat(minEWalletAmount.value);
    form.post(route('pamm.topUpPamm'), {
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    });
}

const termsModal = ref(false);

const openTermsModal = () => {
    termsModal.value = true
}

const closeTermsModal = () => {
    termsModal.value = false
}
</script>

<template>
    <Button
        type="button"
        variant="primary"
        class="w-full flex justify-center"
        @click="openTopUpModal"
    >
        {{ $t('public.top_up') }}
    </Button>

    <Modal :show="topUpModal" :title="$t('public.top_up')" @close="closeModal">
        <div class="p-5 bg-gray-100 dark:bg-gray-800 rounded-lg">
            <div class="flex flex-col items-start gap-3 self-stretch">
                <div class="text-lg font-semibold dark:text-white">
                    <div v-if="currentLocale === 'en'">
                        {{ pamm.master.trading_user.name }}
                    </div>
                    <div v-if="currentLocale === 'cn'">
                        {{ pamm.master.trading_user.company ? pamm.master.trading_user.company : pamm.master.trading_user.name }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.account_number')}}
                    </div>
                    <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                        {{ pamm.master_meta_login }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.subscription_number')}}
                    </div>
                    <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                        {{ pamm.subscription_number }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.product')}}
                    </div>
                    <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                        {{ pamm.package ? '$ ' + formatAmount(pamm.package.amount, 2) + ' - ' : null }} {{ pamm.subscription_package_product }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.fund')}}
                    </div>
                    <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                        $ {{ formatAmount(pamm.subscription_amount, 0) }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.roi_period')}} ({{ pamm.subscription_period }} {{ $t('public.days') }})
                    </div>
                    <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                        {{ formatDateTime(pamm.expired_date, false) }}
                    </div>
                </div>
            </div>
        </div>
        <form class="space-y-4 my-4">
            <div class="space-y-2">
                <Label
                    class="w-full text-gray-600 dark:text-white"
                    for="wallet"
                    :value="$t('public.wallet')"
                />
                <BaseListbox
                    :options="walletSel"
                    v-model="form.wallet_id"
                    :error="!!form.errors.wallet_id"
                />
            </div>
            <div class="space-y-2 pb-2">
                <Label
                    class="w-full text-gray-600 dark:text-white"
                    for="top_up_amount"
                    :value="$t('public.top_up_amount')"
                />
                <InputNumber
                    v-model="top_up_amount"
                    class="w-full"
                    inputId="horizontal-buttons"
                    showButtons
                    buttonLayout="horizontal"
                    :min="1000"
                    :step="1000"
                    mode="currency"
                    currency="USD"
                    fluid
                >
                    <template #incrementbuttonicon>
                        <PlusIcon class="w-5 h-5"/>
                    </template>
                    <template #decrementbuttonicon>
                        <MinusIcon class="w-5 h-5" />
                    </template>
                </InputNumber>
                <div class="text-sm text-gray-500">
                    {{ (productQuantity ?? 0) + ' ' + $t('public.product') }}
                </div>
                <InputError :message="form.errors.top_up_amount" />
            </div>

            <div class="border-t boarder-gray-300 pt-5" v-if="form.wallet_id === (walletSel.length > 1 ? walletSel[1].value : null)">
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        {{  walletSel[1].name }} ({{ $t('public.max') }}: $ {{ formatAmount(maxEWalletAmount) }})
                    </div>
                    <div class="flex items-center gap-2">
                        <Input
                            id="eWalletAmount"
                            type="number"
                            :min="minEWalletAmount"
                            :max="maxEWalletAmount"
                            class="block w-24"
                            v-model="eWalletAmount"
                            :disabled="form.processing || !top_up_amount"
                            :invalid="form.errors.eWalletAmount"
                        />
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 self-stretch">
                    <InputError :message="form.errors.eWalletAmount" />
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('public.cash_wallet') }}
                    </div>
                    <div class="text-base text-gray-800 dark:text-white font-semibold">
                        $ {{ formatAmount(cashWalletAmount ? cashWalletAmount : 0) }}
                    </div>
                </div>
            </div>

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
                <Button class="justify-center" @click="submit" :disabled="form.processing">{{$t('public.confirm')}}</Button>
            </div>
        </form>
    </Modal>

    <Modal :show="termsModal" :title="$t('public.terms_and_conditions')" @close="closeTermsModal">
        <div v-html="terms.contents" class="prose dark:text-white"></div>
    </Modal>
</template>
