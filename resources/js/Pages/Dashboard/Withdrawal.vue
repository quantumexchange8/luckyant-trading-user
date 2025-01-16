<script setup>
import Button from "@/Components/Button.vue";
import {CreditCardDownIcon} from "@/Components/Icons/outline.jsx";
import {computed, ref, watch} from "vue";
import Dialog from "primevue/dialog";
import {transactionFormat} from "@/Composables/index.js";
import InputLabel from "@/Components/Label.vue";
import InputNumber from "primevue/inputnumber"
import InputError from "@/Components/InputError.vue";
import {router, useForm, usePage} from "@inertiajs/vue3";
import Select from "primevue/select";
import VOtpInput from "vue3-otp-input";
import Skeleton from "primevue/skeleton";
import {trans} from "laravel-vue-i18n";
import {useConfirm} from 'primevue/useconfirm';

const props = defineProps({
    withdrawalFee: Object,
    withdrawalFeePercentage: Object,
})

const visible = ref(false);
const {formatAmount} = transactionFormat();
const user = usePage().props.auth.user;
const userPaymentAccounts = user.payment_accounts;

const passwordChangedDuration = () => {
    const currentTime = Date.now();
    const passwordChangedTime = new Date(user.password_changed_at).getTime();
    const timeDifference = currentTime - passwordChangedTime;
    return timeDifference / (1000 * 60 * 60);
};

const openDialog = () => {
    if (user.kyc_approval !== 'Verified') {
        withdrawalConfirmation('verify_kyc');
    } else if (passwordChangedDuration() < 24 && user.kyc_approval === 'Verified') {
        withdrawalConfirmation('password_changed');
    } else if (user.security_pin === null) {
        withdrawalConfirmation('add_security_pin');
    } else if (userPaymentAccounts.length === 0) {
        withdrawalConfirmation('add_payment_account');
    } else {
        visible.value = true;
        getWithdrawalWallets();
        getPaymentAccounts();
    }
}

const wallets = ref([]);
const totalBalance = ref(null);
const loadWallets = ref(false);

const getWithdrawalWallets = async () => {
    try {
        loadWallets.value = true
        const response = await axios.get('/getWithdrawalWallets');
        wallets.value = response.data.wallets;
        totalBalance.value = response.data.total_balance;
    } catch (error) {
        console.error('Error fetching withdrawal wallets data:', error);
    } finally {
        loadWallets.value = false
    }
};

const paymentAccounts = ref([]);
const selectedPaymentAccount = ref();
const loadingPaymentAccounts = ref(false);

const getPaymentAccounts = async () => {
    try {
        loadingPaymentAccounts.value = true
        const response = await axios.get('/getPaymentAccounts');
        paymentAccounts.value = response.data;
        selectedPaymentAccount.value = paymentAccounts.value[0];
    } catch (error) {
        console.error('Error fetching withdrawal wallets data:', error);
    } finally {
        loadingPaymentAccounts.value = false
    }
};

const withdrawAmounts = ref({});

const form = useForm({
    withdraw_wallets: null,
    transaction_charges: '',
    payment_account_id: '',
    amount: '',
    security_pin: '',
});

const transactionFee = ref(props.withdrawalFee.value);
const calculatedBalance = ref(0);
// Compute the total withdrawal amount
const totalWithdrawalBalance = computed(() =>
    Object.values(withdrawAmounts.value).reduce((sum, amount) => sum + (amount || 0), 0)
);

// Watch the total withdrawal balance and update transaction fees and final balance
watch(
    totalWithdrawalBalance,
    (newTotal) => {
        const calculatedMinimumFee = newTotal * (props.withdrawalFeePercentage.value / 100);

        if (calculatedMinimumFee <= props.withdrawalFee.value) {
            transactionFee.value = props.withdrawalFee.value;
            const calculated = newTotal - props.withdrawalFee.value;
            calculatedBalance.value = calculated <= 0 ? 0 : calculated;
        } else {
            transactionFee.value = calculatedMinimumFee;
            calculatedBalance.value = newTotal * ((100 - props.withdrawalFeePercentage.value) / 100);
        }
    }
);

