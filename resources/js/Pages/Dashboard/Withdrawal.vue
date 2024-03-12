<script setup>
import Button from "@/Components/Button.vue";
import {BanIcon, CurrencyDollarIcon} from "@heroicons/vue/outline";
import {ref, computed, watch} from "vue";
import Modal from "@/Components/Modal.vue";
import Label from "@/Components/Label.vue";
import {XIcon} from "@/Components/Icons/outline.jsx";
import BaseListbox from "@/Components/BaseListbox.vue";
import Input from "@/Components/Input.vue";
import InputError from "@/Components/InputError.vue";
import {useForm} from "@inertiajs/vue3";
import {transactionFormat} from "@/Composables/index.js";

const props = defineProps({
    walletSel: Array,
    paymentAccountSel: Array,
    withdrawalFee: Object,
})
const withdrawalModal = ref(false);
const { formatAmount } = transactionFormat();
const withdrawalAmount = ref();
const selectedWallet = ref(props.walletSel[0].value);
const selectedPaymentAccount = ref(null);

const openWithdrawalModal = () => {
    withdrawalModal.value = true;
    if (props.paymentAccountSel.length > 0) {
        selectedPaymentAccount.value = props.paymentAccountSel[0].value;
    }
}

const form = useForm({
    wallet_id: '',
    amount: '',
    wallet_address: '',
    transaction_charges: '',
})

const submit = () => {
    form.wallet_id = selectedWallet.value;
    form.wallet_address = selectedPaymentAccount.value;
    form.amount = withdrawalAmount.value;
    form.transaction_charges = transactionFee.value;

    form.post(route('transaction.withdrawal'), {
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    });
}

const closeModal = () => {
    withdrawalModal.value = false;
}

const transactionFee = ref(props.withdrawalFee.value);
const calculatedBalance = ref();

watch(withdrawalAmount, (newValue) => {
    const calculatedMinimumFee = newValue * (props.withdrawalFee.value / 100);

    if (calculatedMinimumFee <= props.withdrawalFee.value) {
        transactionFee.value = props.withdrawalFee.value;
        const calculated = newValue - props.withdrawalFee.value;
        calculatedBalance.value = calculated <= 0 ? 0 : calculated;
    } else {
        transactionFee.value = newValue * (props.withdrawalFee.value / 100);
        calculatedBalance.value = newValue * ((100 - props.withdrawalFee.value) / 100);
    }
});

const handleButtonClick = () => {
    if (!withdrawalAmount.value) {
        // Find the selected wallet in props.walletSel
        const selectedWalletId = selectedWallet.value;
        const foundWallet = props.walletSel.find(wallet => wallet.value === selectedWalletId);

        if (foundWallet) {
            // Set withdrawal amount to the balance of the selected wallet
            withdrawalAmount.value = foundWallet.balance;
        } else {
            console.error('Selected wallet not found');
        }
    } else {
        // Clear withdrawal amount
        withdrawalAmount.value = '';
    }
}
</script>

