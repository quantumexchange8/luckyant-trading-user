<script setup>
import BaseListbox from "@/Components/BaseListbox.vue";
import {useForm} from "@inertiajs/vue3";
import Label from "@/Components/Label.vue";
import Input from "@/Components/Input.vue";
import InputError from "@/Components/InputError.vue";
import Button from "@/Components/Button.vue";
import {onMounted, ref, watch} from "vue";
import {transactionFormat} from "@/Composables/index.js";

const props = defineProps({
    account: Object,
    walletSel: Array,
})
const emit = defineEmits(['update:accountActionModal']);
const { formatAmount } = transactionFormat();

const form = useForm({
    wallet_id: props.walletSel[0].value,
    amount: '',
    to_meta_login: props.account.meta_login,
    eWalletAmount: '',
    cashWalletAmount: '',
    maxEWalletAmount: '',
    minEWalletAmount: '',
})

const closeModal = () => {
    emit('update:accountActionModal', false);
}

const submit = () => {
    form.amount = parseFloat(depositAmount.value);
    form.eWalletAmount = parseFloat(eWalletAmount.value);
    form.cashWalletAmount = parseFloat(cashWalletAmount.value);
    form.maxEWalletAmount = parseFloat(maxEWalletAmount.value);
    form.minEWalletAmount = parseFloat(minEWalletAmount.value);
    form.post(route('account_info.depositTradingAccount'), {
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    });
}

const depositAmount = ref();
const eWalletAmount = ref();
const cashWalletAmount = ref();
const maxEWalletAmount = ref(0);
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
    const parseEWalletAmount = parseFloat(newEWalletAmount); // Convert to number
    // Check if newEWalletAmount is within the range
    if (parseEWalletAmount >= minEWalletAmount.value && parseEWalletAmount <= maxEWalletAmount.value) {
        cashWalletAmount.value = depositAmount.value - parseEWalletAmount;
    }
});

</script>

<template>
    <form class="space-y-2">
        <div class="flex flex-col sm:flex-row gap-4">
            <Label class="text-sm dark:text-white w-full md:w-1/4 pt-0.5" for="wallet" :value="$t('public.sidebar.wallet')" />
            <div class="flex flex-col w-full">
                <BaseListbox
                    :options="walletSel"
                    v-model="form.wallet_id"
                    :error="!!form.errors.wallet_id"
                />
            </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-4 py-2">
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
</template>