const submitForm = () => {
    form.withdraw_wallets = withdrawAmounts.value;
    form.transaction_charges = transactionFee.value;
    form.payment_account_id = selectedPaymentAccount.value.id;

    form.post(route('transaction.withdrawal'), {
        onSuccess: () => {
            closeDialog();
            form.reset();
            withdrawAmounts.value = {};
        },
    });
}

const closeDialog = () => {
    visible.value = false;
}

// Confirmation Template
const confirm = useConfirm();

const withdrawalConfirmation = (accountType) => {
    const messages = {
        add_payment_account: {
            group: 'headless-primary',
            header: trans('public.add_account'),
            text: trans('public.no_payment_account'),
            suffix: trans('public.add_account_suffix'),
            actionType: 'add_payment_account',
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.add_payment_account'),
            action: () => {
                router.get(route('profile.edit'))
            }
        },
        add_security_pin: {
            group: 'headless-primary',
            header: trans('public.setup_security_pin'),
            text: trans('public.no_security_pin'),
            suffix: trans('public.add_security_pin_suffix'),
            actionType: 'add_security_pin',
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.setup_security_pin'),
            action: () => {
                router.get(route('profile.edit'))
            }
        },
        password_changed: {
            group: 'headless-error',
            header: trans('public.password_changed'),
            text: trans('public.password_change_restriction'),
            actionType: 'password_changed',
            cancelButton: trans('public.close'),
        },
        verify_kyc: {
            group: 'headless-error',
            header: trans('public.verify_kyc'),
            text: trans('public.kyc_not_verified'),
            suffix: trans('public.kyc_not_verified_suffix'),
            actionType: 'verify_kyc',
            cancelButton: trans('public.cancel'),
            acceptButton: trans('public.verify_kyc'),
            action: () => {
                router.get(route('profile.edit'))
            }
        },
    };

    const { group, header, text, dynamicText, suffix, actionType, cancelButton, acceptButton, action } = messages[accountType];

    confirm.require({
        group,
        header,
        actionType,
        message: {
            text,
            dynamicText,
            suffix
        },
        cancelButton,
        acceptButton,
        accept: action
    });
};

const inputClasses = ['rounded-lg w-full py-2.5 bg-white dark:bg-gray-800 placeholder:text-gray-400 dark:placeholder:text-gray-500 text-gray-900 dark:text-gray-50 border border-gray-300 dark:border-gray-800 focus:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500']

</script>

