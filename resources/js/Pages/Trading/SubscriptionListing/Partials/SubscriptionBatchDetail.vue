<script setup>
import Badge from "@/Components/Badge.vue";
import {onMounted, ref} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import {usePage} from "@inertiajs/vue3";
import StatusBadge from "@/Components/StatusBadge.vue";

const props =  defineProps({
    subscription: Object
})

const emit = defineEmits(['update:subscriptionModal']);
const { formatDateTime, formatAmount } = transactionFormat();
const currentLocale = ref(usePage().props.locale);
const batchDetail = ref(null);

onMounted(async () => {
    try {
        console.log(props.subscription.id)
        const response = await axios.get('getPenaltyDetail?subscription_batch_id=' + props.subscription.id);
        batchDetail.value = response.data;
    } catch (error) {
        console.error(error);
    }
});

const closeModal = () => {
    emit('update:subscriptionModal', false);
}

const statusVariant = (autoRenewal) => {
    if (autoRenewal === 1) {
        return 'success';
    } else {
        return 'danger'
    }
}

const calculateWidthPercentage = (starting_date, expired_date, period) => {
    const startDate = new Date(starting_date);
    const endDate = new Date(expired_date);

    const currentDate = new Date();
    const elapsedMilliseconds = currentDate - startDate;
    const elapsedDays = Math.ceil(elapsedMilliseconds / (1000 * 60 * 60 * 24));

    const totalMilliseconds = endDate - startDate;
    const totalDays = Math.ceil(totalMilliseconds / (1000 * 60 * 60 * 24));

    // Adjust remaining time display based on the unit of the period
    const remainingTime = Math.ceil(period - elapsedDays);

    const widthResult = Math.max(0, Math.min(100, (elapsedDays / totalDays) * 100));

    return { widthResult, remainingTime };
};
</script>

<template>
    <div class="p-5 bg-gray-100 dark:bg-gray-600 rounded-lg">
        <div class="flex flex-col items-start gap-3 self-stretch">
            <div class="flex items-center justify-between gap-2 self-stretch">
                <div class="text-lg font-semibold dark:text-white">
                    <div v-if="currentLocale === 'en'">
                        {{ subscription.master.trading_user.name }} - {{ subscription.master_meta_login }}
                    </div>
                    <div v-if="currentLocale === 'cn'">
                        {{ subscription.master.trading_user.company ? subscription.master.trading_user.company : subscription.master.trading_user.name }} - {{ subscription.master_meta_login }}
                    </div>
                </div>
                <StatusBadge
                    :value="subscription.status"
                    width="w-20"
                />
            </div>
            <div class="flex items-center justify-between gap-2 self-stretch">
                <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                    {{$t('public.account_number')}}
                </div>
                <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                    {{ subscription.meta_login }}
                </div>
            </div>
            <div class="flex items-center justify-between gap-2 self-stretch">
                <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                    {{$t('public.subscription_number')}}
                </div>
                <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                    {{ subscription.subscription_number }}
                </div>
            </div>
            <div class="flex items-center justify-between gap-2 self-stretch">
                <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                    {{$t('public.join_date')}}
                </div>
                <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                    {{ formatDateTime(subscription.approval_date, false) }}
                </div>
            </div>
            <div class="flex items-center justify-between gap-2 self-stretch">
                <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                    {{$t('public.join_day')}}
                </div>
                <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                    {{ subscription.join_days }} {{ $t('public.days') }} / {{ subscription.management_period }} {{ $t('public.days') }}
                </div>
            </div>
            <div class="flex items-start justify-between gap-2 self-stretch">
                <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                    {{$t('public.amount')}}
                </div>
                <div class="text-sm sm:text-base text-primary-500 dark:text-primary-400 font-bold">
                    $ {{ formatAmount(subscription.meta_balance) }}
                </div>
            </div>
            <div
                v-if="batchDetail && subscription.status === 'Terminated'"
                class="flex items-start justify-between gap-2 self-stretch"
            >
                <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                    {{$t('public.management_fee')}} (<span :class="{'text-error-500': batchDetail.penalty_percent > 0}">{{ formatAmount(batchDetail.penalty_percent, 0) }}%</span>)
                </div>
                <div class="text-sm sm:text-base text-error-500 font-bold">
                    $ {{ formatAmount(batchDetail ? batchDetail.penalty_amount : 0) }}
                </div>
            </div>
            <div
                v-if="batchDetail && subscription.status === 'Terminated'"
                class="flex items-start justify-between gap-2 self-stretch"
            >
                <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                    {{$t('public.return_amount')}}
                </div>
                <div class="text-sm sm:text-base text-success-500 font-bold">
                    $ {{ formatAmount(subscription.meta_balance - (batchDetail ? batchDetail.penalty_amount : 0)) }}
                </div>
            </div>
            <div
                v-if="batchDetail && subscription.status === 'Terminated'"
                class="flex items-start justify-between gap-2 self-stretch"
            >
                <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                    {{$t('public.termination_date')}}
                </div>
                <div class="text-sm sm:text-base text-error-500 font-bold">
                    {{ formatDateTime(subscription ? subscription.termination_date : '-', false) }}
                </div>
            </div>
            <div
                v-if="subscription.status !== 'Terminated'"
                class="flex items-center justify-between gap-2 self-stretch"
            >
                <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                    {{$t('public.auto_renewal')}}
                </div>
                <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                    <Badge :variant="statusVariant(subscription.auto_renewal)">{{ subscription.auto_renewal === 1 ? $t('public.auto') : $t('public.expiring') }}</Badge>
                </div>
            </div>
            <div
                v-if="subscription.status !== 'Terminated'"
                class="flex flex-col gap-2 self-stretch"
            >
                <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                    {{$t('public.progress')}}
                </div>
                <div class="mb-1 flex h-2.5 w-full overflow-hidden rounded-full bg-gray-300 dark:bg-gray-400 text-xs">
                    <div
                        :style="{ width: `${calculateWidthPercentage(subscription.approval_date, subscription.settlement_date, subscription.subscription_period).widthResult}%` }"
                        class="rounded-full bg-gradient-to-r from-primary-300 to-success-400 dark:from-primary-400 dark:to-success-500 transition-all duration-500 ease-out"
                    >
                    </div>
                </div>
                <div class="mb-2 flex items-center justify-between text-xs">
                    <div class="dark:text-gray-400">
                        {{ formatDateTime(subscription.approval_date, false) }}
                    </div>
                    <div class="dark:text-gray-400">
                        {{ formatDateTime(subscription.settlement_date, false) }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
