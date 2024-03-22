<script setup>
import Button from "@/Components/Button.vue";
import BaseListbox from "@/Components/BaseListbox.vue";
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import {SearchIcon} from "@heroicons/vue/outline";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import Input from "@/Components/Input.vue";
import {ref, watch, watchEffect} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import debounce from "lodash/debounce.js";
import SubscriptionForm from "@/Pages/Trading/MasterListing/SubscriptionForm.vue";
import Badge from "@/Components/Badge.vue";
import TerminateSubscription from "@/Pages/Trading/MasterListing/TerminateSubscription.vue";
import {usePage} from "@inertiajs/vue3";
import SubscriptionHistory from "@/Pages/Trading/MasterListing/SubscriptionHistory.vue";
import StopRenewSubscription from "@/Pages/Trading/MasterListing/StopRenewSubscription.vue";

const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});

const typeFilter = [
    {value: '', label:"All"},
    {value: 'max_equity', label:"Highest Equity to follow"},
    {value: 'min_equity', label:"Lowest Equity to follow"},
    {value: 'max_sub', label:"Most Subscribers"},
    {value: 'min_sub', label:"Least Subscribers"},
];

const categories = ref({});
const isLoading = ref(false);
const date = ref('');
const search = ref('');
const refresh = ref(false);
const type = ref('');
const subscriberAccounts = ref({data: []})
const currentPage = ref(1);
const { formatAmount, formatDateTime } = transactionFormat();
const currentLocale = ref(usePage().props.locale);

const getResults = async (page = 1, search = '', type = '', date = '') => {
    isLoading.value = true
    try {
        let url = `/trading/getSubscriptions?page=${page}`;

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
        subscriberAccounts.value = response.data;
    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false
    }
}

getResults();

watch(
    [search, type],
    debounce(([searchValue, typeValue, dateValue]) => {
        getResults(1, searchValue, typeValue, dateValue);
    }, 300)
);

const clearFilter = () => {
    search.value = '';
}

const statusVariant = (status) => {
    if (status === 'Pending') return 'processing';
    if (status === 'Active') return 'success';
    if (status === 'Rejected' || 'Terminated') return 'danger';
}

watchEffect(() => {
    if (usePage().props.title !== null) {
        getResults();
    }
});
</script>

<template>
    <div class="flex justify-end">
        <div class="flex flex-wrap gap-3 items-center sm:flex-nowrap w-full sm:w-1/2">
            <div class="w-full">
                <InputIconWrapper>
                    <template #icon>
                        <SearchIcon aria-hidden="true" class="w-5 h-5" />
                    </template>
                    <Input
                        withIcon
                        id="search"
                        type="text"
                        class="w-full block dark:border-transparent"
                        :placeholder="$t('public.search_name_and_account_no_placeholder')"
                        v-model="search"
                    />
                </InputIconWrapper>
            </div>
<!--            <div class="w-full">-->
<!--                <BaseListbox-->
<!--                    v-model="type"-->
<!--                    :options="typeFilter"-->
<!--                    placeholder="Filters"-->
<!--                    class="w-full"-->
<!--                />-->
<!--            </div>-->

            <div class="w-full sm:w-auto">
                <Button
                    type="button"
                    variant="transparent"
                    @click="clearFilter"
                    class="w-full justify-center"
                >
                    {{ $t('public.clear') }}
                </Button>
            </div>
        </div>
    </div>

    <div
        class="grid grid-cols-1 sm:grid-cols-3 gap-5 my-5"
    >
        <div
            v-for="subscriberAccount in subscriberAccounts.data"
            class="flex flex-col items-start gap-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg p-5 w-full shadow-lg hover:cursor-pointer hover:bg-gray-50 hover:shadow-primary-300"
        >
            <div class="flex justify-between items-center w-full">
                <div class="flex gap-2">
                    <img
                        class="object-cover w-12 h-12 rounded-full"
                        :src="subscriberAccount.master.user.profile_photo ? subscriberAccount.master.user.profile_photo : 'https://img.freepik.com/free-icon/user_318-159711.jpg'"
                        alt="userPic"
                    />
                    <div class="flex flex-col">
                        <div v-if="currentLocale === 'en'" class="text-sm">
                            {{ subscriberAccount.master.user.username }}
                        </div>
                        <div v-if="currentLocale === 'cn'" class="text-sm">
                            {{ subscriberAccount.master.trading_user.company ? subscriberAccount.master.trading_user.company : subscriberAccount.master.trading_user.name }}
                        </div>
                        <div class="font-semibold">
                            {{ subscriberAccount.master.meta_login }}
                        </div>
                    </div>
                </div>
                <div>
                    <Badge :variant="statusVariant(subscriberAccount.subscription.status)" width="full">{{ subscriberAccount.subscription.status }}</Badge>
                </div>
            </div>

            <div class="border-y border-gray-300 dark:border-gray-600 w-full py-1 flex items-center gap-2 flex justify-between">
                <div class="flex gap-1">
                    <div class="text-sm">{{ $t('public.join_date') }}:</div>
                    <div class="text-sm font-semibold">{{ formatDateTime(subscriberAccount.subscription.approval_date, false) }}</div>
                </div>
                <div class="flex gap-1">
                    <div class="text-sm">{{ $t('public.join_day') }}:</div>
                    <div class="text-sm font-semibold">{{ subscriberAccount.join_days }}</div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 w-full">
                <div class="space-y-1 col-span-2">
                    <div class="flex justify-center gap-2">
                        <div>{{ $t('public.account_no') }}</div>
                        <div class="text-gray-800 dark:text-gray-100 font-semibold">{{ subscriberAccount.meta_login }}
                        </div>
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
                        <span class="text-gray-800 dark:text-gray-100 font-semibold">$ {{ formatAmount(subscriberAccount.subscription.meta_balance ? subscriberAccount.subscription.meta_balance : 0) }}</span>
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

            <div class="flex w-full gap-2 items-center">
                <StopRenewSubscription
                    v-if="subscriberAccount.subscription.status === 'Active'"
                    :subscriberAccount="subscriberAccount"
                />
                <TerminateSubscription
                    v-if="subscriberAccount.subscription.status === 'Active'"
                    :subscriberAccount="subscriberAccount"
                />
            </div>

        </div>
    </div>

    <div class="p-5 my-5 mb-28 bg-white overflow-hidden md:overflow-visible rounded-xl shadow-md dark:bg-gray-900">
        <SubscriptionHistory
        />
    </div>
</template>
