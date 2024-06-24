<script setup>
import {ref, watch, watchEffect} from "vue";
import {usePage} from "@inertiajs/vue3";
import {transactionFormat} from "@/Composables/index.js";
import debounce from "lodash/debounce.js";
import StatusBadge from "@/Components/StatusBadge.vue";

const props = defineProps({
    search: String,
    meta_login: Number,
    date: String,
})

const selectedSubscriberAccount = ref();
const subscriberAccount = ref(null);
const currentLocale = ref(usePage().props.locale);
const { formatAmount, formatDateTime } = transactionFormat();
const emit = defineEmits(['update:master', 'update:meta_login'])

const getResults = async (page = 1, search = props.search, filterMetaLogin = props.meta_login, date = props.date) => {
    try {
        let url = `/pamm/getPammMasters?page=${page}`;

        if (search) {
            url += `&search=${search}`;
        }

        if (filterMetaLogin) {
            url += `&meta_login=${filterMetaLogin}`;
        }

        if (date) {
            url += `&date=${date}`;
        }

        const response = await axios.get(url);
        subscriberAccount.value = response.data.subscriber;

        selectedSubscriberAccount.value = subscriberAccount.value[0]
    } catch (error) {
        console.error(error);
    }
}

getResults();

watch(
    [() => props.search, () => props.meta_login, () => props.date],
    debounce(([searchValue, metaLoginValue, dateValue]) => {
        getResults(1, searchValue, metaLoginValue, dateValue);
    }, 300)
);

watchEffect(() => {
    if (usePage().props.title !== null) {
        getResults();
    }
});
</script>

<template>
    <div v-if="subscriberAccount !== null" class="grid grid-cols-1 sm:grid-cols-2 gap-5 my-5">
        <div class="flex flex-col items-start gap-3 bg-white dark:bg-gray-900 rounded-lg p-5 w-full">
            <div class="flex gap-2 items-start">
                <div class="flex flex-col">
                    <div v-if="currentLocale === 'en'" class="text-sm">
                        {{ subscriberAccount.master.trading_user.name }}
                    </div>
                    <div v-if="currentLocale === 'cn'" class="text-sm">
                        {{ subscriberAccount.master.trading_user.company ? subscriberAccount.master.trading_user.company : subscriberAccount.master.trading_user.name }}
                    </div>
                    <div class="font-semibold">
                        {{ subscriberAccount.master.meta_login }}
                    </div>
                </div>
                <StatusBadge
                    :value="subscriberAccount.status"
                    width="w-20"
                />
            </div>

            <div class="border-y border-gray-300 dark:border-gray-600 w-full py-1 flex items-center gap-2 flex justify-between">
                <div class="flex gap-1">
                    <div class="text-sm">{{ $t('public.join_date') }}:</div>
                    <div class="text-sm font-semibold">{{ subscriberAccount.approval_date ? formatDateTime(subscriberAccount.approval_date, false) : $t('public.pending') }}</div>
                </div>
                <div class="flex gap-1">
                    <div class="text-sm">{{ $t('public.join_day') }}:</div>
                    <div class="text-sm font-semibold">{{ subscriberAccount.join_days }}</div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 w-full">
                <div class="space-y-1">
                    <div class="text-xs flex justify-center">
                        {{ $t('public.live_account') }}
                    </div>
                    <div class="flex justify-center gap-2">
                        <span class="text-gray-800 dark:text-gray-100 font-semibold">{{ subscriberAccount.trading_user.name }}
                        </span>
                    </div>
                </div>
                <div class="space-y-1">
                    <div class="text-xs flex justify-center">
                        {{ $t('public.account_number') }}
                    </div>
                    <div class="flex justify-center gap-2">
                        <span class="text-gray-800 dark:text-gray-100 font-semibold">{{ subscriberAccount.meta_login }}
                        </span>
                    </div>
                </div>
                <div class="space-y-1">
                    <div class="text-xs flex justify-center">
                        {{ $t('public.sharing_profit') }}
                    </div>
                    <div class="flex justify-center">
                        <span class="text-gray-800 dark:text-gray-100 font-semibold">{{ subscriberAccount.master.sharing_profit % 1 === 0 ? formatAmount(subscriberAccount.master.sharing_profit, 0) : formatAmount(subscriberAccount.master.sharing_profit) }}%</span>
                    </div>
                </div>
                <div class="space-y-1">
                    <div class="text-xs flex justify-center">
                        {{ $t('public.total_fund') }}
                    </div>
                    <div class="flex justify-center">
                        <span class="text-gray-800 dark:text-gray-100 font-semibold">$ {{ formatAmount(subscriberAccount.master ? subscriberAccount.master.total_fund : 0, 0) }}</span>
                    </div>
                </div>
                <div class="space-y-1">
                    <div class="text-xs flex justify-center">
                        {{ $t('public.estimated_roi') }}
                    </div>
                    <div class="flex justify-center">
                        <span class="text-gray-800 dark:text-gray-100 font-semibold">{{ subscriberAccount.master.estimated_monthly_returns }}</span>
                    </div>
                </div>
                <div class="space-y-1">
                    <div class="text-xs flex justify-center">
                        {{ $t('public.roi_period') }}
                    </div>
                    <div class="flex justify-center">
                        <span class="text-gray-800 dark:text-gray-100 font-semibold">{{ subscriberAccount.master.roi_period }} {{ $t('public.days') }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex flex-col items-start gap-5 bg-white dark:bg-gray-900 rounded-lg p-5 w-full">
            Investment Detail
            <div class="flex gap-3 items-center self-stretch">
                <div class="flex flex-col w-full">
                    <div class="text-xs flex justify-center">
                        {{ $t('public.package') }}
                    </div>
                    <div class="flex justify-center">
                        <span class="text-gray-800 dark:text-gray-100 font-semibold">$ {{ formatAmount(subscriberAccount.subscription ? subscriberAccount.subscription.meta_balance : 0, 0) }}</span>
                    </div>
                </div>
                <div class="flex flex-col w-full">
                    <div class="text-xs flex justify-center">
                        {{ $t('public.max_out_amount') }}
                    </div>
                    <div class="flex justify-center">
                        <span class="text-gray-800 dark:text-gray-100 font-semibold">$ {{ formatAmount(subscriberAccount.subscription ? subscriberAccount.subscription.max_out_amount : 0, 0) }}</span>
                    </div>
                </div>
            </div>
            <div class="flex flex-col gap-2 self-stretch">
                <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                    {{ $t('public.progress') }}
                </div>
                <div class="mb-1 flex h-2.5 w-full overflow-hidden rounded-full bg-gray-300 dark:bg-gray-400 text-xs">
                    <div
                        :style="{ width: `${subscriberAccount.progressWidth}%` }"
                        class="rounded-full bg-gradient-to-r from-primary-300 to-primary-600 dark:from-primary-500 dark:to-primary-800 transition-all duration-500 ease-out"
                    >
                    </div>
                </div>
                <div class="mb-2 flex items-center justify-between text-xs">
                    <div class="dark:text-gray-400">
                        $ {{ formatAmount(subscriberAccount.subscription ? subscriberAccount.subscription.cumulative_amount : 0, 0) }}
                    </div>
                    <div class="dark:text-gray-400">$ {{ subscriberAccount.max_out_amount }}</div>
                </div>
            </div>
        </div>
    </div>
</template>
