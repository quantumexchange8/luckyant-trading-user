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

const props = defineProps({
    walletSel: Array,
    leverageSel: Array,
    accountCounts: Number,
    masterAccountLogin: Array
})

const { formatAmount } = transactionFormat();
const tradingAccounts = ref([]);
const totalEquity = ref();
const totalBalance = ref();
const countdown = ref(10);
const isLoading = ref(false);

const refreshData = async () => {
    try {
        isLoading.value = true; // Set isLoading to true before fetching data
        const response = await axios.get('/account_info/refreshTradingAccountsData');
        tradingAccounts.value = response.data.tradingAccounts;
        totalEquity.value = response.data.totalEquity;
        totalBalance.value = response.data.totalBalance;
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

const currentLocale = ref(usePage().props.locale);
</script>

<template>
    <div
        v-if="isLoading && tradingAccounts.length === 0"
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
        v-else-if="tradingAccounts.length === 0"
        class="flex flex-col items-start gap-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg p-5 sm:w-1/2 mx-auto sm:mx-0 shadow-lg"
    >
        <div class="flex justify-between items-center self-stretch">
            <div class="flex items-center gap-3">
                <div class="flex items-center">
                    <div class="flex flex-col gap-2">
                        <div class="font-semibold">{{ $t('public.no_trading_account') }}</div>
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
        class="grid grid-cols-1 xl:grid-cols-2 gap-5"
    >
        <div
            v-for="account in tradingAccounts"
            class="flex flex-col items-start gap-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg p-5 w-full shadow-lg"
        >
            <div class="flex justify-between items-center self-stretch">
                <div class="flex items-start gap-3">
                    <div class="flex flex-col items-start">
                        <div class="text-xl font-bold">
                            {{ $t('public.account_no') }}: {{ account.meta_login }}
                        </div>
                        <div class="text-xs">
                            <div v-if="currentLocale === 'en'">
                                {{ $t('public.name') }}: {{ account.trading_user.name }}
                            </div>
                            <div v-if="currentLocale === 'cn'">
                                {{ $t('public.name') }}: {{ account.trading_user.company ? account.trading_user.company : account.trading_user.name}}
                            </div>
                        </div>
                    </div>
                    <div class="flex justify-end" v-if="account.trading_user.acc_status === 'Active'">
                        <Badge variant="success" class="text-sm">{{ $t('public.active') }}</Badge>
                    </div>
                </div>
                <div class="flex justify-end" v-if="account.trading_user.acc_status === 'Active'">
                    <Action
                        :account="account"
                        :walletSel="walletSel"
                        :leverageSel="leverageSel"
                        :masterAccountLogin="masterAccountLogin"
                        type="Edit"
                    />
                </div>
            </div>
            <div class="flex justify-between items-center self-stretch">
                <div class="flex items-center gap-3 w-full">
                    <div class="grid grid-cols-2 gap-2 w-full text-xs">
                        <div class="flex items-center gap-1 self-stretch">
                            <span class="text-gray-600 dark:text-gray-400">{{ $t('public.balance') }}: </span>
                            <span class="font-semibold"> $ {{ formatAmount(account.balance ? account.balance : 0) }}</span>
                        </div>
                        <div class="flex items-center gap-1 self-stretch">
                            <span class="text-gray-600 dark:text-gray-400">{{ $t('public.equity') }}: </span>
                            <span class="font-semibold"> $ {{ formatAmount(account.equity ? account.equity : 0) }}</span>
                        </div>
                        <div class="flex items-center gap-1 self-stretch">
                            <span class="text-gray-600 dark:text-gray-400">{{ $t('public.credit') }}: </span>
                            <span class="font-semibold"> $ {{ formatAmount(account.credit ? account.credit : 0) }}</span>
                        </div>
                        <div class="flex items-center gap-1 self-stretch">
                            <span class="text-gray-600 dark:text-gray-400">{{ $t('public.leverage') }}: </span>
                            <span class="font-semibold">1 : {{ account.margin_leverage }}</span>
                        </div>
                        <div class="col-span-2" v-if="account.active_subscriber && account.active_subscriber.status === 'Subscribing' || account.pamm_subscription">
                            <div v-if="account.active_subscriber">
                                <div v-if="currentLocale === 'en'" class="flex items-center gap-1 self-stretch">
                                    <span class="text-gray-600 dark:text-gray-400">{{ $t('public.master') }}: </span>
                                    <span class="font-semibold">{{ account.active_subscriber?.master?.trading_user?.name }}</span>
                                </div>
                                <div v-if="currentLocale === 'cn'" class="flex items-center gap-1 self-stretch">
                                    <span class="text-gray-600 dark:text-gray-400">{{ $t('public.master') }}: </span>
                                    <span class="font-semibold">{{ account.active_subscriber?.master?.trading_user?.company ? account.active_subscriber?.master?.trading_user?.company : account.active_subscriber?.master?.trading_user?.name}}</span>
                                </div>
                            </div>
                            <div v-if="account.pamm_subscription">
                                <div v-if="currentLocale === 'en'" class="flex items-center gap-1 self-stretch">
                                    <span class="text-gray-600 dark:text-gray-400">{{ $t('public.master') }}: </span>
                                    <span class="font-semibold">{{ account.pamm_subscription?.master?.trading_user?.name }}</span>
                                </div>
                                <div v-if="currentLocale === 'cn'" class="flex items-center gap-1 self-stretch">
                                    <span class="text-gray-600 dark:text-gray-400">{{ $t('public.master') }}: </span>
                                    <span class="font-semibold">{{ account.pamm_subscription?.master?.trading_user?.company ? account.pamm_subscription?.master?.trading_user?.company : account.pamm_subscription?.master?.trading_user?.name}}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row items-center gap-2 sm:gap-10 w-full" v-if="account.trading_user.acc_status === 'Active'">
                <div class="flex items-center gap-3 w-full">
                    <Action
                        :account="account"
                        :walletSel="walletSel"
                        :leverageSel="leverageSel"
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
</template>
