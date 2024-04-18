<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import Button from "@/Components/Button.vue";
import Input from "@/Components/Input.vue";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import {SearchIcon, ArrowLeftIcon, ArrowRightIcon, RefreshIcon} from "@heroicons/vue/outline";
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import BaseListbox from "@/Components/BaseListbox.vue";
import {h, ref, watch} from "vue";
import {usePage} from "@inertiajs/vue3";
import {transactionFormat} from "@/Composables/index.js";
import debounce from "lodash/debounce.js";
import Modal from "@/Components/Modal.vue";
import Combobox from "@/Components/Combobox.vue";
import toast from "@/Composables/toast.js";
import {trans} from 'laravel-vue-i18n';
import TanStackTable from "@/Components/TanStackTable.vue";
import NoData from "@/Components/NoData.vue";
import StatusBadge from "@/Components/StatusBadge.vue";
import {Users01Icon, CurrencyDollarCircleIcon} from "@/Components/Icons/outline.jsx";

const props = defineProps({
    rankLists: Array,
    countries: Array,
})

const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});

const pageSizes = [
    {value: 5, label: 5},
    {value: 10, label: 10},
    {value: 20, label: 20},
    {value: 50, label: 50},
    {value: 100, label: 100},
]

const totalAccounts = ref(null);
const totalAmount = ref(null);
const date = ref('');
const search = ref('');
const rank = ref('');
const country = ref();
const refresh = ref(false);
const affiliateCopyTradeTransactions = ref({data: []});
const sorting = ref();
const pageSize = ref(10);
const action = ref('');
const currentPage = ref(1);
const {formatDateTime, formatAmount} = transactionFormat();
const currentLocale = ref(usePage().props.locale);

const getResults = async (page = 1, paginate = 10, filterSearch = search.value, filterDate = date.value, filterRank = rank.value, filterCountry = country.value, columnName = sorting.value) => {
    try {
        let url = `/referral/affiliateSubscriptionData?page=${page}`;

        if (paginate) {
            url += `&paginate=${paginate}`;
        }

        if (filterSearch) {
            url += `&search=${filterSearch}`;
        }

        if (filterDate) {
            url += `&date=${filterDate}`;
        }

        if (filterRank) {
            url += `&rank=${filterRank}`;
        }

        if (filterCountry) {
            url += `&country=${filterCountry.value}`;
        }

        if (columnName) {
            // Convert the object to JSON and encode it to send as a query parameter
            const encodedColumnName = encodeURIComponent(JSON.stringify(columnName));
            url += `&columnName=${encodedColumnName}`;
        }

        const response = await axios.get(url);
        affiliateCopyTradeTransactions.value = response.data.affiliateCopyTradeTransactions;
        totalAccounts.value = response.data.totalAccounts;
        totalAmount.value = response.data.totalAmount;

    } catch (error) {
        console.error(error);
    }
}

getResults();

const columns = [
    {
        accessorKey: 'created_at',
        header: 'date',
        cell: info => formatDateTime(info.getValue()),
    },
    {
        accessorKey: 'user.username',
        header: 'username',
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
        accessorKey: 'master_meta_login',
        header: 'account_no',
    },
    {
        accessorKey: 'amount',
        header: 'amount',
        cell: info => '$ ' + formatAmount(info.getValue()),
    },
    {
        accessorKey: 'status',
        header: 'status',
        enableSorting: false,
        cell: ({ row }) => h(StatusBadge, {value: row.original.status}),
    },
];

const clearFilter = () => {
    search.value = '';
    date.value = '';
    rank.value = '';
}

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
        getResults(1, pageSizeValue, search.value, date.value, rank.value, country.value, sortingValue);
    }
);

watch(
    [search, date, rank, country],
    debounce(([searchValue, dateValue, rankValue, countryValue]) => {
        getResults(1, pageSize.value, searchValue, dateValue, rankValue, countryValue, sorting.value);
    }, 300)
);

function loadCountries(query, setOptions) {
    fetch('/referral/getAllCountries?query=' + query)
        .then(response => response.json())
        .then(results => {
            setOptions(
                results.map(country => {
                    return {
                        value: country.id,
                        label: country.name,
                    }
                })
            )
        });
}
</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar.affiliate_subscriptions')">
        <template #header>
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    {{ $t('public.sidebar.affiliate_subscriptions') }}
                </h2>
            </div>
        </template>

        <div class="grid grid-cols-1 sm:grid-cols-2 w-full gap-4">
            <div
                class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                <div class="flex flex-col gap-4">
                    <div>
                        {{ $t('public.total_affiliate') }}
                    </div>
                    <div class="text-2xl font-bold">
                        <span v-if="totalAccounts !== null">
                            {{ totalAccounts }}
                        </span>
                        <span v-else>
                            {{ $t('public.loading') }}
                        </span>
                    </div>
                </div>
                <div class="rounded-full flex items-center justify-center w-14 h-14 bg-primary-200">
                    <Users01Icon class="text-primary-500 w-8 h-8"/>
                </div>
            </div>
            <div
                class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                <div class="flex flex-col gap-4">
                    <div>
                        {{ $t('public.total_balance') }}
                    </div>
                    <div class="text-2xl font-bold">
                        <span v-if="totalAmount !== null">
                            $ {{ formatAmount(totalAmount) }}
                        </span>
                        <span v-else>
                            {{ $t('public.loading') }}
                        </span>
                    </div>
                </div>
                <div class="rounded-full flex items-center justify-center w-14 h-14 bg-success-200">
                    <CurrencyDollarCircleIcon class="text-success-500 w-8 h-8"/>
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-5 items-start self-stretch my-8">
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 w-full">
                <div class="w-full">
                    <InputIconWrapper>
                        <template #icon>
                            <SearchIcon aria-hidden="true" class="w-5 h-5"/>
                        </template>
                        <Input
                            withIcon
                            id="search"
                            type="text"
                            class="w-full block"
                            :placeholder="$t('public.search')"
                            v-model="search"
                        />
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

                <div class="flex sm:col-span-2 justify-end gap-4 items-center w-full">
                    <Button
                        type="button"
                        variant="secondary"
                        @click="clearFilter"
                    >
                        {{ $t('public.clear') }}
                    </Button>
                </div>
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
                v-if="affiliateCopyTradeTransactions.data.length === 0"
                class="w-full flex items-center justify-center"
            >
                <NoData/>
            </div>
            <div v-else>
                <TanStackTable
                    :data="affiliateCopyTradeTransactions"
                    :columns="columns"
                    @update:sorting="sorting = $event"
                    @update:action="action = $event"
                    @update:currentPage="currentPage = $event"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
