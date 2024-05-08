<script setup>
import {ref, watchEffect} from "vue";
import Badge from "@/Components/Badge.vue";
import StatusBadge from "@/Components/StatusBadge.vue";
import {usePage} from "@inertiajs/vue3";
import {transactionFormat} from "@/Composables/index.js";
import StopRenewSubscription from "@/Pages/Trading/MasterListing/StopRenewSubscription.vue";
import TerminateSubscription from "@/Pages/Trading/MasterListing/TerminateSubscription.vue";

const props = defineProps({
    terms: Object
})

const selectedSubscriberAccount = ref();
const subscriberAccounts = ref(null);
const currentLocale = ref(usePage().props.locale);
const { formatAmount, formatDateTime } = transactionFormat();
const emit = defineEmits(['update:master'])

const getResults = async (page = 1, search = '', type = '', date = '') => {
    try {
        let url = `/trading/getSubscriberAccounts?page=${page}`;

        if (search) {
            url += `&search=${search}`;
        }

        if (type) {
            url += `&type=${type}`;
        }

        if (date) {
            url += `&date=${date}`;
        }

        const response = await axios.get(url);
        subscriberAccounts.value = response.data.subscribers;
        selectedSubscriberAccount.value = subscriberAccounts.value[0]
        if (selectedSubscriberAccount.value) {
            emit('update:master', selectedSubscriberAccount.value.master_id)
        }
    } catch (error) {
        console.error(error);
    }
}

getResults();

const selectSubscriberAccount = (subscriber) => {
    selectedSubscriberAccount.value = subscriber;
    emit('update:master', subscriber.master_id)
}

watchEffect(() => {
    if (usePage().props.title !== null) {
        getResults();
    }
});
</script>

<template>
    <div
        v-if="subscriberAccounts !== null"
        class="grid grid-cols-1 sm:grid-cols-3 gap-4 my-5"
    >
        <div
            v-for="subscriberAccount in subscriberAccounts"
            class="flex flex-col items-start gap-3 bg-white dark:bg-gray-900 rounded-lg p-5 w-full hover:cursor-pointer hover:bg-gray-50"
            :class="{
                'border-2 border-primary-300 dark:border-primary-600': selectedSubscriberAccount.id === subscriberAccount.id,
            }"
            @click="selectSubscriberAccount(subscriberAccount)"
        >
            <div class="flex justify-between items-center w-full">
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
<!--                Swap-->
            </div>

            <div class="border-y border-gray-300 dark:border-gray-600 w-full py-1 flex items-center gap-2 flex justify-between">
                <div class="flex gap-1">
                    <div class="text-sm">{{ $t('public.join_date') }}:</div>
                    <div class="text-sm font-semibold">{{ formatDateTime(subscriberAccount.approval_date, false) }}</div>
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
                        {{ $t('public.amount') }}
                    </div>
                    <div class="flex justify-center">
                        <span class="text-gray-800 dark:text-gray-100 font-semibold">$ {{ formatAmount(subscriberAccount.subscription ? subscriberAccount.subscription.meta_balance : 0, 0) }}</span>
                    </div>
                </div>
                <div class="space-y-1">
                    <div class="text-xs flex justify-center">
                        {{ $t('public.estimated_roi') }}
                    </div>
                    <div class="flex justify-center">
                        <span class="text-gray-800 dark:text-gray-100 font-semibold">$ {{ subscriberAccount.master.estimated_monthly_returns }}</span>
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

            <div
                v-if="subscriberAccount.status !== 'Unsubscribed'"
                class="flex w-full gap-2 items-center"
            >
                <StopRenewSubscription
                    :subscription="subscriberAccount.subscription"
                    :subscriberAccount="subscriberAccount"
                    :terms="terms"
                />
                <TerminateSubscription
                    :subscription="subscriberAccount.subscription"
                    :subscriberAccount="subscriberAccount"
                    :terms="terms"
                />
            </div>
        </div>
    </div>
</template>
