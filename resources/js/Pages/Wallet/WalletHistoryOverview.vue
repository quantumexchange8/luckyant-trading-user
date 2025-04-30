<script setup>
import {transactionFormat} from "@/Composables/index.js";
import {Wallet01Icon} from "@/Components/Icons/outline.jsx";

defineProps({
    cashWalletAmount: Number,
    bonusWalletAmount: Number,
    eWalletAmount: Number,
})

const {formatAmount} = transactionFormat();
</script>

<template>
    <div class="flex flex-col md:flex-row gap-5 items-center w-full self-stretch">
        <div class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900 w-full">
            <div class="flex flex-col gap-4">
                <div>
                    {{ $t('public.cash_wallet') }}
                </div>
                <div v-if="cashWalletAmount !== null" class="text-lg font-bold">
                    ${{ formatAmount(cashWalletAmount) }}
                </div>
                <div v-else class="text-lg">
                    {{ $t('public.loading') }}
                </div>
            </div>
            <div class="rounded-full flex items-center justify-center w-14 h-14 bg-primary-200 dark:bg-primary-900">
                <Wallet01Icon class="w-8 h-8 text-primary-500 dark:text-primary-300" />
            </div>
        </div>
        <div class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900 w-full">
            <div class="flex flex-col gap-4">
                <div>
                    {{ $t('public.bonus_wallet') }}
                </div>
                <div v-if="bonusWalletAmount !== null" class="text-lg font-bold">
                    ${{ formatAmount(bonusWalletAmount) }}
                </div>
                <div v-else class="text-lg">
                    {{ $t('public.loading') }}
                </div>
            </div>
            <div class="rounded-full flex items-center justify-center w-14 h-14 bg-purple-200 dark:bg-purple-900/60">
                <Wallet01Icon class="w-8 h-8 text-purple-500 dark:text-purple-300" />
            </div>
        </div>
        <div class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900 w-full">
            <div class="flex flex-col gap-4">
                <div>
                    {{ $t('public.e_wallet') }}
                </div>
                <div v-if="eWalletAmount !== null" class="text-lg font-bold">
                    ${{ formatAmount(eWalletAmount) }}
                </div>
                <div v-else class="text-lg">
                    {{ $t('public.loading') }}
                </div>
            </div>
            <div class="rounded-full flex items-center justify-center w-14 h-14 bg-gray-200 dark:bg-gray-800">
                <Wallet01Icon class="w-8 h-8 text-gray-500 dark:text-gray-300" />
            </div>
        </div>
    </div>
</template>
