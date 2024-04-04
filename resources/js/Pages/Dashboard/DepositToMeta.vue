<script setup>
import Button from "@/Components/Button.vue";
import {CreditCardUpIcon} from "@/Components/Icons/outline";
import {onMounted, ref, watch} from "vue";
import Modal from "@/Components/Modal.vue";
import Label from "@/Components/Label.vue";
import Input from "@/Components/Input.vue";
import BaseListbox from "@/Components/BaseListbox.vue";
import InputError from "@/Components/InputError.vue";
import {useForm} from "@inertiajs/vue3";
import {transactionFormat} from "@/Composables/index.js";

const props = defineProps({
    wallet: Object,
    walletSel: Array,
})

const internalTransferModal = ref(false);
const tradingAccountsSel = ref();
const loading = ref('Loading..');
const cashWallet = ref('Cash Wallet');
const selectedAccount = ref();
const { formatAmount } = transactionFormat();

const form = useForm({
    wallet_id: props.wallet.id,
    amount: '',
    to_meta_login: '',
    eWalletAmount: '',
    cashWalletAmount: '',
    maxEWalletAmount: '',
    minEWalletAmount: '',
})

const openInternalTransferModal = () => {
    internalTransferModal.value = true;
}

const getTradingAccounts = async () => {
    try {
        const response = await axios.get('/account_info/getTradingAccounts');
        tradingAccountsSel.value = response.data;
        selectedAccount.value = tradingAccountsSel.value.length > 0 ? tradingAccountsSel.value[0].value : null;
    } catch (error) {
        console.error('Error refreshing trading accounts data:', error);
    }
};

onMounted(() => {
    getTradingAccounts();
});

const submit = () => {
    form.to_meta_login = selectedAccount.value;
    form.amount = parseFloat(depositAmount.value);
    form.eWalletAmount = parseFloat(eWalletAmount.value);
    form.cashWalletAmount = parseFloat(cashWalletAmount.value);
    form.maxEWalletAmount = parseFloat(maxEWalletAmount.value);
    form.minEWalletAmount = parseFloat(minEWalletAmount.value);
    form.post(route('account_info.depositTradingAccount'), {
        onSuccess: () => {
            closeModal();
            form.reset();
            getTradingAccounts();
        },
    });
}

const closeModal = () => {
    internalTransferModal.value = false;
}

const depositAmount = ref();
const eWalletAmount = ref();
const cashWalletAmount = ref();
const maxEWalletAmount = ref();
const minEWalletAmount = ref();

const selectedPercentage = ref(20); // Default to 20%

watch(depositAmount, (newDepositAmount) => {
    const percentage = selectedPercentage.value / 100; // Convert percentage to decimal
    eWalletAmount.value = (newDepositAmount * percentage).toString();
    cashWalletAmount.value = newDepositAmount - eWalletAmount.value;
    maxEWalletAmount.value = eWalletAmount.value;
    minEWalletAmount.value = maxEWalletAmount.value * 0.05;
})

watch(eWalletAmount, (newEWalletAmount) => {
    // Check if newEWalletAmount is within the range
    if (newEWalletAmount >= minEWalletAmount.value && newEWalletAmount <= maxEWalletAmount.value) {
        cashWalletAmount.value = depositAmount.value - newEWalletAmount;
    }
});
</script>

<template>
    <Button
        type="button"
        size="sm"
        variant="gray"
        class="w-full flex justify-center gap-1"
        v-slot="{ iconSizeClasses }"
        @click="openInternalTransferModal"
    >
        <CreditCardUpIcon aria-hidden="true" :class="iconSizeClasses" />
        {{ $t('public.deposit') + ' ' + $t('public.to_account') }}
    </Button>

    <Modal :show="internalTransferModal" :title="$t('public.deposit') + ' ' + $t('public.to_account')" @close="closeModal">
        <form class="space-y-2">
            <div class="flex flex-col sm:flex-row gap-4">
                <Label class="text-sm dark:text-white w-full md:w-1/4 pt-0.5" for="trading_account" :value="$t('public.account_number')" />
                <div class="flex flex-col w-full">
                    <div v-if="tradingAccountsSel">
                        <BaseListbox
                            :options="tradingAccountsSel"
                            v-model="selectedAccount"
                            :error="!!form.errors.to_meta_login"
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
                    <InputError :message="form.errors.to_meta_login" class="mt-2" />
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 pt-2">
                <Label class="text-sm dark:text-white w-full md:w-1/4" for="amount" :value="$t('public.amount')  + ' ($)'" />
                <div class="flex flex-col w-full">
                    <Input
                        id="amount"
                        type="number"
                        min="0"
                        step="100"
                        :placeholder="$t('public.deposit_placeholder')"
                        class="block w-full"
                        v-model="depositAmount"
                        :invalid="form.errors.amount"
                    />
                    <InputError :message="form.errors.amount" class="mt-2" />
                </div>
            </div>

            <div class="border-t boarder-gray-300 pt-5">
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('public.'+ wallet.type) }} ({{ $t('public.max') }}: {{ selectedPercentage }}%)
                    </div>
                    <div class="flex items-center gap-2">
                        <Input
                            id="eWalletAmount"
                            type="number"
                            :min="minEWalletAmount"
                            :max="maxEWalletAmount"
                            class="block w-24"
                            v-model="eWalletAmount"
                            :disabled="form.processing || !depositAmount"
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

            <div class="pt-5 grid grid-cols-2 gap-4 w-full md:w-1/3 md:float-right">
                <Button variant="transparent" type="button" class="justify-center" @click.prevent="closeModal">
                    {{$t('public.cancel')}}
                </Button>
                <Button class="justify-center" @click="submit" :disabled="form.processing">{{$t('public.confirm')}}</Button>
            </div>
        </form>
    </Modal>
</template>