<template>
    <Button
        type="button"
        size="sm"
        variant="danger"
        class="w-full flex justify-center gap-1"
        v-slot="{ iconSizeClasses }"
        @click="openWithdrawalModal"
    >
        <CurrencyDollarIcon aria-hidden="true" :class="iconSizeClasses" />
        {{ $t('public.withdrawal') }}
    </Button>

    <Modal :show="withdrawalModal" :title="$t('public.withdrawal')" @close="closeModal">

        <div
            v-if="$page.props.auth.user.kyc_approval !== 'Verified'"
            class="flex flex-col gap-4 items-center justify-center w-full"
        >
            <div class="bg-error-400 rounded-full w-20 h-20">
                <BanIcon class="text-white" />
            </div>
            <div class="flex flex-col items-center">
                <div class="text-xl text-gray-800 font-semibold">
                    {{ $t('public.account_verification_required') }}
                </div>
                <div class="text-gray-500">
                    {{ $t('public.withdrawal_required_verification') }}
                </div>
            </div>
            <div class="flex justify-center w-full">
                <Button
                    external
                    type="button"
                    variant="primary"
                    class="items-center gap-2 max-w-xs"
                    :href="route('profile.edit')"
                >
                    {{ $t('public.verify_account') }}
                </Button>
            </div>
        </div>
        <form
            v-else-if="paymentAccountSel.length > 0"
            class="space-y-2 mt-5"
        >
            <div class="flex flex-col sm:flex-row gap-4">
                <Label class="text-sm dark:text-white w-full md:w-1/4 pt-0.5" for="wallet" :value="$t('public.sidebar.wallet')" />
                <div class="flex flex-col w-full">
                    <BaseListbox
                        :options="walletSel"
                        v-model="selectedWallet"
                        :error="!!form.errors.wallet_id"
                    />
                </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-4 pt-2">
                <Label class="text-sm dark:text-white w-full md:w-1/4" for="amount" :value="$t('public.amount')  + ' ($)'" />
                <div class="relative flex flex-col w-full">
                    <div class="relative">
                        <Input
                            id="amount"
                            type="number"
                            min="0"
                            :placeholder="'$ ' + formatAmount(withdrawalFee.value)"
                            class="block w-full"
                            v-model="withdrawalAmount"
                            :invalid="form.errors.amount"
                        />
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer">
                            <Button
                                type="button"
                                variant="primary-opacity"
                                size="sm"
                                class="flex justify-center"
                                :class="{
                                        'bg-gray-400 hover:bg-gray-500 text-white dark:bg-gray-500 dark:hover:bg-gray-700' : !withdrawalAmount,
                                        'bg-error-600 text-white hover:bg-error-700' : withdrawalAmount
                                    }"
                                @click="handleButtonClick"
                            >
                                {{ !withdrawalAmount ? $t('public.full_amount') : $t('public.clear') }}
                            </Button>
                        </div>
                    </div>
                    <InputError :message="form.errors.amount" class="mt-2" />
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 pt-2 pb-5">
                <Label class="text-sm dark:text-white w-full md:w-1/4 pt-0.5" for="wallet_address" :value="$t('public.to_account')" />
                <div class="flex flex-col w-full">
                    <BaseListbox
                        :options="paymentAccountSel"
                        v-model="selectedPaymentAccount"
                        :error="!!form.errors.wallet_address"
                    />
                </div>
            </div>

            <div class="flex flex-col gap-2 border-t border-gray-300">
                <div class="flex items-start justify-between mt-5">
                    <span class="text-sm dark:text-gray-400 font-Inter">{{ $t('public.withdrawal_charges') }} ({{ withdrawalFee.value }}%)</span>
                    <div class="flex flex-col">
                        <span class="text-sm dark:text-white text-right">$ {{ formatAmount(transactionFee) }}</span>
                        <span class="text-xs text-gray-500 dark:text-white">{{ $t('public.minimum_charges') }}: $ {{ formatAmount(withdrawalFee.value) }}</span>
                    </div>
                </div>
                <div class="flex items-center justify-between mt-2">
                    <span class="text-sm dark:text-gray-400 font-Inter">{{$t('public.withdrawal_amount')}}</span>
                    <span class="text-sm dark:text-white">$&nbsp;{{ calculatedBalance ? formatAmount(calculatedBalance) : '0.00' }}</span>
                </div>
            </div>


            <div class="pt-5 grid grid-cols-2 gap-4 w-full md:w-1/3 md:float-right">
                <Button variant="transparent" type="button" class="justify-center" @click.prevent="closeModal">
                    {{$t('public.cancel')}}
                </Button>
                <Button class="justify-center" @click="submit" :disabled="form.processing">{{$t('public.confirm')}}</Button>
            </div>
        </form>

        <div
            v-else
            class="flex flex-col items-center justify-center"
        >
            <div class="text-2xl text-gray-400 dark:text-gray-200">
                No Payment Account
            </div>
            <div class="text-lg text-gray-400 dark:text-gray-600">
                Click the button below to add new payment account.
            </div>
            <Button
                type="button"
                class="mt-5"
                external
                :href="route('profile.edit', {status:'paymentAccount'})"
            >
                Add Payment Account
            </Button>
        </div>
    </Modal>
</template>
