<script setup>
import Button from "@/Components/Button.vue";
import Input from "@/Components/Input.vue";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import {ArrowLeftIcon, ArrowRightIcon, RefreshIcon} from "@heroicons/vue/outline";
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
    meta_login: Number
})

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
const type = ref();
const tradeHistories = ref({data: []})
const currentPage = ref(1)
const { formatDateTime, formatAmount } = transactionFormat();

const getResults = async (page = 1, type = null, date = '') => {
    isLoading.value = true
    try {
        let url = `/trading/getTradeHistories/${props.meta_login}?page=${page}`;

        if (type) {
            url += `&type=${type}`;
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
    [type, date],
    debounce(([typeValue, dateValue]) => {
        const typeStrings = typeValue ? typeValue.map(item => item.value) : null;
        getResults(1, typeStrings, dateValue);
    }, 300)
);

const handlePageChange = (newPage) => {
    if (newPage >= 1) {
        currentPage.value = newPage;
        const typeStrings = type.value ? type.value.map(item => item.value) : null;

        getResults(currentPage.value, typeStrings, date.value);
    }
};

const refreshHistory = () => {
    const typeStrings = type.value ? type.value.map(item => item.value) : null;

    getResults(1, typeStrings, date.value);

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

function loadSymbols(query, setOptions) {
    fetch(`/trading/getTradingSymbols?meta_login=${props.meta_login}&query=` + query)
        .then(response => response.json())
        .then(results => {
            setOptions(
                results.map(history => {
                    return {
                        value: history.symbol,
                        label: history.symbol,
                    }
                })
            )
        });
}
</script>

<template>
    <div class="flex justify-between mb-3">
        <h4 class="font-semibold text-lg dark:text-white">{{$t('public.trade_history')}}</h4>
        <RefreshIcon
            :class="{ 'animate-spin': isLoading }"
            class="flex-shrink-0 w-5 h-5 cursor-pointer dark:text-white"
            aria-hidden="true"
            @click="refreshHistory"
        />
    </div>

    <div class="flex flex-wrap gap-3 w-full justify-end items-center sm:flex-nowrap">
        <div class="w-full sm:w-80">
            <vue-tailwind-datepicker
                :placeholder="$t('public.date_placeholder')"
                :formatter="formatter"
                separator=" - "
                v-model="date"
                input-classes="py-2.5 w-full rounded-lg dark:placeholder:text-gray-500 focus:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500 bg-white dark:bg-gray-700 dark:text-white border border-gray-300 dark:border-dark-eval-2"
            />
        </div>
        <div class="w-full sm:w-80">
            <Combobox
                :load-options="loadSymbols"
                v-model="type"
                multiple
                :placeholder="$t('public.filter_symbols')"
            />
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
                    {{$t('public.symbol')}}
                </th>
                <th scope="col" class="p-3">
                    {{$t('public.date')}}
                </th>
                <th scope="col" class="p-3">
                    {{$t('public.profit')}} ($)
                </th>
                <th scope="col" class="p-3">
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
                    {{ history.symbol }}
                </td>
                <td class="p-3">
                    <div class="inline-flex items-center gap-2">
                        {{ formatDateTime(history.time_close) }}
                    </div>
                </td>
                <td class="p-3 font-semibold">
                    <div :class="{ 'text-error-500': history.closed_profit < 0, 'text-success-500': history.closed_profit > 0 }">
                        {{ history.closed_profit ? formatAmount(history.closed_profit) : '0.00' }}
                    </div>
                </td>
                <td class="p-3">
                    {{ history.volume }}
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
            class="shadow-none"
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