<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import BaseListbox from "@/Components/BaseListbox.vue";
import {h, ref, watch} from "vue";
import { transactionFormat } from "@/Composables/index.js";
import debounce from "lodash/debounce.js";
import NoData from "@/Components/NoData.vue";
import TanStackTable from "@/Components/TanStackTable.vue";
import {CreditCardUpIcon, CreditCardDownIcon} from "@/Components/Icons/outline.jsx";
import {ChevronRightIcon, SearchIcon} from "@heroicons/vue/outline";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import Input from "@/Components/Input.vue";
import {trans} from "laravel-vue-i18n";
import StatusBadge from "@/Components/StatusBadge.vue";
import TransactionAmount from "@/Pages/Transaction/Partials/TransactionAmount.vue";
import Action from "@/Pages/Transaction/Partials/Action.vue";
import Button from "@/Components/Button.vue";

const props = defineProps({
    transactionTypes: Array
})

const { formatDateTime, formatAmount } = transactionFormat();
const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});

const totalBalanceIn = ref(null);
const totalBalanceOut = ref(null);
const transactionHistories = ref({data: []});
const sorting = ref();
const search = ref('');
const type = ref('');
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
        let url = `/account_info/getTransactionData?page=${page}`;

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
        transactionHistories.value = response.data.transactionHistories;
        totalBalanceIn.value = response.data.totalBalanceIn;
        totalBalanceOut.value = response.data.totalBalanceOut;
    } catch (error) {
        console.error(error);
    }
}

getResults()

const getCellValue = (row, walletKey, metaLoginKey, trans) => {
    const wallet = row[walletKey];
    const metaLogin = row[metaLoginKey];

    if (wallet) {
        return trans('public.' + wallet.type);
    } else {
        return metaLogin ? `${trans('public.account_no')} - ${metaLogin.meta_login}` : '-';
    }
};

const columns = [
    {
        accessorKey: 'created_at',
        header: 'date',
        cell: info => formatDateTime(info.getValue()),
    },
    {
        accessorKey: 'transaction_type',
        header: 'transaction_type',
        enableSorting: false,
        cell: info => trans('public.' + info.getValue().replace(/([A-Z])/g, '_$1').toLowerCase().replace(/^_/, ''))
    },
    {
        accessorFn: row => row.from_wallet?.type || row.from_meta_login?.meta_login,
        header: 'from',
        enableSorting: false,
        cell: info => getCellValue(info.row.original, 'from_wallet', 'from_meta_login', trans)
    },
    {
        accessorFn: row => row.to_wallet?.type || row.to_meta_login?.meta_login,
        header: 'to',
        enableSorting: false,
        cell: info => getCellValue(info.row.original, 'to_wallet', 'to_meta_login', trans)
    },
    {
        accessorKey: 'transaction_number',
        header: 'transaction_no',
        enableSorting: false,
        cell: info => info.getValue() ?? '-',
    },
    {
        accessorKey: 'amount',
        header: 'amount',
        cell: ({ row }) => {
            return h(TransactionAmount, {
                transaction: row.original,
            });
        }
    },
    {
        accessorKey: 'status',
        header: 'status',
        enableSorting: false,
        cell: ({ row }) => {
            return h(StatusBadge, { value: row.original.status });
        }
    },
    {
        accessorKey: 'status',
        header: 'status',
        enableSorting: false,
        cell: ({ row }) => {
            return h(Action, { walletHistory: row.original });
        }
    },
];

const clearFilter = () => {
    search.value = '';
    type.value = '';
    date.value = '';
}
</script>

<template>
    <AuthenticatedLayout :title="$t('public.transaction_history')">
        <template #header>
            <div class="flex gap-2 items-center">
                <h2 class="text-xl font-semibold leading-tight">
                    <a class="hover:text-primary-500 dark:hover:text-primary-500" :href="route('account_info.account_info')">{{ $t('public.sidebar.account_info') }}</a>
                </h2>
                <ChevronRightIcon aria-hidden="true" class="w-5 h-5" />
                <div class="flex gap-1 text-xl text-primary-500 font-semibold leading-tight">
                    <h2>
                        {{ $t('public.transaction_history') }}
                    </h2>
                </div>
            </div>
        </template>

        <div class="grid grid-cols-1 sm:grid-cols-2 w-full gap-5">
            <div
                class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                <div class="flex flex-col gap-4">
                    <div>
                        {{ $t('public.balance_in') }}
                    </div>
                    <div class="text-2xl font-bold">
                        <span v-if="totalBalanceIn !== null">
                            $ {{ totalBalanceIn !== '' ? formatAmount(totalBalanceIn) : '0' }}
                        </span>
                        <span v-else>
                            {{ $t('public.loading') }}
                        </span>
                    </div>
                </div>
                <div class="rounded-full flex items-center justify-center w-14 h-14 bg-success-200">
                    <CreditCardUpIcon class="text-success-500 w-8 h-8"/>
                </div>
            </div>
            <div
                class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                <div class="flex flex-col gap-4">
                    <div>
                        {{ $t('public.balance_out') }}
                    </div>
                    <div class="text-2xl font-bold">
                        <span v-if="totalBalanceOut !== null">
                            $ {{ totalBalanceOut !== '' ? formatAmount(totalBalanceOut) : '0' }}
                        </span>
                        <span v-else>
                            {{ $t('public.loading') }}
                        </span>
                    </div>
                </div>
                <div class="rounded-full flex items-center justify-center w-14 h-14 bg-error-200">
                    <CreditCardDownIcon class="text-error-500 w-8 h-8"/>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-5 items-start self-stretch my-8">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 w-full">
                <div class="w-full">
                    <InputIconWrapper class="w-full">
                        <template #icon>
                            <SearchIcon aria-hidden="true" class="w-5 h-5"/>
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
                        :options="transactionTypes"
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
            <div class="flex justify-end gap-4 items-center w-full">
                <Button
                    type="button"
                    variant="secondary"
                    @click="clearFilter"
                >
                    {{ $t('public.clear') }}
                </Button>
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
                v-if="transactionHistories.data.length === 0"
                class="w-full flex items-center justify-center"
            >
                <NoData/>
            </div>
            <div v-else>
                <TanStackTable
                    :data="transactionHistories"
                    :columns="columns"
                    @update:sorting="sorting = $event"
                    @update:action="action = $event"
                    @update:currentPage="currentPage = $event"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
