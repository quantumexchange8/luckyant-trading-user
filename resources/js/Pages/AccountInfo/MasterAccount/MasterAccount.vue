<script setup>
import Button from "@/Components/Button.vue";
import Badge from "@/Components/Badge.vue";
import {transactionFormat} from "@/Composables/index.js";
import {CreditCardAddIcon} from "@/Components/Icons/outline.jsx";
import {onMounted, onUnmounted, ref, watchEffect} from "vue";
import Loading from "@/Components/Loading.vue";
import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import Action from "@/Pages/AccountInfo/TradingAccount/Action.vue";
import {usePage} from "@inertiajs/vue3";
import MasterRequestHistory from "@/Pages/AccountInfo/MasterAccount/MasterRequestHistory.vue";

const props = defineProps({
    walletSel: Array,
    accountCounts: Number,
    masterAccountLogin: Array
})

const { formatAmount } = transactionFormat();
const masterAccounts = ref([]);
const countdown = ref(10);
const isLoading = ref(false);

const refreshData = async () => {
    try {
        isLoading.value = true;
        const response = await axios.get('/account_info/refreshTradingAccountsData');
        masterAccounts.value = response.data.masterAccounts;
    } catch (error) {
        console.error('Error refreshing trading accounts data:', error);
    } finally {
        isLoading.value = false; // Set isLoading to false after fetching data (whether successful or not)
    }
};

onMounted(() => {
    // Initial data fetch when the component is mounted
    refreshData();

    // Schedule periodic refresh every 10 seconds
    const intervalId = setInterval(() => {
        refreshData();
        countdown.value = 10; // Reset countdown
    }, 10000); // 10 seconds = 10000 milliseconds

    // Update countdown every second
    const countdownIntervalId = setInterval(() => {
        countdown.value -= 1;
    }, 1000); // 1 second = 1000 milliseconds

    // Clear intervals when component is unmounted
    onUnmounted(() => {
        clearInterval(intervalId);
        clearInterval(countdownIntervalId);
    });
});

watchEffect(() => {
    if (usePage().props.title !== null) {
        refreshData();
    }
});

</script>

<template>
    <div
        v-if="isLoading && masterAccounts.length === 0"
        class="flex flex-col items-start gap-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg p-5 animate-pulse sm:w-1/2 mx-auto sm:mx-0 shadow-lg"
    >
        <div class="flex justify-between items-center self-stretch">
            <div class="flex items-center gap-3">
                <div class="flex items-center">
                    <div>
                        <div class="h-2.5 bg-gray-200 rounded-full dark:bg-gray-700 w-32 mb-2"></div>
                        <div class="w-48 h-2 bg-gray-200 rounded-full dark:bg-gray-700"></div>
                    </div>
                </div>
                <span class="sr-only">{{ $t('public.is_loading') }}</span>
            </div>
        </div>
        <div class="flex items-center justify-between w-full">
            <div>
                <div class="h-2.5 bg-gray-300 rounded-full dark:bg-gray-600 w-24 mb-2.5"></div>
                <div class="w-32 h-2 bg-gray-200 rounded-full dark:bg-gray-700"></div>
            </div>
            <div class="h-2.5 bg-gray-300 rounded-full dark:bg-gray-700 w-12"></div>
        </div>
    </div>
    <div
        v-else-if="masterAccounts.length === 0"
        class="flex flex-col items-start gap-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg p-5 sm:w-1/2 mx-auto sm:mx-0 shadow-lg"
    >
        <div class="flex justify-between items-center self-stretch">
            <div class="flex items-center gap-3">
                <div class="flex items-center">
                    <div class="flex flex-col gap-2">
                        <div class="font-semibold">{{ $t('public.no_master_account') }}</div>
                        <div class="w-48 h-2 bg-gray-200 rounded-full dark:bg-gray-700"></div>
                    </div>
                </div>
                <span class="sr-only">{{ $t('public.is_loading') }}</span>
            </div>
        </div>
        <div class="flex items-center justify-between w-full">
            <div>
                <div class="h-2.5 bg-gray-300 rounded-full dark:bg-gray-600 w-24 mb-2.5"></div>
                <div class="w-32 h-2 bg-gray-200 rounded-full dark:bg-gray-700"></div>
            </div>
            <div class="h-2.5 bg-gray-300 rounded-full dark:bg-gray-700 w-12"></div>
        </div>
    </div>
    <div
        v-else
        class="grid grid-cols-1 sm:grid-cols-2 gap-5"
    >
        <div
            v-for="account in masterAccounts"
            class="flex flex-col items-start gap-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg p-5 w-full shadow-lg"
        >
            <div class="flex justify-between items-center self-stretch">
                <div class="flex items-center gap-3">
                    <div class="flex flex-col items-start">
                        <div class="text-sm font-semibold">
                            {{ $t('public.name') }}: {{ account.user.username }}
                        </div>
                        <div class="text-xs">
                            {{ $t('public.account_no') }}: {{ account.meta_login }}
                        </div>
                    </div>
                </div>
                <div class="flex justify-end">
                    <Badge variant="success">{{ $t('public.active') }}</Badge>
                </div>
            </div>
            <div class="flex justify-between items-center self-stretch">
                <div class="flex items-center gap-3">
                    <div class="border-r pr-3 border-gray-400 dark:border-gray-600 text-xs font-normal">
                        {{ $t('public.leverage') }}: 1 : {{ account.trading_account.margin_leverage }}
                    </div>
                    <div class="border-r pr-3 border-gray-400 dark:border-gray-600 text-xs font-normal">
                        {{ $t('public.equity') }}: $ {{ formatAmount(account.equity ? account.equity : 0) }}
                    </div>
                    <div class="text-xs font-normal">
                        {{ $t('public.credit') }}: $ {{ formatAmount(account.trading_account.credit ? account.trading_account.credit : 0) }}
                    </div>
                </div>
                <div class="text-xl">
                    $ {{ formatAmount(account.trading_account.balance ? account.trading_account.balance : 0) }}
                </div>
            </div>
            <div class="flex flex-col sm:flex-row items-center gap-2 sm:gap-10 w-full">
                <div class="flex items-center gap-3 w-full">
                    <Action
                        :account="account.trading_account"
                        :walletSel="walletSel"
                        :accountCounts="accountCounts"
                        :masterAccountLogin="masterAccountLogin"
                    />
                </div>
                <div class="flex items-center gap-2 justify-end w-full">
                    <Loading class="w-5 h-5" />
                    <div class="text-xs">{{ $t('public.refreshing_in') }}{{ countdown }}s</div>
                </div>
            </div>
        </div>
    </div>

    <div class="p-5 my-5 mb-28 bg-white overflow-hidden md:overflow-visible rounded-xl shadow-md dark:bg-gray-900">
        <MasterRequestHistory />
    </div>
</template>
