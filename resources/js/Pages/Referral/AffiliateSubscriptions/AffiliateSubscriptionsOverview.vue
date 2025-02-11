<script setup>
import {
    IconUsers,
    IconCoin
} from "@tabler/icons-vue";
import {transactionFormat} from "@/Composables/index.js";

defineProps({
    totalDeposit: Number,
    totalAffiliate: Number,
})

const {formatAmount} = transactionFormat();
</script>

<template>
    <div class="flex flex-col md:flex-row gap-5 items-center justify-center w-full">
        <div class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900 w-full">
            <div class="flex flex-col gap-1">
                <div>
                    {{ $t('public.total_affiliate') }}
                </div>
                <div class="text-xl font-bold">
                        <span v-if="totalAffiliate !== null">
                            {{ totalAffiliate }}
                        </span>
                    <span v-else>
                            {{ $t('public.loading') }}
                        </span>
                </div>
            </div>
            <div class="rounded-full flex items-center justify-center w-14 h-14 bg-primary-200 dark:bg-primary-800 text-primary-500 dark:text-primary-200">
                <IconUsers size="32" stroke-width="1.5" />
            </div>
        </div>
        <div class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900 w-full">
            <div class="flex flex-col gap-1">
                <div>
                    {{ $t('public.total_active_balance') }}
                </div>
                <div class="text-xl font-bold">
                        <span v-if="totalDeposit !== null">
                            $ {{ formatAmount(totalDeposit) }}
                        </span>
                    <span v-else>
                            {{ $t('public.loading') }}
                        </span>
                </div>
            </div>
            <div class="rounded-full flex items-center justify-center w-14 h-14 bg-success-100 dark:bg-success-800 text-success-500 dark:text-success-200">
                <IconCoin size="32" stroke-width="1.5" />
            </div>
        </div>
    </div>
</template>
