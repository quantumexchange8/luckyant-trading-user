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
    trade_rebates: Object
})
const isLoading = ref(false);
const date = ref('');
const search = ref('');
const refresh = ref(false);
const type = ref();
const tradeType = ref();
const tradeHistories = ref({data: []})
const currentPage = ref(1)
const { formatDateTime, formatAmount } = transactionFormat();

const getResults = async (page = 1, search = '', date = '') => {
    isLoading.value = true
    try {
        let url = `/report/getTradeRebateHistories?page=${page}`;

        if (search) {
            url += `&search=${search}`;
        }

        if (date) {
            url += `&date=${date}`;
        }

        const response = await axios.get(url);
        tradeHistories.value = response.data;

    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false
    }
}

getResults()

watch(
    [search, date],
    debounce(([serachValue, dateValue]) => {
        getResults(1, serachValue, dateValue);
    }, 300)
);

const handlePageChange = (newPage) => {
    if (newPage >= 1) {
        currentPage.value = newPage;
        getResults(currentPage.value, search.value, date.value);
    }
};

const refreshHistory = () => {
    getResults(1, search.value, date.value);

    toast.add({
        message: wTrans('public.successfully_refreshed'),
    });
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

// function loadSymbols(query, setOptions) {
//     fetch(`/trading/getTradingSymbols?meta_login=${props.meta_login}&query=` + query)
//         .then(response => response.json())
//         .then(results => {
//             setOptions(
//                 results.map(history => {
//                     return {
//                         value: history.symbol,
//                         label: history.symbol,
//                     }
//                 })
//             )
//         });
// }
</script>

<template>
    <div class="flex justify-between mb-3">
        <h4 class="font-semibold text-lg dark:text-white">{{$t('public.rebate_history')}}</h4>
        <RefreshIcon
            :class="{ 'animate-spin': isLoading }"
            class="flex-shrink-0 w-5 h-5 cursor-pointer dark:text-white"
            aria-hidden="true"
            @click="refreshHistory"
        />
    </div>

    <div class="flex flex-wrap gap-3 w-full justify-end items-center sm:flex-nowrap">
        <div class="w-full sm:w-80">
            <InputIconWrapper>
                <template #icon>
                    <SearchIcon aria-hidden="true" class="w-5 h-5" />
                </template>
                <!-- <Input withIcon id="search" type="text" class="w-full block dark:border-transparent" :placeholder="$t('public.report.search_placeholder')" v-model="search" /> -->
                <Input withIcon id="search" type="text" class="w-full block dark:border-transparent" :placeholder="$t('public.search')" v-model="search" />
            </InputIconWrapper>
        </div>
        <div class="w-full sm:w-80">
            <vue-tailwind-datepicker
                :placeholder="$t('public.date_placeholder')"
                :formatter="formatter"
                separator=" - "
                v-model="date"
                input-classes="py-2.5 w-full rounded-lg dark:placeholder:text-gray-500 focus:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500 bg-white dark:bg-gray-700 dark:text-white border border-gray-300 dark:border-dark-eval-2"
            />
        </div>
        <!-- <div class="w-full sm:w-80">
            <BaseListbox
                v-model="tradeType"
                :options="tradeActions"
                :placeholder="$t('public.filters_placeholder')"
                class="w-full"
            />
        </div>
        <div class="w-full sm:w-80">
            <Combobox
                :load-options="loadSymbols"
                v-model="type"
                multiple
                :placeholder="$t('public.filter_symbols')"
            />
        </div> -->
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
                    {{$t('public.affiliate')}}
                </th>
                <th scope="col" class="p-3 text-center">
                    {{$t('public.rebate')}} ($)
                </th>
                <th scope="col" class="p-3 text-center">
                    {{$t('public.volume')}}
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
                    <div class="grid">
                        <span>{{ history.of_user.username }} ({{ history.meta_login }})</span>
                        <span class="dark:text-gray-400">{{ history.of_user.email }}</span>
                    </div>
                </td>
                <td class="p-3 font-semibold text-center">
                    {{ formatAmount(history.rebate) }}
                </td>
                <td class="p-3 font-semibold text-center">
                    {{ formatAmount(history.volume) }}
                </td>
            </tr>
            </tbody>
        </table>
    </div>
    <div class="flex justify-center mt-4" v-if="!isLoading">
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
