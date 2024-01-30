<script setup>
import Button from "@/Components/Button.vue";
import Badge from "@/Components/Badge.vue";
import {transactionFormat} from "@/Composables/index.js";
import {CreditCardAddIcon} from "@/Components/Icons/outline.jsx";
import {onMounted, onUnmounted, ref} from "vue";
import {RefreshIcon} from "@heroicons/vue/solid";
import Loading from "@/Components/Loading.vue";

const { formatAmount } = transactionFormat();

const tradingAccounts = ref([]);
const countdown = ref(10);

const refreshData = async () => {
    try {
        const response = await axios.get('/account_info/refreshTradingAccountsData');
        tradingAccounts.value = response.data;
    } catch (error) {
        console.error('Error refreshing trading accounts data:', error);
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
</script>

<template>
    <div
        class="grid grid-cols-3"
    >
        <div
            v-for="account in tradingAccounts"
            class="flex flex-col items-start gap-3 border border-gray-400 dark:border-gray-600 rounded-lg p-5 max-w-md"
        >
            <div class="flex justify-between items-center self-stretch">
                <div class="flex items-center gap-3">
                    <div class="bg-gray-600 rounded-full w-12 h-12 flex items-center justify-center">
                        Logo
                    </div>
                    <div class="flex flex-col items-start">
                        <div class="text-sm">
                            Standard Account
                        </div>
                        <div class="text-xs">
                            {{ account.meta_login }}
                        </div>
                    </div>
                </div>
                <Badge status="success">Active</Badge>
            </div>
            <div class="flex justify-between items-center self-stretch">
                <div class="flex items-center gap-3">
                    <div class="border-r pr-3 border-gray-400 dark:border-gray-600 text-xs">
                        {{ account.margin_leverage }}
                    </div>
                    <div class="text-xs">
                        Credit: $ {{ formatAmount(account.credit ? account.credit : 0) }}
                    </div>
                </div>
                <div class="text-xl">
                    $ {{ formatAmount(account.balance ? account.balance : 0) }}
                </div>
            </div>
            <div class="flex items-center gap-3 w-full">
                <Button
                    type="button"
                    variant="primary"
                    class="flex justify-center gap-2"
                    v-slot="{ iconSizeClasses }"
                >
                    <CreditCardAddIcon />
                    Deposit
                </Button>
                <Button
                    type="button"
                    variant="transparent"
                    class="flex justify-center"
                >
                    More
                </Button>
                <div class="flex items-center gap-2 justify-end w-full">
                    <Loading class="w-5 h-5" />
                    <div class="text-xs">Refreshing in {{ countdown }} seconds</div>
                </div>
            </div>
        </div>

    </div>
</template>
