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
import NoData from "@/Components/NoData.vue";
import TanStackTable from "@/Components/TanStackTable.vue";

const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});

const props = defineProps({
    meta_login: Number
})

const isLoading = ref(false);
const date = ref('');
const search = ref('');
const refresh = ref(false);
const type = ref();
const tradeType = ref();
const tradeHistories = ref({data: []})
const action = ref('');
const sorting = ref();
const currentPage = ref(1)
const pageSize = ref(10);
const { formatDateTime, formatAmount } = transactionFormat();

const tradeActions = [
    {value: '', label:"All"},
    {value: 'BUY', label:"BUY"},
    {value: 'SELL', label:"SELL"},
];

const pageSizes = [
    { value: 5, label: 5 },
    { value: 10, label: 10 },
    { value: 20, label: 20 },
    { value: 50, label: 50 },
    { value: 100, label: 100 },
];

watch([currentPage, action], ([currentPageValue, newAction]) => {
    if (newAction === 'goToFirstPage' || newAction === 'goToLastPage') {
        getResults(currentPageValue, pageSize.value);
    } else {
        getResults(currentPageValue, pageSize.value);
    }
});

watch(
    [sorting, pageSize],
    ([sortingValue, pageSizeValue]) => {
        getResults(1, pageSizeValue, type.value, tradeType.value, date.value, sorting.value);
    }
);

watch(
    [type, tradeType, date],
    debounce(([typeValue, tradeTypeValue, dateValue]) => {
        const typeStrings = typeValue ? typeValue.map(item => item.value) : null;
        getResults(1, pageSize.value, typeStrings, tradeTypeValue, dateValue, sorting.value);
    }, 300)
);

const getResults = async (page = 1, paginate = 10, type = null, tradeType = '', date = '', columnName = sorting.value) => {
    // isLoading.value = true
    try {
        let url = `/trading/getTradeHistories/${props.meta_login}?page=${page}`;

        if (paginate) {
            url += `&paginate=${paginate}`;
        }

        if (type) {
            url += `&type=${type}`;
        }

        if (tradeType) {
            url += `&tradeType=${tradeType}`;
        }

        if (date) {
            url += `&date=${date}`;
        }

        if (columnName) {
            // Convert the object to JSON and encode it to send as a query parameter
            const encodedColumnName = encodeURIComponent(JSON.stringify(columnName));
            url += `&columnName=${encodedColumnName}`;
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

const columns = [
    {
        accessorKey: 'time_close',
        header: 'date',
        cell: info => formatDateTime(info.getValue()),
    },
    {
        accessorKey: 'symbol',
        header: 'symbol',
        cell: info => info.getValue(),
    },
    {
        accessorKey: 'ticket',
        header: 'ticket_number',
        cell: info => info.getValue(),
    },
    {
        accessorKey: 'trade_type',
        header: 'action',
        cell: info => info.getValue(),
    },
    {
        accessorKey: 'volume',
        header: 'volume',
        cell: info => formatAmount(info.getValue()),
    },
    {
        accessorKey: 'price_open',
        header: 'open_price',
        cell: info => '$ ' + formatAmount(info.getValue()),
    },
    {
        accessorKey: 'price_close',
        header: 'close_price',
        cell: info => '$ ' + formatAmount(info.getValue()),
    },
    {
        accessorKey: 'trade_swap',
        header: 'swap',
        cell: info => formatAmount(info.getValue()),
    },
    {
        accessorKey: 'trade_profit',
        header: 'profit',
        cell: info => '$ ' + formatAmount(info.getValue()),
    },
    {
        accessorKey: 'trade_profit_pct',
        header: 'change',
        cell: info => formatAmount(info.getValue()) + ' %',
    },
];

// watch(
//     [type, date, tradeType],
//     debounce(([typeValue, dateValue, tradeType]) => {
//         const typeStrings = typeValue ? typeValue.map(item => item.value) : null;
//         getResults(1, typeStrings, dateValue, tradeType);
//     }, 300)
// );

// const handlePageChange = (newPage) => {
//     if (newPage >= 1) {
//         currentPage.value = newPage;
//         const typeStrings = type.value ? type.value.map(item => item.value) : null;

//         getResults(currentPage.value, typeStrings, date.value, tradeType.value);
//     }
// };

// const refreshHistory = () => {
//     const typeStrings = type.value ? type.value.map(item => item.value) : null;

//     getResults(1, typeStrings, date.value, tradeType.value);

//     toast.add({
//         message: wTrans('public.successfully_refreshed'),
//     });
// }

// const paginationClass = [
//     'bg-transparent border-0 text-gray-600 dark:text-gray-400 dark:enabled:hover:text-white'
// ];

// const paginationActiveClass = [
//     'border dark:border-gray-600 dark:bg-gray-600 rounded-full text-primary-500 dark:text-primary-300'
// ];

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
        <!-- <RefreshIcon
            :class="{ 'animate-spin': isLoading }"
            class="flex-shrink-0 w-5 h-5 cursor-pointer dark:text-white"
            aria-hidden="true"
            @click="refreshHistory"
        /> -->
    </div>

    <div class="flex flex-wrap gap-3 w-full justify-end items-center sm:flex-nowrap">
        <div class="w-full sm:w-80">
            <vue-tailwind-datepicker
                :placeholder="$t('public.date_placeholder')"
                :formatter="formatter"
                separator=" - "
                v-model="date"
                input-classes="py-2.5 w-full rounded-lg dark:placeholder:text-gray-500 focus:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500 bg-white dark:bg-gray-800 dark:text-white border border-gray-300 dark:border-gray-800"
            />
        </div>
        <div class="w-full sm:w-80">
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
        </div>
    </div>

    <div class="p-5 my-8 bg-white overflow-hidden md:overflow-visible rounded-xl shadow-md dark:bg-gray-900">
            <div class="flex justify-end items-center gap-2">
                <div class="text-sm">
                    {{ $t('public.size') }}
                </div>
                <div>
                    <BaseListbox
                        :options="pageSizes"
                        v-model="pageSize"
                    />
                </div>
            </div>
            <div
                v-if="tradeHistories.data.length === 0"
                class="w-full flex items-center justify-center"
            >
                <NoData />
            </div>
            <div v-else>
                <TanStackTable
                    :data="tradeHistories"
                    :columns="columns"
                    @update:sorting="sorting = $event"
                    @update:action="action = $event"
                    @update:currentPage="currentPage = $event"
                />
            </div>
        </div>
</template>
