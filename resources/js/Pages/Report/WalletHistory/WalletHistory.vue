<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import {ref, watch} from "vue";
import {CurrencyDollarCircleIcon, Wallet01Icon} from "@/Components/Icons/outline.jsx";
import BaseListbox from "@/Components/BaseListbox.vue";
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import {usePage} from "@inertiajs/vue3";
import {transactionFormat} from "@/Composables/index.js";
import debounce from "lodash/debounce.js";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import {SearchIcon} from "@heroicons/vue/outline";
import Input from "@/Components/Input.vue";
import NoData from "@/Components/NoData.vue";
import TanStackTable from "@/Components/TanStackTable.vue";
import {trans} from "laravel-vue-i18n";
import Button from "@/Components/Button.vue";

const props = defineProps({
    walletsSel: Array,
    bonusTypeSel: Array,
    wallets: Object,
})

const totalBonus = ref(null);
const walletLogs = ref({data: []});
const sorting = ref();
const search = ref('');
const type = ref('');
const wallet = ref('');
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
        getResults(1, pageSizeValue, search.value, type.value, wallet.value, date.value, sortingValue);
    }
);

watch(
    [search, type, wallet, date],
    debounce(([searchValue, typeValue, walletValue, dateValue]) => {
        getResults(1, pageSize.value, searchValue, typeValue, walletValue, dateValue, sorting.value);
    }, 300)
);

const getResults = async (page = 1, paginate = 10, filterSearch = search.value, filterType = type.value, filterWallet = wallet.value, filterDate = date.value, columnName = sorting.value) => {
    // isLoading.value = true
    try {
        let url = `/report/getWalletLogs?page=${page}`;

        if (paginate) {
            url += `&paginate=${paginate}`;
        }

        if (filterSearch) {
            url += `&search=${filterSearch}`;
        }

        if (filterType) {
            url += `&type=${filterType}`;
        }

        if (filterWallet) {
            url += `&wallet_id=${filterWallet}`;
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
        walletLogs.value = response.data.walletLogs;
        totalBonus.value = response.data.totalBonus;
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
        accessorKey: 'wallet.type',
        header: 'wallet',
        enableSorting: false,
        cell: info => trans('public.' + info.getValue()),
    },
    {
        accessorKey: 'purpose',
        header: 'type',
        enableSorting: false,
        cell: info => trans('public.' + info.getValue()),
    },
    {
        accessorKey: 'amount',
        header: 'amount',
        cell: info => '$ ' + formatAmount(info.getValue()),
    },
];

const clearFilter = () => {
    search.value = '';
    type.value = '';
    wallet.value = '';
    date.value = '';
}
</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar.wallet_history')">
        <template #header>
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    {{ $t('public.sidebar.wallet_history') }}
                </h2>
            </div>
        </template>

        <div class="grid grid-cols-1 sm:grid-cols-3 w-full gap-4">
            <div class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                <div class="flex flex-col gap-4">
                    <div>
                        {{ $t('public.total_rewards') }}
                    </div>
                    <div class="text-2xl font-bold">
                        <span v-if="totalBonus !== null">
                            $ {{ totalBonus }}
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
            <div v-for="wallet in wallets" class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                <div class="flex flex-col gap-4">
                    <div>
                        {{ $t('public.' + wallet.type) }}
                    </div>
                    <div class="text-2xl font-bold">
                        $ {{ formatAmount(wallet.balance)}}
                    </div>
                </div>
                <div
                    class="rounded-full flex items-center justify-center w-14 h-14"
                    :class="{
                        'bg-purple-200': wallet.type === 'bonus_wallet',
                        'bg-gray-200': wallet.type === 'e_wallet',
                    }"
                >
                    <Wallet01Icon
                        class="w-8 h-8"
                        :class="{
                            'text-purple-500': wallet.type === 'bonus_wallet',
                            'text-gray-500': wallet.type === 'e_wallet',
                        }"
                    />
                </div>
            </div>
        </div>

        <div class="flex flex-col gap-5 items-start self-stretch my-8">
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 w-full">
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
                    <vue-tailwind-datepicker
                        :placeholder="$t('public.date')"
                        :formatter="formatter"
                        separator=" - "
                        v-model="date"
                        input-classes="py-2.5 w-full rounded-lg dark:placeholder:text-gray-500 focus:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500 bg-white dark:bg-gray-700 dark:text-white border border-gray-300 dark:border-dark-eval-2"
                    />
                </div>
                <div class="w-full">
                    <BaseListbox
                        id="statusID"
                        class="rounded-lg text-base text-black w-full dark:text-white dark:bg-gray-800"
                        v-model="type"
                        :options="bonusTypeSel"
                        :placeholder="$t('public.type')"
                    />
                </div>
                <div class="w-full">
                    <BaseListbox
                        id="statusID"
                        class="rounded-lg text-base text-black w-full dark:text-white dark:bg-gray-800"
                        v-model="wallet"
                        :options="walletsSel"
                        :placeholder="$t('public.wallet_name')"
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
                v-if="walletLogs.data.length === 0"
                class="w-full flex items-center justify-center"
            >
                <NoData />
            </div>
            <div v-else>
                <TanStackTable
                    :data="walletLogs"
                    :columns="columns"
                    @update:sorting="sorting = $event"
                    @update:action="action = $event"
                    @update:currentPage="currentPage = $event"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
