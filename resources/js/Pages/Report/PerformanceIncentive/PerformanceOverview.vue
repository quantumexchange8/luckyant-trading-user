<script setup>
import {transactionFormat} from "@/Composables/index.js";
import {IconCoin} from "@tabler/icons-vue";

defineProps({
    totalPerformanceIncentive: Number,
    totalAffiliateAmount: Number,
    totalPersonalAmount: Number,
})

const {formatAmount} = transactionFormat();
</script>

<template>
    <div class="flex flex-col md:flex-row gap-5 items-center w-full self-stretch">
        <div class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900 w-full">
            <div class="flex flex-col gap-4">
                <div>
                    {{ $t('public.total_performance_incentive') }}
                </div>
                <div v-if="totalPerformanceIncentive !== null" class="text-xl font-bold">
                    ${{ formatAmount(totalPerformanceIncentive) }}
                </div>
                <div v-else class="text-xl">
                    {{ $t('public.loading') }}
                </div>
            </div>
            <div class="rounded-full flex items-center justify-center w-14 h-14 bg-success-100 dark:bg-success-900 text-success-500">
                <IconCoin size="40" stroke-width="1.5" />
            </div>
        </div>
        <div class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900 w-full">
            <div class="flex flex-col gap-4">
                <div>
                    {{ $t('public.affiliate') }}
                </div>
                <div v-if="totalAffiliateAmount !== null" class="text-xl font-bold">
                    ${{ formatAmount(totalAffiliateAmount) }}
                </div>
                <div v-else class="text-xl">
                    {{ $t('public.loading') }}
                </div>
            </div>
            <div class="rounded-full flex items-center justify-center w-14 h-14 bg-purple-200 dark:bg-purple-900/60 text-purple-500">
                <IconCoin size="40" stroke-width="1.5" />
            </div>
        </div>
        <div class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900 w-full">
            <div class="flex flex-col gap-4">
                <div>
                    {{ $t('public.personal') }}
                </div>
                <div v-if="totalPersonalAmount !== null" class="text-xl font-bold">
                    ${{ formatAmount(totalPersonalAmount) }}
                </div>
                <div v-else class="text-xl">
                    {{ $t('public.loading') }}
                </div>
            </div>
            <div class="rounded-full flex items-center justify-center w-14 h-14 bg-gray-200 dark:bg-gray-800 text-gray-500">
                <IconCoin size="40" stroke-width="1.5" />
            </div>
        </div>
    </div>
</template>
