<script setup>
import {onMounted, onUnmounted, ref, watchEffect} from "vue";
import Skeleton from 'primevue/skeleton';
import Badge from "@/Components/Badge.vue";
import Loading from "@/Components/Loading.vue";
import Action from "@/Pages/Account/Partials/Action.vue";
import {transactionFormat} from "@/Composables/index.js";
import {usePage} from "@inertiajs/vue3";
import BalanceIn from "@/Pages/Account/Partials/BalanceIn.vue";

const props = defineProps({
    activeAccountCounts: Number,
    walletSel: Array,
    leverageSel: Array,
})

const activeAccounts = ref([]);
const isLoading = ref(false);
const countdown = ref(10);
const { formatAmount } = transactionFormat();
const emit = defineEmits(['update:totalEquity', 'update:totalBalance']);
const currentLocale = ref(usePage().props.locale);

const getResults = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get('/account_info/getTradingAccountsData');
        activeAccounts.value = response.data.tradingAccounts;
        emit("update:totalEquity", response.data.totalEquity);
        emit("update:totalBalance", response.data.totalBalance);
    } catch (error) {
        console.error('Error refreshing trading accounts data:', error);
    } finally {
        isLoading.value = false;
    }
}

onMounted(() => {
    // Initial data fetch when the component is mounted
    getResults();

    // Schedule periodic refresh every 10 seconds
    const intervalId = setInterval(() => {
        getResults();
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
        getResults();
    }
});
</script>

<template>
    <div
        v-if="activeAccountCounts === 0 && !activeAccounts.length"
        class="flex flex-col justify-center items-center w-full min-h-52"
    >
        <div class="text-2xl text-gray-400 dark:text-gray-200">
            {{ $t('public.no_account') }}
        </div>
        <div class="text-lg text-gray-400 dark:text-gray-600">
            {{ $t('public.no_account_message') }}
        </div>
    </div>

    <div
        v-else
        :class="{
            'grid md:grid-cols-2': isLoading && activeAccounts.length === 0
        }"
    >
        <div
            v-if="isLoading && activeAccounts.length === 0"
            class="flex flex-col items-start gap-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg p-5 w-full shadow-lg"
        >
            <div class="flex justify-between items-center self-stretch">
                <div class="flex items-center gap-3">
                    <div class="flex items-center">
                        <div class="flex flex-col gap-2 w-full">
                            <Skeleton height="2rem" width="15rem"></Skeleton>
                            <Skeleton width="5rem" class="mb-2"></Skeleton>
                        </div>
                    </div>
                    <span class="sr-only">{{ $t('public.is_loading') }}</span>
                </div>
            </div>
            <div class="flex justify-between items-center self-stretch">
                <div class="flex items-center gap-3 w-full">
                    <div class="grid grid-cols-2 gap-2 w-full text-xs">
                        <div class="flex items-center gap-1 self-stretch">
                            <span class="text-gray-600 dark:text-gray-400">{{ $t('public.balance') }}: </span>
                            <Skeleton width="5rem"></Skeleton>
                        </div>
                        <div class="flex items-center gap-1 self-stretch">
                            <span class="text-gray-600 dark:text-gray-400">{{ $t('public.equity') }}: </span>
                            <Skeleton width="5rem"></Skeleton>
                        </div>
                        <div class="flex items-center gap-1 self-stretch">
                            <span class="text-gray-600 dark:text-gray-400">{{ $t('public.credit') }}: </span>
                            <Skeleton width="5rem"></Skeleton>
                        </div>
                        <div class="flex items-center gap-1 self-stretch">
                            <span class="text-gray-600 dark:text-gray-400">{{ $t('public.leverage') }}: </span>
                            <Skeleton width="5rem"></Skeleton>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div
            v-else-if="!activeAccounts.length"
            class="flex flex-col justify-center items-center w-full min-h-52"
        >
            <div class="text-2xl text-gray-600 dark:text-gray-200">
                {{ $t('public.no_account') }}
            </div>
            <div class="text-lg text-center text-gray-400 dark:text-gray-600">
                {{ $t('public.no_account_message') }}
            </div>
        </div>

        <div
            v-else
            class="grid grid-cols-1 xl:grid-cols-2 gap-5"
        >
            <div
                v-for="account in activeAccounts"
                class="flex flex-col items-start gap-5 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg p-5 w-full shadow-lg"
            >
                <div class="flex justify-between items-start self-stretch">
                    <div class="flex items-start gap-3">
                        <div class="flex flex-col gap-2 items-start">
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
                            :activeAccountCounts="activeAccountCounts"
                            :account="account"
                            :walletSel="walletSel"
                            :leverageSel="leverageSel"
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
                        <BalanceIn
                            :account="account"
                            :walletSel="walletSel"
                        />
                    </div>
                    <div class="flex items-center gap-2 justify-end w-full">
                        <Loading class="w-5 h-5" />
                        <div class="text-xs">{{ $t('public.refreshing_in') }}{{ countdown }}s</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
