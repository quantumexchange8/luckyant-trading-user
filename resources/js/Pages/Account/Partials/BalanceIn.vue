<script setup>
import {CreditCardAddIcon} from "@/Components/Icons/outline.jsx";
import Button from "primevue/button";
import {ref, watch} from "vue";
import Dialog from "primevue/dialog";
import {transactionFormat} from "@/Composables/index.js";
import InputLabel from "@/Components/Label.vue";
import InputNumber from "primevue/inputnumber";
import InputError from "@/Components/InputError.vue";
import Select from "primevue/select";
import {useForm} from "@inertiajs/vue3";
import {IconFileInfo} from "@tabler/icons-vue";

const props = defineProps({
    account: Object,
    alphaDepositMax: Number,
    alphaDepositQuota: Number,
})

const visible = ref(false);
const {formatAmount} = transactionFormat();
const selectedWallet = ref();
const depositAmount = ref(null);
const eWalletAmount = ref(null);
const cashWalletAmount = ref(null);
const maxEWalletAmount = ref(null);
const minEWalletAmount = ref(null);

const form = useForm({
    wallet_id: '',
    amount: null,
    to_meta_login: props.account.meta_login,
    eWalletAmount: null,
    cashWalletAmount: null,
    maxEWalletAmount: null,
    minEWalletAmount: null,
})

const openDialog = () => {
    visible.value = true;
    getDepositWallets();
    getBalanceInAmount();
}

const loadingWallets = ref(false);
const wallets = ref([]);

const getDepositWallets = async () => {
    loadingWallets.value = true;
    try {
        const response = await axios.get(`/getDepositWallets?account_type=${props.account.account_type.slug}`);
        wallets.value = response.data;
        selectedWallet.value = wallets.value[0];
    } catch (error) {
        console.error('Error fetching account types:', error);
    } finally {
        loadingWallets.value = false;
    }
};

const minAmount = ref(null);
const loadingMinAmount = ref(false);

const getBalanceInAmount = async () => {
    loadingMinAmount.value = true;
    try {
        const response = await axios.get(`/getBalanceInAmount?meta_login=${props.account.meta_login}`);
        minAmount.value = response.data;
    } catch (error) {
        console.error('Error fetching account types:', error);
    } finally {
        loadingMinAmount.value = false;
    }
};

const toggleFullAmount = () => {
    if (depositAmount.value) {
        depositAmount.value = null;
    } else {
        let maxDepositAmount = selectedWallet.value.balance;

        // Check if the account type is 3 and apply the condition
        if (props.account.account_type.id === 3) {
            const calculatedMax = props.alphaDepositMax > props.alphaDepositQuota
                ? props.alphaDepositQuota
                : props.alphaDepositMax;

            // Set the maximum deposit amount to the lesser value
            maxDepositAmount = Math.min(selectedWallet.value.balance, calculatedMax);
        }

        depositAmount.value = maxDepositAmount;
    }
};

watch(selectedWallet, (newWallet) => {
    depositAmount.value = null
    form.wallet_id = newWallet.value;

    if (selectedWallet.value.type === 'e_wallet') {
        maxEWalletAmount.value = selectedWallet.value.balance;
    } else {
        eWalletAmount.value = null;
        minEWalletAmount.value = null;
        maxEWalletAmount.value = null;
    }
})

watch(depositAmount, (newDepositAmount) => {
    const percentage = 20 / 100;

    if (selectedWallet.value.type === 'e_wallet') {
        eWalletAmount.value = newDepositAmount * percentage;
        cashWalletAmount.value = newDepositAmount - eWalletAmount.value;
        maxEWalletAmount.value = eWalletAmount.value;
        minEWalletAmount.value = maxEWalletAmount.value * 0.05;
    } else {
        cashWalletAmount.value = newDepositAmount;
    }
})

watch(eWalletAmount, (newEWalletAmount) => {
    if (newEWalletAmount >= minEWalletAmount.value && newEWalletAmount <= maxEWalletAmount.value) {
        cashWalletAmount.value = depositAmount.value - newEWalletAmount;
    }
});

const submitForm = () => {
    form.wallet_id = selectedWallet.value.id
    form.amount = depositAmount.value ?? 0;
    form.eWalletAmount = eWalletAmount.value ?? 0;
    form.cashWalletAmount = cashWalletAmount.value ?? 0;
    form.maxEWalletAmount = maxEWalletAmount.value ?? 0;
    form.minEWalletAmount = minEWalletAmount.value ?? 0;
    form.post(route('account_info.depositBalance'), {
        onSuccess: () => {
            closeDialog();
            form.reset();
            depositAmount.value = null;
            eWalletAmount.value = null;
            cashWalletAmount.value = null;
            maxEWalletAmount.value = null;
            minEWalletAmount.value = null;
        }
    })
}

const closeDialog = () => {
    visible.value = false;
}
</script>

