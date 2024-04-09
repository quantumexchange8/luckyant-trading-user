<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import {computed, h, ref, watch} from "vue";
import debounce from "lodash/debounce.js";
import Badge from "@/Components/Badge.vue";
import TanStackTable from "@/Components/TanStackTable.vue";
import {transactionFormat} from "@/Composables/index.js";
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import Input from "@/Components/Input.vue";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import {ChevronRightIcon, SearchIcon} from "@heroicons/vue/outline";
import BaseListbox from "@/Components/BaseListbox.vue";
import StatusBadge from "@/Components/StatusBadge.vue";
import {usePage} from "@inertiajs/vue3";
import {trans} from "laravel-vue-i18n";

const copyTradeTransactions = ref({data: []});
const sorting = ref();
const search = ref('');
const date = ref('');
const pageSize = ref(10);
const action = ref('');
const currentPage = ref(1);
const currentLocale = ref(usePage().props.locale);

const { formatDateTime, formatAmount } = transactionFormat();
const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});

const pageSizes = [
    {value: 5, label: 5 },
    {value: 10, label: 10 },
    {value: 20, label: 20 },
    {value: 50, label: 50 },
    {value: 100, label: 100 },
]

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
        getResults(1, pageSizeValue, search.value, date.value, sortingValue);
    }
);

watch(
    [search, date],
    debounce(([searchValue, dateValue]) => {
        getResults(1, pageSize.value, searchValue, dateValue, sorting.value);
    }, 300)
);

const getResults = async (page = 1, paginate = 10, filterSearch = search.value,filterDate = date.value, columnName = sorting.value) => {
    // isLoading.value = true
    try {
        let url = `/trading/getSubscriptionHistories?page=${page}`;

        if (paginate) {
            url += `&paginate=${paginate}`;
        }

        if (filterSearch) {
            url += `&search=${filterSearch}`;
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
        copyTradeTransactions.value = response.data;
    } catch (error) {
        console.error(error);
    } finally {
        // isLoading.value = false
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
        accessorKey: 'meta_login',
        header: 'live_account',
    },
    {
        accessorKey: currentLocale.value === 'cn' ? ('master.trading_user.company' === null ? 'master.trading_user.company' : 'master.trading_user.name') : 'master.trading_user.name',
        header: 'master',
        enableSorting: false,
    },
    {
        accessorKey: 'master.meta_login',
        header: 'account_no',
    },
    {
        accessorKey: 'subscription_number',
        header: 'subscription_number',
    },
    {
        accessorKey: 'meta_balance',
        header: 'amount',
        cell: info => '$ ' + formatAmount(info.getValue()),
    },
    {
        accessorKey: 'subscription_period',
        header: 'roi_period',
        cell: info => info.getValue() + ' ' + trans('public.days'),
    },
    {
        accessorKey: 'status',
        header: 'status',
        enableSorting: false,
        cell: ({ row }) => h(StatusBadge, {value: row.original.status}),
    },
];
</script>

<template>
    <AuthenticatedLayout :title="$t('public.subscription_history')">
        <template #header>
            <div class="flex gap-2 items-center">
                <h2 class="text-xl font-semibold leading-tight">
                    {{ $t('public.subscriptions') }}
                </h2>
                <ChevronRightIcon aria-hidden="true" class="w-5 h-5" />
                <div class="flex gap-1 text-xl text-primary-500 font-semibold leading-tight">
                    <h2>
                        {{ $t('public.subscription_history') }}
                    </h2>
                </div>
            </div>
        </template>

        <div class="p-5 my-5 bg-white overflow-hidden md:overflow-visible rounded-xl shadow-md dark:bg-gray-900">
            <div class="flex flex-col sm:flex-row gap-2 sm:justify-between sm:items-center w-full">
                <div class="flex flex-col md:flex-row gap-4 w-full sm:w-1/2">
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
                                v-model="search" />
                        </InputIconWrapper>
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
            </div>
            <div v-if="copyTradeTransactions.data.length > 0">
                <TanStackTable
                    :data="copyTradeTransactions"
                    :columns="columns"
                    @update:sorting="sorting = $event"
                    @update:action="action = $event"
                    @update:currentPage="currentPage = $event"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
