<script setup>
import Button from "@/Components/Button.vue";
import Input from "@/Components/Input.vue";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import {ArrowLeftIcon, ArrowRightIcon, SearchIcon} from "@heroicons/vue/outline";
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import BaseListbox from "@/Components/BaseListbox.vue";
import {ref, watch, watchEffect} from "vue";
import {usePage} from "@inertiajs/vue3";
import Loading from "@/Components/Loading.vue";
import Badge from "@/Components/Badge.vue";
import {TailwindPagination} from "laravel-vue-pagination";
import {transactionFormat} from "@/Composables/index.js";
import debounce from "lodash/debounce.js";
import Modal from "@/Components/Modal.vue";

const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});


const typeFilter = [
    {value: '', label:"All"},
    {value: 'Pending', label:"Pending"},
    {value: 'Active', label:"Active"},
    {value: 'Inactive', label:"Inactive"},
    {value: 'Terminated', label:"Terminated"},
    {value: 'Rejected', label:"Rejected"},
];

const isLoading = ref(false);
const date = ref('');
const search = ref('');
const refresh = ref(false);
const type = ref('');
const subscriptions = ref({data: []})
const currentPage = ref(1)
const { formatDateTime, formatAmount } = transactionFormat();

const getResults = async (page = 1, search = '', type = '', date = '') => {
    isLoading.value = true
    try {
        let url = `/trading/getSubscriptionHistories?page=${page}`;

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
        subscriptions.value = response.data;

    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false
    }
}

getResults()

watch(
    [search, type, date],
    debounce(([searchValue, typeValue, dateValue]) => {
        getResults(1, searchValue, typeValue, dateValue);
    }, 300)
);

const handlePageChange = (newPage) => {
    if (newPage >= 1) {
        currentPage.value = newPage;

        getResults(currentPage.value, search.value, date.value);
    }
};

const subscriptionHistoryModal = ref(false);
const subscriptionHistoryDetail = ref()

const openSubscriptionHistoryModal = (subscription) => {
    subscriptionHistoryModal.value = true
    subscriptionHistoryDetail.value = subscription
}

const closeModal = () => {
    subscriptionHistoryModal.value = false
}

const clearFilter = () => {
    search.value = '';
    type.value = '';
    date.value = [];
}

const statusVariant = (status) => {
    if (status === 'Pending') return 'processing';
    if (status === 'Active') return 'success';
    if (status === 'Rejected' || status === 'Terminated') return 'danger';
}

const paginationClass = [
    'bg-transparent border-0 text-gray-600 dark:text-gray-400 dark:enabled:hover:text-white'
];

const paginationActiveClass = [
    'border dark:border-gray-600 dark:bg-gray-600 rounded-full text-primary-500 dark:text-primary-300'
];

watchEffect(() => {
    if (usePage().props.title !== null) {
        getResults();
    }
});

const currentLocale = ref(usePage().props.locale);

</script>

<template>
    <div class="flex justify-between mb-3">
