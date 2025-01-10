<script setup>
import Button from "primevue/button";
import {ref, watch} from "vue";
import {useForm} from "@inertiajs/vue3";
import {transactionFormat} from "@/Composables/index.js";
import InputError from "@/Components/InputError.vue";
import InputNumber from "primevue/inputnumber";
import Checkbox from "primevue/checkbox";
import dayjs from "dayjs";
import {useLangObserver} from "@/Composables/localeObserver.js";
import Select from "primevue/select";
import InputLabel from "@/Components/Label.vue";
import TermsAndCondition from "@/Components/TermsAndCondition.vue";
import {MinusIcon, PlusIcon} from "@heroicons/vue/outline";

const props = defineProps({
    strategyType: String,
    subscriber: Object,
    walletSel: Array
})

const {formatAmount} = transactionFormat();
const {locale} = useLangObserver();
const top_up_amount = ref(null);
const eWalletAmount = ref(null);
const cashWalletAmount = ref(null);
const maxEWalletAmount = ref(null);
const minEWalletAmount = ref(null);
const selectedWallet = ref(props.walletSel[0]);
const terms = ref();
const emit = defineEmits(['update:visible']);

const form = useForm({
    wallet_id: selectedWallet.value.value,
    pamm_id: props.subscriber.id,
    top_up_amount: 0,
    eWalletAmount: 0,
    cashWalletAmount: 0,
    maxEWalletAmount: 0,
    minEWalletAmount: 0,
    terms: ''
})

const getTerms = async () => {
    try {
        const response = await axios.get(`/${props.strategyType}_strategy/getTerms?type=pamm_esg`);
        terms.value = response.data;

    } catch (error) {
        console.error('Error fetching trading accounts data:', error);
    }
};

getTerms();

watch(selectedWallet, (newWallet) => {
    top_up_amount.value = null
    form.wallet_id = newWallet.value;

    if (selectedWallet.value.type === 'e_wallet') {
        maxEWalletAmount.value = selectedWallet.value.balance;
    } else {
        eWalletAmount.value = null;
        minEWalletAmount.value = null;
        maxEWalletAmount.value = null;
    }
})

watch(top_up_amount, (newTopupAmount) => {
    const percentage = 20 / 100;

    console.log(selectedWallet.value);
    if (selectedWallet.value.type === 'e_wallet') {
        eWalletAmount.value = newTopupAmount * percentage;
        cashWalletAmount.value = newTopupAmount - eWalletAmount.value;
        maxEWalletAmount.value = eWalletAmount.value;
        minEWalletAmount.value = maxEWalletAmount.value * 0.05;
    } else {
        cashWalletAmount.value = newTopupAmount;
    }
})

watch(eWalletAmount, (newEWalletAmount) => {
    if (newEWalletAmount >= minEWalletAmount.value && newEWalletAmount <= maxEWalletAmount.value) {
        cashWalletAmount.value = top_up_amount.value - newEWalletAmount;
    }
});

const submitForm = () => {
    form.top_up_amount = top_up_amount.value;
    form.eWalletAmount = eWalletAmount.value ?? 0;
    form.cashWalletAmount = cashWalletAmount.value ?? 0;
    form.maxEWalletAmount = maxEWalletAmount.value ?? 0;
    form.minEWalletAmount = minEWalletAmount.value ?? 0;
    form.post(route('pamm.topUpPamm'), {
        onSuccess: () => {
            closeDialog();
            form.reset();
        },
    });
}

const closeDialog = () => {
    emit("update:visible", false);
}
</script>

