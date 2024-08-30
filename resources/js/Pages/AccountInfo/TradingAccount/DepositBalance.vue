<script setup>
import BaseListbox from "@/Components/BaseListbox.vue";
import {useForm, usePage} from "@inertiajs/vue3";
import Label from "@/Components/Label.vue";
import Input from "@/Components/Input.vue";
import InputError from "@/Components/InputError.vue";
import Button from "@/Components/Button.vue";
import {onMounted, ref, watch} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import {RadioGroup, RadioGroupLabel, RadioGroupOption} from "@headlessui/vue";

const props = defineProps({
    account: Object,
    walletSel: Array,
})
const emit = defineEmits(['update:accountActionModal']);
const { formatAmount } = transactionFormat();
const page = usePage();
const hasPammMasters = ref(page.props.hasPammMasters);

const form = useForm({
    wallet_id: props.walletSel[0].value,
    amount: 0,
    to_meta_login: props.account.meta_login,
    type: props.account.account_type,
    eWalletAmount: 0,
    cashWalletAmount: 0,
    maxEWalletAmount: 0,
    minEWalletAmount: 0,
    multiplier: 1
})

const amountPackages = [
    {
        name: '1000',
        value: '1000',
        multiplier: 2
    },
    {
        name: '3000',
        value: '3000',
        multiplier: 2.5
    },
    {
        name: '5000',
        value: '5000',
        multiplier: 3
    },
    {
        name: '10000',
        value: '10000',
        multiplier: 3.5
    },
    {
        name: '30000',
        value: '30000',
        multiplier: 4
    },
]

const closeModal = () => {
    emit('update:accountActionModal', false);
}

const depositAmount = ref(null);
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
})

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

const submit = () => {
    let numericDepositAmount;
    let packageMultiplier;
    if (typeof depositAmount.value === 'object' && depositAmount.value !== null) {
        numericDepositAmount = depositAmount.value.value ?? 0;
        packageMultiplier = depositAmount.value.multiplier ?? 1;
    } else if (typeof depositAmount.value === 'string') {
        numericDepositAmount = depositAmount.value;
    } else {
        numericDepositAmount = 0;
        packageMultiplier = 1;
    }

    form.amount = parseFloat(numericDepositAmount);
    form.eWalletAmount = parseFloat(eWalletAmount.value);
    form.cashWalletAmount = parseFloat(cashWalletAmount.value);
    form.maxEWalletAmount = maxEWalletAmount.value;
    form.minEWalletAmount = parseFloat(minEWalletAmount.value);
    form.multiplier = packageMultiplier;
    form.post(route('account_info.depositTradingAccount'), {
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    });
}
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
                                        checked ? 'border-primary-600 dark:border-white bg-primary-500 dark:bg-gray-600 text-white' : 'border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white',
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
