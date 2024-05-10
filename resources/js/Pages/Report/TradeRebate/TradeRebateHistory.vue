<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import BaseListbox from "@/Components/BaseListbox.vue";
import {h, ref, watch} from "vue";
import { transactionFormat } from "@/Composables/index.js";
import debounce from "lodash/debounce.js";
import NoData from "@/Components/NoData.vue";
import TanStackTable from "@/Components/TanStackTable.vue";
import {CurrencyDollarCircleIcon} from "@/Components/Icons/outline.jsx";
import { CoinsHandIcon } from '@/Components/Icons/outline'
import {SearchIcon} from "@heroicons/vue/outline";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import Input from "@/Components/Input.vue";
import StatusBadge from "@/Components/StatusBadge.vue";

const props = defineProps({
    rebateTypes: Array
})

const { formatDateTime, formatAmount } = transactionFormat();
const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});

const totalRebateAmount = ref(null);
const totalAffiliateRebate = ref(null);
const totalPersonalRebate = ref(null);
const totalTradeLots = ref(null);
const tradeRebates = ref({data: []});
const sorting = ref();
const search = ref('');
const type = ref('affiliate');
const date = ref('');
const pageSize = ref(10);
const action = ref('');
const currentPage = ref(1);

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
        getResults(1, pageSizeValue, search.value, type.value, date.value, sortingValue);
    }
);

watch(
    [search, type, date],
    debounce(([searchValue, typeValue, dateValue]) => {
        getResults(1, pageSize.value, searchValue, typeValue, dateValue, sorting.value);
    }, 300)
);

const getResults = async (page = 1, paginate = 10, filterSearch = search.value, filterType = type.value, filterDate = date.value, columnName = sorting.value) => {
    // isLoading.value = true
    try {
        let url = `/report/getTradeRebateHistories?page=${page}`;

        if (paginate) {
            url += `&paginate=${paginate}`;
        }

        if (filterSearch) {
            url += `&search=${filterSearch}`;
        }

        if (filterType) {
            url += `&type=${filterType}`;
        }

        if (filterDate) {
            url += `&date=${filterDate}`;
        }

        if (columnName) {
            // Convert the object to JSON and encode it to send as a query parameter
            const encodedColumnName = encodeURIComponent(JSON.stringify(columnName));
            url += `&columnName=${encodedColumnName}`;
        }

        const response = await axios.get(url);
        tradeRebates.value = response.data.tradeRebates;
        totalRebateAmount.value = response.data.totalRebateAmount;
        totalPersonalRebate.value = response.data.totalPersonalRebate;
        totalAffiliateRebate.value = response.data.totalAffiliateRebate;
        totalTradeLots.value = response.data.totalTradeLots;
    } catch (error) {
        console.error(error);
    }
}

getResults()

const columns = [
    {
        accessorKey: 'created_at',
        header: 'date',
        cell: info => formatDateTime(info.getValue()),
    },
    {
        accessorKey: 'of_user.username',
        header: 'username',
        enableSorting: false,
        cell: info => info.getValue(),
    },
    {
        accessorKey: 'meta_login',
        header: 'account_number',
        cell: info => info.getValue(),
    },
    {
        accessorKey: 'volume',
        header: 'volume',
        cell: info => formatAmount(info.getValue()),
    },
    {
        accessorKey: 'rebate',
        header: 'rebate',
        cell: info => '$ ' + formatAmount(info.getValue()),
    },
    {
        accessorKey: 'status',
        header: 'status',
        enableSorting: false,
        cell: ({ row }) => h(StatusBadge, {value: row.original.status}),
    },
];

// const clearFilter = () => {
//     tradingAccount.value = props.tradingAccounts[0].value;
//     type.value = '';
//     tradeType.value = '';
//     date.value = '';
// }
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
                        {{ $t('public.total_rebate_amount') }}
                    </div>
                    <div class="text-2xl font-bold">
                        <span v-if="totalRebateAmount !== null">
                            $ {{ totalRebateAmount !== '' ? formatAmount(totalRebateAmount) : '0' }}
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
                        <span v-if="totalTradeLots !== null">
                            {{ totalTradeLots !== '' ? formatAmount(totalTradeLots) : '0' }}
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
            <div class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                <div class="flex flex-col gap-4">
                    <div>
                        {{ $t('public.affiliate') }}
                    </div>
                    <div class="text-2xl font-bold">
                        <span v-if="totalAffiliateRebate !== null">
                            $ {{ totalAffiliateRebate !== '' ? formatAmount(totalAffiliateRebate) : '0' }}
                        </span>
                        <span v-else>
                            {{ $t('public.loading') }}
                        </span>
                    </div>
                </div>
                <div class="rounded-full flex items-center justify-center w-14 h-14 bg-purple-200">
                    <CurrencyDollarCircleIcon class="text-purple-500 w-8 h-8" />
                </div>
            </div>
            <div class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                <div class="flex flex-col gap-4">
                    <div>
                        {{ $t('public.personal') }}
                    </div>
                    <div class="text-2xl font-bold">
                        <span v-if="totalPersonalRebate !== null">
                            $ {{ totalPersonalRebate !== '' ? formatAmount(totalPersonalRebate) : '0' }}
                        </span>
                        <span v-else>
                            {{ $t('public.loading') }}
                        </span>
                    </div>
                </div>
                <div class="rounded-full flex items-center justify-center w-14 h-14 bg-gray-200">
                    <CurrencyDollarCircleIcon class="text-gray-500 w-8 h-8" />
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-5 items-start self-stretch my-8">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 w-full">
                <div class="w-full">
                    <InputIconWrapper class="w-full">
                        <template #icon>
                            <SearchIcon aria-hidden="true" class="w-5 h-5" />
                        </template>
                        <Input
                            withIcon
                            id="search"
                            type="text"
                            class="block w-full"
                            :placeholder="$t('public.search')"
                            v-model="search"
                        />
                    </InputIconWrapper>
                </div>
                <div class="w-full">
                    <BaseListbox
                        v-model="type"
                        :options="rebateTypes"
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
                v-if="tradeRebates.data.length === 0"
                class="w-full flex items-center justify-center"
            >
                <NoData />
            </div>
            <div v-else>
                <TanStackTable
                    :data="tradeRebates"
                    :columns="columns"
                    @update:sorting="sorting = $event"
                    @update:action="action = $event"
                    @update:currentPage="currentPage = $event"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
