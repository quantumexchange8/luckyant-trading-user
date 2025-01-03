<script setup>
import {ref, watchEffect} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import WalletAction from "@/Pages/Dashboard/WalletAction.vue";
import {usePage} from "@inertiajs/vue3";

const props = defineProps({
    walletSel: Array,
    eWalletSel: Array,
    withdrawalFee: Object,
    withdrawalFeePercentage: Object,
})

const wallets = ref([]);
const isLoading = ref(false);
const { formatAmount } = transactionFormat();

const getWallets = async () => {
    try {
        isLoading.value = true
        const response = await axios.get('/getWallets');
        wallets.value = response.data;
    } catch (error) {
        console.error('Error refreshing wallets data:', error);
    } finally {
        isLoading.value = false
    }
};

getWallets();

watchEffect(() => {
    if (usePage().props.title !== null || usePage().props.toast !== null) {
        getWallets();
    }
});
</script>

<template>
    <div v-if="isLoading">
        <div
            class="w-full bg-white dark:bg-gray-900 rounded-lg border-t-8 px-4 py-5 flex flex-col gap-2 justify-around shadow-md border-primary-600 dark:border-primary-400"
        >
            <div role="status" class="max-w-sm animate-pulse">
                <div class="h-2.5 bg-gray-200 rounded-full dark:bg-gray-700 w-48 mb-4"></div>
                <div class="h-2 bg-gray-200 rounded-full dark:bg-gray-700 max-w-[360px] mb-2.5"></div>
                <div class="h-2 bg-gray-200 rounded-full dark:bg-gray-700 mb-2.5"></div>
                <div class="h-2 bg-gray-200 rounded-full dark:bg-gray-700 max-w-[330px] mb-2.5"></div>
                <div class="h-2 bg-gray-200 rounded-full dark:bg-gray-700 max-w-[300px] mb-2.5"></div>
                <div class="h-2 bg-gray-200 rounded-full dark:bg-gray-700 max-w-[360px]"></div>
                <span class="sr-only">{{ $t('public.is_loading') }}</span>
            </div>
        </div>
    </div>
    <div
        v-for="wallet in wallets"
        class="w-full bg-white dark:bg-gray-900 rounded-lg border-t-8 px-4 py-5 flex flex-col gap-2 justify-around shadow-md"
        :class="[
            { 'border-primary-600 dark:border-primary-400': wallet.type === 'cash_wallet' },
            { 'border-purple-500 dark:border-purple-400': wallet.type === 'bonus_wallet' },
        ]"
    >
        <div class="text-lg text-gray-600 dark:text-gray-400 font-bold">{{ $t('public.' + wallet.type) }} ({{wallet.wallet_address }})</div>
        <div class="text-xl font-semibold">
            $ {{ formatAmount(wallet.balance) }}
        </div>
        <WalletAction
            :wallet="wallet"
            :walletSel="walletSel"
            :eWalletSel="eWalletSel"
            :withdrawalFee="withdrawalFee"
            :withdrawalFeePercentage="withdrawalFeePercentage"
        />
    </div>
</template>
