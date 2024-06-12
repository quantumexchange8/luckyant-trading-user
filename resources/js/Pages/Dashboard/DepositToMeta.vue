<script setup>
import Button from "@/Components/Button.vue";
import {CreditCardUpIcon} from "@/Components/Icons/outline";
import {onMounted, ref, watch} from "vue";
import Modal from "@/Components/Modal.vue";
import Label from "@/Components/Label.vue";
import Input from "@/Components/Input.vue";
import BaseListbox from "@/Components/BaseListbox.vue";
import InputError from "@/Components/InputError.vue";
import {useForm, usePage} from "@inertiajs/vue3";
import {transactionFormat} from "@/Composables/index.js";
import {RadioGroup, RadioGroupLabel, RadioGroupOption} from "@headlessui/vue";

const props = defineProps({
    wallet: Object,
    walletSel: Array,
})

const internalTransferModal = ref(false);
const tradingAccountsSel = ref();
const loading = ref('Loading..');
const selectedAccount = ref();
const { formatAmount } = transactionFormat();
const page = usePage();
const hasPammMasters = ref(page.props.hasPammMasters);

const form = useForm({
    wallet_id: props.wallet.id,
    amount: '',
    to_meta_login: '',
    eWalletAmount: '',
    cashWalletAmount: '',
    maxEWalletAmount: '',
    minEWalletAmount: '',
})

const amountPackages = [
    {
        name: '1000',
        value: '1000',
    },
    {
        name: '3000',
        value: '3000',
    },
    {
        name: '5000',
        value: '5000',
    },
    {
        name: '10000',
        value: '10000',
    },
    {
        name: '30000',
        value: '30000',
    },
]

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
const maxEWalletAmount = ref(0);
const minEWalletAmount = ref(0);

const selectedPercentage = ref(20); // Default to 20%

watch(depositAmount, (newDepositAmount) => {
    let numericDepositAmount;

    if (typeof newDepositAmount === 'object' && newDepositAmount !== null) {
        numericDepositAmount = newDepositAmount.value ?? 0;
    } else if (typeof newDepositAmount === 'string') {
        numericDepositAmount = newDepositAmount;
    } else {
        numericDepositAmount = 0;
    }

    const percentage = selectedPercentage.value / 100; // Convert percentage to decimal

    eWalletAmount.value = (numericDepositAmount * percentage).toString();
    cashWalletAmount.value = numericDepositAmount - eWalletAmount.value;
    maxEWalletAmount.value = eWalletAmount.value;
    minEWalletAmount.value = maxEWalletAmount.value * 0.05;
});

watch(eWalletAmount, (newEWalletAmount) => {
    const parseEWalletAmount = parseFloat(newEWalletAmount); // Convert to number
    // Check if newEWalletAmount is within the range
    if (parseEWalletAmount >= minEWalletAmount.value && parseEWalletAmount <= maxEWalletAmount.value) {
        let numericDepositAmount;

        if (typeof depositAmount.value === 'object' && depositAmount.value !== null) {
            numericDepositAmount = depositAmount.value.value ?? 0;
        } else if (typeof depositAmount.value === 'string') {
            numericDepositAmount = depositAmount.value;
        } else {
            numericDepositAmount = 0;
        }

        cashWalletAmount.value = numericDepositAmount - parseEWalletAmount;
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

            <div class="flex flex-col sm:flex-row gap-4 py-2">
                <Label class="text-sm dark:text-white w-full md:w-1/4" for="amount" :value="$t('public.amount')  + ' ($)'" />
                <div v-if="hasPammMasters" class="w-full">
                    <RadioGroup v-model="depositAmount">
                        <RadioGroupLabel class="sr-only">{{ $t('public.signal_status') }}</RadioGroupLabel>
                        <div class="grid grid-cols-3 gap-3 items-center self-stretch">
                            <RadioGroupOption
                                as="template"
                                v-for="(amountSel, index) in amountPackages"
                                :key="index"
                                :value="amountSel"
                                v-slot="{ active, checked }"
                            >
                                <div
                                    :class="[
                                    active
                                      ? 'ring-0 ring-white ring-offset-0'
                                      : '',
                                    checked ? 'border-primary-600 dark:border-white bg-primary-500 dark:bg-gray-600 text-white' : 'border-gray-300 bg-white dark:bg-gray-700',
                                ]"
                                    class="relative flex cursor-pointer rounded-xl border p-3 focus:outline-none"
                                >
                                    <div class="flex items-center w-full">
                                        <div class="text-sm flex flex-col gap-3 w-full">
                                            <RadioGroupLabel
                                                as="div"
                                                class="font-medium"
                                            >
                                                <div class="flex justify-center items-center gap-3">
                                                    $ {{ formatAmount(amountSel.name) }}
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


                <div v-else class="flex flex-col w-full">
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
                        {{ $t('public.'+ wallet.type) }} ({{ $t('public.max') }}: $ {{ formatAmount(maxEWalletAmount) }})
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
