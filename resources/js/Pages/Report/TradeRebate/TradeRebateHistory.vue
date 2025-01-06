<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import {ref} from "vue";
import { transactionFormat } from "@/Composables/index.js";
import {CurrencyDollarCircleIcon} from "@/Components/Icons/outline.jsx";
import { CoinsHandIcon } from '@/Components/Icons/outline'
import TradeRebateTable from "@/Pages/Report/TradeRebate/TradeRebateTable.vue";

const { formatAmount } = transactionFormat();

const totalRebateAmount = ref(null);
const totalAffiliateRebate = ref(null);
const totalPersonalRebate = ref(null);
const totalTradeLots = ref(null);
const totalAffiliateLot = ref(null);
const totalPersonalLot = ref(null);

const handleUpdateTotals = (data) => {
    totalRebateAmount.value = data.totalRebateAmount;
    totalAffiliateRebate.value = data.totalAffiliateRebate;
    totalPersonalRebate.value = data.totalPersonalRebate;
    totalTradeLots.value = data.totalTradeLots;
    totalAffiliateLot.value = data.totalAffiliateLot;
    totalPersonalLot.value = data.totalPersonalLot;
};
</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar.trade_rebate')">
        <template #header>
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    {{ $t('public.sidebar.trade_rebate') }}
                </h2>
            </div>
        </template>

        <div class="grid grid-cols-1 sm:grid-cols-2 w-full gap-4">
            <div class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                <div class="flex flex-col gap-4">
                    <div>
                        {{ $t('public.total_rebate_amount') }}
                    </div>
                    <div class="text-xl font-bold">
                        <span v-if="totalRebateAmount !== null">
                            $ {{ totalRebateAmount !== '' ? formatAmount(totalRebateAmount) : '0' }}
                        </span>
                        <span v-else>
                            {{ $t('public.loading') }}
                        </span>
                    </div>
                </div>
                <div class="rounded-full flex items-center justify-center w-14 h-14 bg-success-200">
                    <CurrencyDollarCircleIcon class="text-success-500 w-8 h-8" />
                </div>
            </div>
            <div class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                <div class="flex flex-col gap-4">
                    <div>
                        {{ $t('public.total_trade_lots') }}
                    </div>
                    <div class="text-xl font-bold">
                        <span v-if="totalTradeLots !== null">
                            {{ totalTradeLots !== '' ? formatAmount(totalTradeLots) : '0' }}
                        </span>
                        <span v-else>
                            {{ $t('public.loading') }}
                        </span>
                    </div>
                </div>
                <div class="rounded-full flex items-center justify-center w-14 h-14 bg-primary-200">
                    <CoinsHandIcon class="text-primary-500 w-8 h-8" />
                </div>
            </div>
            <div class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                <div class="flex flex-col gap-4">
                    <div>
                        {{ $t('public.affiliate') }}
                    </div>
                    <div class="text-xl font-bold">
                        <div v-if="totalAffiliateRebate !== null" class="flex flex-col">
                            <span>$ {{ totalAffiliateRebate !== '' ? formatAmount(totalAffiliateRebate) : '0' }}</span>
                            <span class="text-lg font-medium text-gray-600 dark:text-gray-400">{{ formatAmount(totalAffiliateLot) }} {{ $t('public.lot') }}</span>
                        </div>
                        <div v-else>
                            {{ $t('public.loading') }}
                        </div>
                    </div>
                </div>
                <div class="rounded-full flex items-center justify-center w-14 h-14 bg-purple-200">
                    <CurrencyDollarCircleIcon class="text-purple-500 w-8 h-8" />
                </div>
            </div>
            <div class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                <div class="flex flex-col gap-4">
                    <div>
                        {{ $t('public.personal') }}
                    </div>
                    <div class="text-xl font-bold">
                        <div v-if="totalPersonalRebate !== null" class="flex flex-col">
                            <span>$ {{ totalPersonalRebate !== '' ? formatAmount(totalPersonalRebate) : '0' }}</span>
                            <span class="text-lg font-medium text-gray-600 dark:text-gray-400">{{ formatAmount(totalPersonalLot) }} {{ $t('public.lot') }}</span>
                        </div>
                        <div v-else>
                            {{ $t('public.loading') }}
                        </div>
                    </div>
                </div>
                <div class="rounded-full flex items-center justify-center w-14 h-14 bg-gray-200">
                    <CurrencyDollarCircleIcon class="text-gray-500 w-8 h-8" />
                </div>
            </div>
        </div>

        <div class="pt-5">
            <TradeRebateTable
                @update-totals="handleUpdateTotals"
            />
        </div>
    </AuthenticatedLayout>
</template>
