<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import TransactionHistory from "@/Pages/Transaction/Wallet/Partials/TransactionHistory.vue";
import Button from "@/Components/Button.vue";
import { ref } from 'vue'
import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue'
import Deposit from "@/Pages/Dashboard/Deposit.vue";
import {RefreshIcon} from "@/Components/Icons/outline.jsx";
import Withdrawal from "@/Pages/Dashboard/Withdrawal.vue";
import DashboardWallets from "@/Pages/Dashboard/DashboardWallets.vue";
import BalanceChart from "@/Pages/Transaction/Wallet/Partials/BalanceChart.vue";
import {transactionFormat} from "@/Composables/index.js";

const props = defineProps({
    wallets: Object,
    walletSel: Array,
    paymentAccountSel: Array,
    withdrawalFee: Object,
    paymentDetails: Object,
    totalDeposit: [String,Number],
    totalWithdrawal: [String,Number],
    totalRebate: [String,Number],
});

const { formatAmount } = transactionFormat();
</script>

<template>
    <AuthenticatedLayout title="Wallet">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    Wallet
                </h2>
            </div>
        </template>

        <div class="grid grid-cols-1 sm:grid-cols-2 w-full gap-5">
            <div class="flex flex-col gap-4 sm:col-span-1 col-span-2">
                <div class="flex flex-col gap-2 items-stretch bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 p-5 w-full h-full rounded-lg shadow-lg">
                    <div class="text-sm">
                        Total Deposit
                    </div>
                    <div class="text-base font-semibold">
                        $ {{ totalDeposit }}
                    </div>
                </div>
                <div class="flex flex-col gap-2 items-stretch bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 p-5 w-full h-full rounded-lg shadow-lg">
                    <div class="text-sm">
                        Total Withdrawal
                    </div>
                    <div class="text-base font-semibold">
                        $ {{ formatAmount(totalWithdrawal) }}
                    </div>
                </div>
                <div class="flex flex-col gap-2 items-stretch bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 p-5 w-full h-full rounded-lg shadow-lg">
                    <div class="text-sm">
                        Total Rebate Earn
                    </div>
                    <div class="text-base font-semibold">
                        $ {{ formatAmount(totalRebate) }}
                    </div>
                </div>
            </div>

            <div class="flex flex-col justify-between p-6 sm:col-span-1 col-span-2 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                <div class="text-base font-semibold dark:text-gray-400">
                    Balance Chart
                </div>
                <BalanceChart />
            </div>
        </div>


        <div class="p-5 my-5 mb-28 bg-white overflow-hidden md:overflow-visible rounded-xl shadow-md dark:bg-gray-900">
            <TransactionHistory/>
        </div>

    </AuthenticatedLayout>

</template>
