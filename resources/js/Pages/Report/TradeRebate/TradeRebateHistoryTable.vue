<script setup>
import Button from "@/Components/Button.vue";
import Input from "@/Components/Input.vue";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import {SearchIcon, ArrowLeftIcon, ArrowRightIcon, RefreshIcon} from "@heroicons/vue/outline";
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
import Combobox from "@/Components/Combobox.vue";
import toast from "@/Composables/toast.js";
import { wTrans } from 'laravel-vue-i18n';

const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});

const props = defineProps({
    search: String,
    date: String,
    refresh: Boolean,
    isLoading: Boolean,
    historyType: String,
    // exportStatus: Boolean,
})
const date = ref('');
const search = ref('');
const refreshRebateHistory = ref(props.refresh);
const rebateHistoryLoading = ref(props.isLoading);
const emit = defineEmits(['update:loading', 'update:refresh', 'update:export']);
const type = ref();
const tradeType = ref();
const tradeHistories = ref({data: []})
const currentPage = ref(1)
const { formatDateTime, formatAmount } = transactionFormat();

const sortDescending = ref('desc');
const types = ref('')

const toggleSort = (sortType) => {
    sortDescending.value = sortDescending.value === 'desc' ? 'asc' : 'desc';
    types.value = sortType;
    console.log(sortType)
}

watch(
    [() => props.search, () => props.date, () => props.historyType, () => types.value, () => sortDescending.value],
    debounce(([searchValue, dateValue, typeValue, sortValue]) => {
        getResults(1, searchValue, dateValue, typeValue, sortValue);
    }, 300)
);

const getResults = async (page = 1, search = props.search, date = props.date, type = props.historyType, sortType = types.value, sort = sortDescending.value) => {
    rebateHistoryLoading.value = true
    try {
        let url = `/report/getTradeRebateHistories?page=${page}`;

        if (search) {
            url += `&search=${search}`;
        }

        if (date) {
            url += `&date=${date}`;
        }

        if (type) {
            url += `&type=${type}`;
        }

        if (sortType) {
            url += `&sortType=${sortType}`;
            url += `&sort=${sort}`;
        }

        const response = await axios.get(url);
        tradeHistories.value = response.data;

    } catch (error) {
        console.error(error);
    } finally {
        rebateHistoryLoading.value = false
        emit('update:loading', false);
    }
}

getResults()

watch(() => props.refresh, (newVal) => {
    refreshRebateHistory.value = newVal;
    if (newVal) {
        refreshHistory();
        emit('update:refresh', false);
    }
});

const refreshHistory = () => {
    getResults(currentPage.value, props.search, props.date, props.historyType, types.value, sortDescending.value);

    toast.add({
        message: wTrans('public.successfully_refreshed'),
    });
}

const handlePageChange = (newPage) => {
    if (newPage >= 1) {
        currentPage.value = newPage;
        getResults(currentPage.value, props.search, props.date, props.historyType, types.value, sortDescending.value);
    }
};

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

const statusVariant = (transactionStatus) => {
    if (transactionStatus === 'Processing') return 'processing';
    if (transactionStatus === 'Success') return 'success';
    if (transactionStatus === 'Rejected') return 'danger';
}

// watch(() => props.exportStatus, (newVal) => {
//     refreshRebateHistory.value = newVal;
//     if(newVal) {

//         let url = `/transaction/getTransactionData?exportStatus=yes`;

//         if (props.date) {
//             url += `&date=${props.date}`;
//         }

//         if (props.search) {
//             url += `&search=${props.search}`;
//         }

//         if (props.category) {
//             url += `&category=${props.category}`;
//         }

//         if (props.methods) {
//             url += `&methods=${props.methods}`;
//         }

//         window.location.href = url;
//         emit('update:export', false);
//     }
// })
</script>

<template>

    <div class="mt-2 relative overflow-x-auto">
        <div v-if="rebateHistoryLoading" class="w-full flex justify-center my-8">
            <Loading />
        </div>
        <table v-else class="w-[650px] md:w-full text-sm text-left text-gray-500 dark:text-gray-400 mt-5">
            <thead class="text-xs font-medium text-gray-400 uppercase dark:bg-transparent dark:text-gray-400 border-b dark:border-gray-800">
            <tr>
                <th scope="col" class="p-3">
                    {{$t('public.date')}}
                </th>
                <th scope="col" class="p-3">
                    <span v-if="historyType === 'Affiliate'">{{$t('public.affiliate')}}</span>
                    <span v-if="historyType === 'Personal'">{{$t('public.live_account')}}</span>
                </th>
                <th scope="col" class="p-3 text-center">
                    {{$t('public.volume')}}
                </th>
                <th scope="col" class="p-3 text-center">
                    {{$t('public.rebate')}} ($)
                </th>
                <th scope="col" class="p-3 text-center">
                    {{$t('public.status')}}
                </th>
            </tr>
            </thead>
            <tbody>
            <tr v-if="tradeHistories.data.length === 0">
                <th colspan="4" class="py-4 text-lg text-center">
                    {{$t('public.no_history')}}
                </th>
            </tr>
            <tr
                v-for="history in tradeHistories.data"
                class="bg-white dark:bg-transparent text-xs text-gray-900 dark:text-white border-b dark:border-gray-800 hover:bg-primary-50 dark:hover:bg-gray-600"
            >
                <td class="p-3">
                    {{ formatDateTime(history.created_at) }}
                </td>
                <td class="p-3 inline-flex">
                    <div class="grid" v-if="historyType === 'Affiliate'">
                        <span>{{ history.of_user.username }} - <span class="font-semibold">{{ history.meta_login }}</span></span>
                        <span class="dark:text-gray-400">{{ history.of_user.email }}</span>
                    </div>
                    <div class="grid" v-if="historyType === 'Personal'">
                        <span class="font-semibold">{{ history.meta_login }}</span>
                        <span class="dark:text-gray-400">{{ history.trading_account.trading_user.name }}</span>
                    </div>
                </td>
                <td class="p-3 font-semibold text-center">
                    {{ formatAmount(history.volume) }}
                </td>
                <td class="p-3 font-semibold text-center">
                    {{ formatAmount(history.rebate) }}
                </td>
                <td class="p-3 flex items-center justify-center">
                    <Badge :variant="statusVariant(history.status)">{{ $t('public.' + history.status.toLowerCase()) }}</Badge>
                </td>
            </tr>
            </tbody>
        </table>
    </div>
    <div class="flex justify-center mt-4" v-if="!rebateHistoryLoading">
        <TailwindPagination
            :item-classes=paginationClass
            :active-classes=paginationActiveClass
            :data="tradeHistories"
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
</template>
