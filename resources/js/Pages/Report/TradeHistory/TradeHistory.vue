<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import Button from "@/Components/Button.vue";
import Input from "@/Components/Input.vue";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import { SearchIcon, ArrowLeftIcon, ArrowRightIcon, RefreshIcon } from "@heroicons/vue/outline";
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import BaseListbox from "@/Components/BaseListbox.vue";
import { ref, watch, watchEffect } from "vue";
import { usePage } from "@inertiajs/vue3";
import Loading from "@/Components/Loading.vue";
import Badge from "@/Components/Badge.vue";
import { TailwindPagination } from "laravel-vue-pagination";
import { transactionFormat } from "@/Composables/index.js";
import debounce from "lodash/debounce.js";
import Modal from "@/Components/Modal.vue";
import Combobox from "@/Components/Combobox.vue";
import toast from "@/Composables/toast.js";
import { trans } from 'laravel-vue-i18n';
import NoData from "@/Components/NoData.vue";
import TanStackTable from "@/Components/TanStackTable.vue";
import {Users01Icon, CurrencyDollarCircleIcon} from "@/Components/Icons/outline.jsx";
import { DashboardIcon, CoinsHandIcon, ReportIcon } from '@/Components/Icons/outline'

const props = defineProps({
    tradingAccounts: Array,
})
const { formatDateTime, formatAmount } = transactionFormat();
const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});
const tradingAccount = props.tradingAccounts[0] && props.tradingAccounts.length > 0  ? ref(props.tradingAccounts[0].value)  : null;
const tradeHistories = ref({ data: [] });
const totalProfit = ref('');
const totalTradeLot = ref('');
const sorting = ref();
const type = ref();
const tradeType = ref('');
const date = ref('');
const pageSize = ref(10);
const action = ref('');
const currentPage = ref(1);
const currentLocale = ref(usePage().props.locale);
const symbols = ref();

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
        getResults(currentPageValue, pageSize.value, tradingAccount.value);
    } else {
        getResults(currentPageValue, pageSize.value, tradingAccount.value);
    }
});

watch(
    [sorting, pageSize],
    ([sortingValue, pageSizeValue]) => {
        getResults(1, pageSizeValue, tradingAccount.value, type.value, tradeType.value, date.value, sorting.value);
    }
);

if (tradingAccount !== null) {
    watch(
        [tradingAccount, type, tradeType, date],
        debounce(([tradingAccountValue, typeValue, tradeTypeValue, dateValue]) => {
            const typeStrings = typeValue ? typeValue.map(item => item.value) : null;
            getResults(1, pageSize.value, tradingAccountValue, typeStrings, tradeTypeValue, dateValue, sorting.value);
        }, 300)
    );
}

const getResults = async (page = 1, paginate = 10, tradingAccount = '', type = null, tradeType = '', date = '', columnName = sorting.value) => {
    try {
        let url = `/report/getTradeHistories?page=${page}`;

        if (paginate) {
            url += `&paginate=${paginate}`;
        }

        if (tradingAccount) {
            url += `&meta_login=${tradingAccount}`;
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
        tradeHistories.value = response.data.tradeHistories; // Assuming the data key holds the trade histories
        totalProfit.value = response.data.totalProfit;
        totalTradeLot.value = response.data.totalTradeLot;
    } catch (error) {
        console.error(error);
    }
};
// Call getResults initially
if (tradingAccount) {
  getResults(1, 10, tradingAccount.value);
}

const columns = [
    {
        accessorKey: 'time_close',
        header: 'date',
        cell: info => formatDateTime(info.getValue()),
    },
    {
        accessorKey: 'meta_login',
        header: 'meta_login',
        enableSorting: false,
        cell: info => info.getValue(),
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

// const clearFilter = () => {
//     tradingAccount.value = props.tradingAccounts[0].value;
//     type.value = '';
//     tradeType.value = '';
//     date.value = '';
// }
if (tradingAccount) {
    const symbols = function loadSymbols(query, setOptions) {
        watchEffect(() => {
            fetch(`/trading/getTradingSymbols?meta_login=${tradingAccount.value}&query=${query}`)
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
        });
    }
}
</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar.trade_history')">
        <template #header>
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    {{ $t('public.sidebar.trade_history') }}
                </h2>
            </div>
        </template>

        <div class="grid grid-cols-1 sm:grid-cols-2 w-full gap-4">
            <div class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                <div class="flex flex-col gap-4">
                    <div>
                        {{ $t('public.total_profit') }}
                    </div>
                    <div class="text-2xl font-bold">
                        <span v-if="totalProfit !== null">
                           $ {{ formatAmount(totalProfit) }}
                        </span>
                        <span v-else>
                            {{ $t('public.loading') }}
                        </span>
                    </div>
                </div>
                <div class="rounded-full flex items-center justify-center w-14 h-14 bg-success-200">
                    <CurrencyDollarCircleIcon class="text-success-500 w-8 h-8" />
                </div>
            </div>
            <div class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                <div class="flex flex-col gap-4">
                    <div>
                        {{ $t('public.total_trade_lots') }}
                    </div>
                    <div class="text-2xl font-bold">
                        <span v-if="totalTradeLot !== null">
                            {{ formatAmount(totalTradeLot) }}
                        </span>
                        <span v-else>
                            {{ $t('public.loading') }}
                        </span>
                    </div>
                </div>
                <div class="rounded-full flex items-center justify-center w-14 h-14 bg-primary-200">
                    <CoinsHandIcon class="text-primary-500 w-8 h-8" />
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-5 items-start self-stretch my-8">
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 w-full">
                <div class="w-full">
                    <BaseListbox
                        v-model="tradingAccount"
                        :options="props.tradingAccounts"
                        :placeholder="$t('public.filters_placeholder')"
                        class="rounded-lg text-base text-black w-full dark:text-white dark:bg-gray-800"
                    />
                </div>
                <div class="w-full">
                    <vue-tailwind-datepicker
                        :placeholder="$t('public.date')"
                        :formatter="formatter"
                        separator=" - "
                        v-model="date"
                        input-classes="py-2.5 w-full rounded-lg dark:placeholder:text-gray-500 focus:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500 bg-white dark:bg-gray-800 dark:text-white border border-gray-300 dark:border-gray-800"
                    />
                </div>
                <div class="w-full">
                    <BaseListbox
                        v-model="tradeType"
                        :options="tradeActions"
                        :placeholder="$t('public.filters_placeholder')"
                        class="rounded-lg text-base text-black w-full dark:text-white dark:bg-gray-800"
                    />
                </div>
                <div class="w-full">
                    <Combobox
                        :load-options="symbols"
                        v-model="type"
                        multiple
                        :placeholder="$t('public.filter_symbols')"
                    />
                </div>
            </div>
            <!-- <div class="flex justify-end gap-4 items-center w-full">
                <Button
                    type="button"
                    variant="secondary"
                    @click="clearFilter"
                >
                    {{ $t('public.clear') }}
                </Button>
            </div> -->
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
    </AuthenticatedLayout>
</template>