<template>
    <Button
        type="button"
        size="sm"
        variant="danger"
        class="w-full flex justify-center gap-2"
        v-slot="{ iconSizeClasses }"
        @click="openDialog"
    >
        <CreditCardDownIcon aria-hidden="true" :class="iconSizeClasses" />
        {{ $t('public.withdrawal') }}
    </Button>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.withdrawal')"
        class="dialog-xs md:dialog-md"
    >
        <form class="flex flex-col gap-5 self-start">
            <div class="p-6 flex flex-col items-center gap-1 bg-gray-200 dark:bg-gray-800">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $t('public.available_balance_to_withdraw')}}
                </div>
                <Skeleton
                    v-if="loadWallets"
                    width="8rem"
                    height="2rem"
                />
                <div v-else class="text-xl font-bold text-gray-950 dark:text-white">
                    ${{ formatAmount(totalBalance) }}
                </div>
            </div>

            <div
                v-if="loadWallets"
                class="flex gap-5 self-stretch w-full"
            >
                <div
                    v-for="index in 2"
                    class="flex flex-col gap-1 self-start w-full"
                >
                    <InputLabel
                        :value="$t('public.loading')"
                    />
                    <InputNumber
                        class="w-full"
                        :min="0"
                        :step="10"
                        mode="currency"
                        currency="USD"
                        fluid
                        placeholder="$0.00"
                        disabled
                    />
                </div>
            </div>

            <div
                v-else
                class="flex gap-5 self-stretch w-full"
            >
                <div
                    v-for="wallet in wallets"
                    class="flex flex-col gap-1 self-start w-full"
                >
                    <InputLabel
                        :for="`withdrawal_wallet_${wallet.id}`"
                        :value="$t(`public.${wallet.type}`)"
                    />
                    <InputNumber
                        v-model="withdrawAmounts[wallet.id]"
                        class="w-full"
                        :inputId="`withdrawal_wallet_${wallet.id}`"
                        :min="0"
                        :step="10"
                        mode="currency"
                        currency="USD"
                        fluid
                        placeholder="$0.00"
                        :invalid="!!form.errors[`withdraw_wallets.${wallet.id}`]"
                    />
                    <div class="self-stretch text-gray-500 text-xs">
                        {{ $t('public.max') }}:
                        <span class="font-semibold text-sm dark:text-white">${{ formatAmount(wallet.balance ?? 0) }}</span>
                    </div>
                    <InputError :message="form.errors[`withdraw_wallets.${wallet.id}`]"/>
                </div>
            </div>

            <!-- To Payment Account -->
            <div class="flex flex-col gap-1 self-start w-full">
                <InputLabel
                    for="to_payment_account"
                    :value="$t('public.to_account')"
                />
                <Select
                    input-id="to_payment_account"
                    v-model="selectedPaymentAccount"
                    :options="paymentAccounts"
                    :placeholder="$t('public.select_account')"
                    class="w-full"
                    :invalid="!!form.errors.payment_account_id"
                    :loading="loadingPaymentAccounts"
                >
                    <template #value="slotProps">
                        <div v-if="slotProps.value">
                            {{ slotProps.value.payment_platform_name }} ({{ slotProps.value.payment_account_name}})
                        </div>
                        <span v-else>{{ slotProps.placeholder }}</span>
                    </template>
                    <template #option="slotProps">
                        {{ slotProps.option.payment_platform_name }} ({{ slotProps.option.payment_account_name}})
                    </template>
                </Select>
                <InputError :message="form.errors.payment_account_id"/>
            </div>

            <!-- Security Pin -->
            <div class="flex flex-col gap-1 self-start w-full">
                <InputLabel
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

            <div class="flex flex-col gap-3 border-t border-gray-300 dark:border-gray-700 pt-5">
                <!-- Withdraw Amount -->
                <div class="flex flex-col gap-1 self-stretch w-full">
                    <div class="flex items-center justify-between">
                        <span class="text-sm dark:text-gray-400">{{$t('public.withdrawal_amount')}}</span>
                        <span class="text-sm dark:text-white font-bold">${{ formatAmount(totalWithdrawalBalance ?? 0) }}</span>
                    </div>
                    <InputError :message="form.errors.amount" />
                </div>

                <!-- Fee -->
                <div class="flex items-start justify-between">
                    <div class="flex flex-col">
                        <span class="text-sm dark:text-gray-400">{{ $t('public.withdrawal_charges') }} ({{ withdrawalFeePercentage.value }}%)</span>
                        <span class="text-xs text-gray-500 dark:text-white">{{ $t('public.minimum_charges') }}: ${{ formatAmount(withdrawalFee.value) }}</span>
                    </div>
                    <div class="text-sm dark:text-white text-right font-bold">
                        ${{ formatAmount(transactionFee) }}
                    </div>
                </div>

                <!-- Withdraw Amount -->
                <div class="flex items-center justify-between">
                    <span class="text-sm dark:text-gray-400">{{$t('public.receive_amount')}}</span>
                    <span class="text-sm dark:text-white font-bold">${{ calculatedBalance ? formatAmount(calculatedBalance) : '0.00' }}</span>
                </div>
            </div>

            <div class="flex justify-end gap-5 items-center pt-3">
                <Button
                    variant="transparent"
                    type="button"
                    class="justify-center w-full md:w-auto"
                    @click.prevent="closeDialog"
                >
                    {{$t('public.cancel')}}
                </Button>
                <Button
                    class="justify-center w-full md:w-auto"
                    @click="submitForm"
                    :disabled="form.processing"
                >
                    {{$t('public.confirm')}}
                </Button>
            </div>
        </form>
    </Dialog>
</template>
