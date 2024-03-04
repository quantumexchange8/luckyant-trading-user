<script setup>
import Button from "@/Components/Button.vue";
import {ref} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import WalletAction from "@/Pages/Dashboard/WalletAction.vue";

const props = defineProps({
    walletSel: Array,
    paymentAccountSel: Array,
    paymentDetails: Object,
    withdrawalFee: Object,
})

const wallets = ref([]);
const { formatAmount } = transactionFormat();

const getWallets = async () => {
    try {
        const response = await axios.get('/getWallets');
        wallets.value = response.data;
    } catch (error) {
        console.error('Error refreshing wallets data:', error);
    }
};

getWallets();

</script>

<template>
    <div
        v-for="wallet in wallets"
        class="w-full bg-white dark:bg-gray-900 rounded-lg border-t-8 px-4 py-5 flex flex-col gap-2 justify-around shadow-md"
        :class="[
            { 'border-primary-600 dark:border-primary-400': wallet.type === 'cash_wallet' },
            { 'border-purple-500 dark:border-purple-400': wallet.type === 'rebate_wallet' },
        ]"
    >
        <div class="text-lg text-gray-600 dark:text-gray-400 font-bold">{{ wallet.name }} ({{wallet.wallet_address }})</div>
        <div class="text-2xl">
            $ {{ formatAmount(wallet.balance) }}
        </div>
        <WalletAction
            :wallet="wallet"
            :walletSel="walletSel"
            :paymentAccountSel="paymentAccountSel"
            :paymentDetails="paymentDetails"
            :withdrawalFee="withdrawalFee"
        />
    </div>
</template>
