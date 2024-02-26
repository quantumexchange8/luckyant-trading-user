<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import TransactionHistory from "@/Pages/Transaction/Wallet/Partials/TransactionHistory.vue";
import Button from "@/Components/Button.vue";
import { ref } from 'vue'
import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue'
import Deposit from "@/Pages/Dashboard/Deposit.vue";
import BalanceChart from "@/Pages/Dashboard/BalanceChart.vue";
import {RefreshIcon} from "@/Components/Icons/outline.jsx";
import Withdrawal from "@/Pages/Dashboard/Withdrawal.vue";

const props = defineProps({
    wallets: Object,
    walletSel: Array,
    withdrawalFee: Object,
    PaymentDetails: Object,
});
</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar.Transaction')">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    Wallet
                </h2>
            </div>
        </template>

        <div class="grid grid-cols-1 sm:grid-cols-2 w-full gap-5">
            <div v-for="wallet in wallets" class="flex flex-col sm:flex-row p-5 items-center self-stretch gap-[60px] bg-white rounded-xl shadow-md dark:bg-gray-700 sm:col-span-1 col-span-2">
                <div class="flex flex-col justify-center items-start gap-8 self-stretch w-full">
                    <div class="flex flex-col items-start gap-3">
                        <div class="text-base font-semibold dark:text-gray-400">
                            Total Balance
                        </div>
                        <div class="text-[28px] font-semibold dark:text-white">
                            <!-- $&nbsp;{{ props.totalBalance }} -->
                        </div>
                    </div>
                    <div class="z-10 flex flex-col gap-1 justify-evenly w-full h-full">
                        <div class="text-lg font-bold">{{ wallet.name }} ({{wallet.wallet_address }})</div>
                        <div class="text-2xl">
                            $ {{ wallet.balance }}
                        </div>
                        <div class="flex justify-between w-full gap-2">
                            <Deposit
                                :walletSel="walletSel"
                                :PaymentDetails="PaymentDetails"
                            />
                            <Withdrawal
                                :walletSel="walletSel"
                                :withdrawalFee="withdrawalFee"
                            />
                        </div>
                        <!-- <div class="flex items-center justify-center w-full">
                            <Button
                                type="button"
                                variant="primary"
                                size="sm"
                                class="flex justify-center w-full gap-1"
                                    v-slot="{ iconSizeClasses }"
                            >
                                <RefreshIcon aria-hidden="true" :class="iconSizeClasses" />
                                Internal Transfer
                            </Button>
                        </div> -->
                    </div>
                </div>
            </div>

            <div class="flex flex-col justify-between p-6 sm:col-span-1 col-span-2 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                <div class="text-base font-semibold dark:text-gray-400">
                    Overview
                </div>
                <div class="flex flex-col gap-2">
                    <div class="flex justify-between font-bold text-lg">
                        <div>
                            Total Deposit
                        </div>
                        <div>
                            $ 0.00
                        </div>
                    </div>
                    <div class="flex justify-between font-bold text-lg">
                        <div>
                            Total Withdrawal
                        </div>
                        <div>
                            $ 0.00
                        </div>
                    </div>
                    <div class="flex justify-between font-bold text-lg">
                        <div>
                            Total Rebate
                        </div>
                        <div>
                            $ 0.00
                        </div>
                    </div>
                </div>
               <!-- <BalanceChart /> -->
            </div>
        </div>


        <div class="p-5 my-5 mb-28 bg-white overflow-hidden md:overflow-visible rounded-xl shadow-md dark:bg-gray-900">
            <TransactionHistory/>
        </div>

    </AuthenticatedLayout>

</template>