<template>
    <div class="flex flex-col items-center self-stretch gap-5 md:gap-8">
        <div
            class="py-5 px-6 flex flex-col items-center gap-4 bg-gray-50 dark:bg-gray-950 divide-y dark:divide-gray-700 self-stretch">
            <div class="w-full flex items-center gap-4">
                <div class="flex flex-col items-start self-stretch">
                    <div class="self-stretch truncate w-[190px] md:w-64 text-gray-950 dark:text-white font-bold">
                        <div v-if="locale === 'cn'">
                            {{
                                subscriber.master.trading_user.company ? subscriber.master.trading_user.company : subscriber.master.trading_user.name
                            }}
                        </div>
                        <div v-else>
                            {{ subscriber.master.trading_user.name }}
                        </div>
                    </div>
                    <div class="self-stretch truncate w-24 text-gray-500 text-sm">
                        {{ subscriber.master.meta_login }}
                    </div>
                </div>
            </div>
            <div class="flex flex-col items-start gap-3 w-full pt-4 self-stretch">
                <div class="flex flex-col gap-1 items-center self-stretch">
                    <div class="flex py-1 gap-3 items-start self-stretch">
                        <span class="w-full text-gray-500 font-medium text-xs">{{ $t('public.account_no') }}</span>
                        <span class="w-full text-gray-950 dark:text-white font-medium text-sm">{{
                                subscriber.meta_login
                            }}</span>
                    </div>
                    <div class="flex py-1 gap-3 items-start self-stretch">
                        <span class="w-full text-gray-500 font-medium text-xs">{{
                                $t('public.subscription_number')
                            }}</span>
                        <span class="w-full text-gray-950 dark:text-white font-medium text-sm">{{
                                subscriber.subscription_number
                            }}</span>
                    </div>
                    <div class="flex py-1 gap-3 items-start self-stretch">
                        <span class="w-full text-gray-500 font-medium text-xs">{{
                                $t('public.roi_date')
                            }} ({{ subscriber.settlement_period }} {{ $t('public.days') }} )</span>
                        <span class="w-full text-gray-950 dark:text-white font-medium text-sm">{{
                                dayjs(subscriber.settlement_date).format('YYYY/MM/DD')
                            }}</span>
                    </div>
                    <div class="flex py-1 gap-3 items-start self-stretch">
                        <span class="w-full text-gray-500 font-medium text-xs">{{
                                $t('public.investment_amount')
                            }}</span>
                        <span class="w-full text-gray-950 dark:text-white font-medium text-sm">$ {{
                                formatAmount(subscriber.total_amount)
                            }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex flex-col items-center gap-3 self-stretch">
            <!-- Select Wallet -->
            <div class="flex flex-col items-start gap-1 self-stretch">
                <InputLabel
                    for="wallet_id"
                    :value="$t('public.wallet')"
                />
                <Select
                    input-id="wallet_id"
                    v-model="selectedWallet"
                    :options="walletSel"
                    optionLabel="label"
                    :placeholder="$t('public.select_wallet')"
                    class="w-full"
                    :invalid="!!form.errors.wallet_id"
                    :disabled="!walletSel.length"
                />
                <InputError :message="form.errors.wallet_id"/>
            </div>

            <div class="flex flex-col items-start gap-1 self-stretch">
                <InputLabel for="amount" :value="$t('public.amount')"/>
                <InputNumber
                    v-model="top_up_amount"
                    class="w-full"
                    inputId="horizontal-buttons"
                    showButtons
                    buttonLayout="horizontal"
                    :min="subscriber.type === 'ESG' ? 1000 : 10"
                    :step="subscriber.type === 'ESG' ? 1000 : 10"
                    mode="currency"
                    currency="USD"
                    fluid
                    :invalid="!!form.errors.top_up_amount"
                >
                    <template #incrementbuttonicon>
                        <PlusIcon class="w-5 h-5"/>
                    </template>
                    <template #decrementbuttonicon>
                        <MinusIcon class="w-5 h-5"/>
                    </template>
                </InputNumber>
                <div v-if="subscriber.type === 'ESG'" class="text-sm text-gray-500">
                    {{ $t('public.purchase_product_desc') }}
                    <span class="font-semibold text-primary-500">{{ top_up_amount / 1000 ?? 0 }}</span>
                </div>
                <InputError :message="form.errors.top_up_amount"/>
            </div>

            <div
                v-if="selectedWallet.value === (walletSel.length > 1 ? walletSel.find(wallet => wallet.type === 'e_wallet')?.value : null)"
                class="grid grid-cols-1 md:grid-cols-2 gap-5 self-stretch"
            >
                <div class="flex flex-col items-start gap-1 self-stretch">
                    <InputLabel for="amount" :value="walletSel.find(wallet => wallet.type === 'e_wallet')?.name"/>
                    <InputNumber
                        v-model="eWalletAmount"
                        inputId="currency-us"
                        mode="currency"
                        currency="USD"
                        class="w-full"
                        :min="minEWalletAmount"
                        :max="maxEWalletAmount"
                        placeholder="$0.00"
                        fluid
                        showButtons
                        :invalid="!!form.errors.eWalletAmount"
                        :disabled="form.processing"
                    />
                    <div class="self-stretch text-gray-500 text-xs">{{ $t('public.max') }}:
                        <span class="font-semibold text-sm dark:text-white">${{
                                formatAmount(maxEWalletAmount ?? 0)
                            }}</span>
                    </div>
                    <InputError :message="form.errors.eWalletAmount"/>
                </div>
                <div class="flex flex-col items-start gap-1 self-stretch">
                    <InputLabel for="amount" :value="$t('public.cash_wallet')"/>
                    <InputNumber
                        v-model="cashWalletAmount"
                        inputId="currency-us"
                        mode="currency"
                        currency="USD"
                        class="w-full"
                        placeholder="$0.00"
                        fluid
                        disabled
                    />
                </div>
            </div>

            <!-- t&c -->
            <div class="flex flex-col gap-1 items-start self-stretch">
                <div class="flex items-start gap-2 self-stretch w-full">
                    <Checkbox
                        v-model="form.terms"
                        inputId="terms"
                        binary
                        :invalid="!!form.errors.terms"
                    />
                    <label for="terms" class="text-gray-600 dark:text-gray-400 text-xs">
                        {{ $t('public.agreement') }}
                        <TermsAndCondition
                            :termsLabel="$t('public.terms_and_conditions')"
                            :terms="terms"
                        />
                    </label>
                </div>
                <InputError :message="form.errors.terms"/>
            </div>
        </div>

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
</template>