<!--        <h4 class="font-semibold dark:text-white">{{$t('public.subscription_history')}}</h4>-->
        <!-- <RefreshIcon
            :class="{ 'animate-spin': isLoading }"
            class="flex-shrink-0 w-5 h-5 cursor-pointer dark:text-white"
            aria-hidden="true"
            @click="refreshTable"
        /> -->
    </div>

    <div class="flex flex-wrap gap-3 items-center sm:flex-nowrap">
        <div class="w-full">
            <InputIconWrapper>
                <template #icon>
                    <SearchIcon aria-hidden="true" class="w-5 h-5" />
                </template>
                <Input withIcon id="search" type="text" class="w-full block dark:border-transparent" :placeholder="$t('public.search')" v-model="search" />
            </InputIconWrapper>
        </div>
        <div class="w-full">
            <vue-tailwind-datepicker
                :placeholder="$t('public.date_placeholder')"
                :formatter="formatter"
                separator=" - "
                v-model="date"
                input-classes="py-2.5 w-full rounded-lg dark:placeholder:text-gray-500 focus:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500 bg-white dark:bg-gray-800 dark:text-white border border-gray-300 dark:border-gray-800"
            />
        </div>
        <div class="w-full">
            <BaseListbox
                v-model="type"
                :options="typeFilter"
                :placeholder="$t('public.filters_placeholder')"
                class="w-full"
            />
        </div>
        <div class="w-full sm:w-auto">
            <Button
                type="button"
                variant="transparent"
                @click="clearFilter"
                class="w-full justify-center"
            >
                {{$t('public.clear')}}
            </Button>
        </div>
    </div>

    <div class="mt-2 relative overflow-x-auto">
        <div v-if="isLoading" class="w-full flex justify-center my-8">
            <Loading />
        </div>
        <table v-else class="w-[650px] md:w-full text-sm text-left text-gray-500 dark:text-gray-400 mt-5">
            <thead class="text-xs font-medium text-gray-400 uppercase dark:bg-transparent dark:text-gray-400 border-b dark:border-gray-800">
            <tr>
                <th scope="col" class="p-3">
                    {{$t('public.date')}}
                </th>
                <th scope="col" class="p-3">
                    {{$t('public.account_number')}}
                </th>
                <th scope="col" class="p-3">
                    {{$t('public.subscription_number')}}
                </th>
                <th scope="col" class="p-3">
                    {{$t('public.amount')}}
                </th>
                <th scope="col" class="p-3">
                    {{$t('public.master_account')}}
                </th>
                <th scope="col" class="p-3 text-center">
                    {{$t('public.status')}}
                </th>
            </tr>
            </thead>
            <tbody>
            <tr v-if="subscriptions.data.length === 0">
                <th colspan="6" class="py-4 text-lg text-center">
                    {{$t('public.no_history')}}
                </th>
            </tr>
            <tr
                v-for="subscription in subscriptions.data"
                class="bg-white dark:bg-transparent text-xs text-gray-900 dark:text-white border-b dark:border-gray-800 hover:cursor-pointer hover:bg-primary-50 dark:hover:bg-gray-600"
                @click="openSubscriptionHistoryModal(subscription)"
            >
                <td class="p-3">
                    <div class="inline-flex items-center">
                        {{ formatDateTime(subscription.created_at) }}
                    </div>
                </td>
                <td class="p-3">
                    {{ subscription.meta_login }}
                </td>
                <td class="p-3">
                    {{ subscription.subscription_number }}
                </td>
                <td class="p-3">
                    $ {{ formatAmount(subscription.meta_balance ? subscription.meta_balance : 0) }}
                </td>
                <td class="p-3">
                    <span class="flex-col ml-3">
                        <span v-if="currentLocale === 'en'" class="col-span-2 text-black dark:text-white mr-3">
                            {{ subscription.master.trading_user.name }}
                        </span>
                        <span v-if="currentLocale === 'cn'" class="col-span-2 text-black dark:text-white mr-3">
                            {{ subscription.master.trading_user.company ? subscription.master.trading_user.company : subscription.master.trading_user.name }}
                        </span>
                        <span class="col-span-2 text-black dark:text-white">({{ subscription.master.meta_login }})</span>
                    </span>
                </td>
                <td class="p-3 flex items-center justify-center">
                    <Badge :variant="statusVariant(subscription.status)">{{ $t('public.' + subscription.status.toLowerCase()) }}</Badge>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
    <div class="flex justify-center mt-4" v-if="!isLoading">
        <TailwindPagination
            :item-classes=paginationClass
            :active-classes=paginationActiveClass
            :data="subscriptions"
            :limit=2
            @pagination-change-page="handlePageChange"
        >
            <template #prev-nav>
                <span class="flex gap-2"><ArrowLeftIcon class="w-5 h-5" /> <span class="hidden sm:flex">{{$t('public.previous')}}</span></span>
            </template>
            <template #next-nav>
                <span class="flex gap-2"><span class="hidden sm:flex">{{$t('public.next')}}</span> <ArrowRightIcon class="w-5 h-5" /></span>
            </template>
        </TailwindPagination>
    </div>

    <Modal :show="subscriptionHistoryModal" :title="$t('public.subscription_details')" @close="closeModal">
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{$t('public.subscription_date')}}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ formatDateTime(subscriptionHistoryDetail.created_at, false) }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{$t('public.account_number')}}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ subscriptionHistoryDetail.meta_login }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{$t('public.status')}}</span>
            <Badge class="w-36" :variant="statusVariant(subscriptionHistoryDetail.status)">{{ $t('public.' + subscriptionHistoryDetail.status.toLowerCase()) }}</Badge>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{$t('public.approval_date')}}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ subscriptionHistoryDetail.approval_date ? formatDateTime(subscriptionHistoryDetail.approval_date, false) : '-' }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{$t('public.subscription_number')}}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ subscriptionHistoryDetail.subscription_number }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{$t('public.amount')}}</span>
            <span class="col-span-2 text-black dark:text-white py-2">$ {{ formatAmount(subscriptionHistoryDetail.meta_balance ? subscriptionHistoryDetail.meta_balance : 0) }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{$t('public.master_account')}}</span>
            <span class="flex-col">
                <span v-if="currentLocale === 'en'" class="col-span-2 text-black dark:text-white py-2 mr-3">
                    {{ subscriptionHistoryDetail.master.trading_user.name }}
                </span>
                <span v-if="currentLocale === 'cn'" class="col-span-2 text-black dark:text-white py-2 mr-3">
                    {{ subscriptionHistoryDetail.master.trading_user.company ? subscriptionHistoryDetail.master.trading_user.company : subscriptionHistoryDetail.master.trading_user.name }}
                </span>
                <span class="col-span-2 text-black dark:text-white py-2">({{ subscriptionHistoryDetail.master.meta_login }})</span>
            </span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{$t('public.subscription_fee')}}</span>
            <span class="col-span-2 text-black dark:text-white py-2">$ {{ subscriptionHistoryDetail.subscription_fee ? formatAmount(subscriptionHistoryDetail.subscription_fee) : '0.00' }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{$t('public.roi_period')}}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ subscriptionHistoryDetail.subscription_period }} {{ $t('public.days') }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{$t('public.renew_date')}}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ subscriptionHistoryDetail.expired_date ? formatDateTime(subscriptionHistoryDetail.expired_date, false) : '-' }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{$t('public.remarks')}}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ subscriptionHistoryDetail.remarks ? subscriptionHistoryDetail.remarks : '-' }}</span>
        </div>
    </Modal>
</template>