<template>
    <Button
        type="button"
        size="small"
        class="flex justify-center gap-2 w-full md:max-w-40"
        v-slot="{ iconSizeClasses }"
        @click="openDialog"
        v-if="account.balance_in"
    >
        <CreditCardAddIcon :class="iconSizeClasses"/>
        {{ $t('public.balance_in') }}
    </Button>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.balance_in')"
        class="dialog-xs md:dialog-md"
    >
        <form>
            <div class="flex flex-col items-center gap-8 self-stretch md:gap-10">
                <div class="flex flex-col items-center gap-5 self-stretch">
                    <div
                        class="flex flex-col justify-center items-center py-4 px-8 gap-2 self-stretch bg-gray-200 dark:bg-gray-950">
                        <span class="w-full text-gray-500 text-center text-sm font-medium">#{{ account.meta_login }} - {{
                                $t('public.balance')
                            }}</span>
                        <span class="w-full text-gray-950 dark:text-white text-center text-xl font-semibold">$ {{
                                formatAmount(account.balance ?? 0)
                            }}</span>
                    </div>

                    <!-- input fields -->
                    <div class="flex flex-col items-start gap-1 self-stretch">
                        <InputLabel
                            for="wallet_id"
                            :value="$t('public.wallet')"
                        />
                        <Select
                            input-id="wallet_id"
                            v-model="selectedWallet"
                            :options="wallets"
                            :placeholder="$t('public.select_wallet')"
                            class="w-full"
                            :invalid="!!form.errors.wallet_id"
                            :loading="loadingWallets"
                        >
                            <template #value="slotProps">
                                <div v-if="slotProps.value" class="flex items-center">
                                    <div>{{ $t(`public.${slotProps.value.type}`) }} ($ {{ formatAmount(slotProps.value.balance) }})</div>
                                </div>
                                <span v-else>{{ slotProps.placeholder }}</span>
                            </template>
                            <template #option="slotProps">
                                <div class="flex items-center gap-1 truncate">
                                    <span>{{ $t(`public.${slotProps.option.type}`) }} ($ {{ formatAmount(slotProps.option.balance) }})</span>
                                </div>
                            </template>
                        </Select>
                        <InputError :message="form.errors.wallet_id"/>
                    </div>

                    <div class="flex flex-col items-start gap-1 self-stretch">
                        <InputLabel for="amount" :value="$t('public.amount')"/>
                        <div class="relative w-full">
                            <InputNumber
                                v-model="depositAmount"
                                inputId="currency-us"
                                mode="currency"
                                currency="USD"
                                class="w-full"
                                :min="0"
                                :step="100"
                                placeholder="$0.00"
                                fluid
                                :invalid="!!form.errors.amount"
                            />
                            <div
                                class="absolute right-4 top-1/2 transform -translate-y-1/2 cursor-pointer text-sm font-semibold"
                                :class="{
                                    'text-primary-500 hover:text-primary-600': !depositAmount,
                                    'text-error-500 hover:text-error-600': depositAmount,
                                }"
                                @click="toggleFullAmount"
                            >
                                {{ depositAmount ? $t('public.clear') : $t('public.full_amount') }}
                            </div>
                        </div>
                        <div v-if="account.account_type.slug === 'alpha'" class="flex self-stretch gap-2 items-center">
                            <div class="text-gray-500 text-xs">
                                {{ $t('public.min') }}:
                                <span v-if="loadingMinAmount">{{ $t('public.loading') }}</span>
                                <span v-else class="font-semibold text-sm dark:text-white">${{ formatAmount(minAmount) }}</span>
                            </div>
                            <div class="text-gray-500 text-xs">
                                {{ $t('public.max') }}:
                                <span class="font-semibold text-sm dark:text-white">${{
                                        formatAmount(alphaDepositMax > alphaDepositQuota ? alphaDepositQuota : alphaDepositMax)
                                    }}</span>
                            </div>
                        </div>
                        <InputError :message="form.errors.amount"/>
                    </div>

                    <div
                        v-if="account.account_type.slug === 'alpha'"
                        class="flex flex-col mt-3 items-start gap-1 self-stretch"
                    >
                        <span class="text-gray-950 dark:text-white text-sm font-bold">{{ $t('public.alpha_notice') }}</span>
                        <div class="text-xs text-gray-500">
                            {{ $t('public.alpha_deposit_notice') }}
                        </div>
                    </div>

                    <div
                        v-if="selectedWallet && selectedWallet.type === 'e_wallet' && account.account_type.slug !== 'alpha'"
                        class="grid grid-cols-1 md:grid-cols-2 gap-5 self-stretch"
                    >
                        <div class="flex flex-col items-start gap-1 self-stretch">
                            <InputLabel for="amount" :value="$t(`public.${selectedWallet.type}`)"/>
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
                </div>
                <div class="flex w-full justify-end gap-3">
                    <Button
                        type="button"
                        severity="secondary"
                        text
                        class="justify-center w-full md:w-auto px-6"
                        @click="closeDialog"
                        :disabled="!selectedWallet || form.processing"
                    >
                        {{ $t('public.cancel') }}
                    </Button>
                    <Button
                        type="submit"
                        class="justify-center w-full md:w-auto px-6"
                        @click.prevent="submitForm"
                        :disabled="!selectedWallet || form.processing"
                    >
                        {{ $t('public.confirm') }}
                    </Button>
                </div>
            </div>
        </form>
    </Dialog>
</template>
