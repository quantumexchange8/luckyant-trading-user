<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import {h, ref, watch} from "vue";
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
import Action from "@/Pages/Wallet/Partials/Action.vue"
import StatusBadge from "@/Components/StatusBadge.vue";

const props = defineProps({
    transactionTypeSel: Array,
    wallets: Object,
    walletsSel: Array,
})
const walletIds = props.wallets.map(wallet => wallet.id);
const cashWalletAmount = ref(null);
const bonusWalletAmount = ref(null);
const ewalletAmount = ref(null);
const walletHistories = ref({data: []});
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

const getResults = async (page = 1, paginate = 10, filterSearch = search.value, filterType = type.value, filterWallet = wallet.value, filterDate = date.value, columnName = sorting.value) => {
    // isLoading.value = true
    try {
        let url = `/wallet/getWalletHistories?page=${page}`;

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
        walletHistories.value = response.data.walletHistories;
        cashWalletAmount.value = response.data.cashWalletAmount;
        bonusWalletAmount.value = response.data.bonusWalletAmount;
        ewalletAmount.value = response.data.ewalletAmount;
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
        accessorKey: 'transaction_type',
        header: 'transaction_type',
        enableSorting: false,
        cell: info => trans('public.' + info.getValue().replace(/([A-Z])/g, '_$1').toLowerCase().replace(/^_/, ''))
   },
   {
        accessorFn: row => row.from_wallet?.type || row.from_meta_login?.meta_login,
        header: 'from',
        enableSorting: false,
        cell: info => {
            const fromWallet = info.row.original.from_wallet;
            const fromMetaLogin = info.row.original.from_meta_login;
            const isTransfer = info.row.original.transaction_type === 'Transfer';
            const isPerformanceIncentive = info.row.original.transaction_type === 'PerformanceIncentive';

            if (isPerformanceIncentive) {
                return '-';
            } else if (fromWallet) {
                const username = fromWallet.user?.username || '';
                return isTransfer ? `${username ? username + ' - ' : ''}${fromWallet.wallet_address}` : trans('public.' + info.getValue());
            } else {
                return fromMetaLogin ? `${trans('public.account_no')} - ${info.getValue()}` : '-';
            }
        }
    },
    {
        accessorFn: row => row.to_wallet?.type || row.to_meta_login?.meta_login,
        header: 'to',
        enableSorting: false,
        cell: info => {
            const isTransfer = info.row.original.transaction_type === 'Transfer';
            const toWallet = info.row.original.to_wallet;
            const toMetaLogin = info.row.original.to_meta_login;
            const isWalletWithdrawal = info.row.original.category === 'wallet' && info.row.original.transaction_type === 'Withdrawal';

            if (isWalletWithdrawal) {
                return `${info.row.original.payment_account.payment_account_name} - ${info.row.original.payment_account.account_no}`;
            } else if (toWallet) {
                const username = toWallet.user?.username || '';
                return isTransfer ? `${username ? username + ' - ' : ''}${toWallet.wallet_address}` : trans('public.' + info.getValue());
            } else {
                return toMetaLogin ? `${trans('public.account_no')} - ${info.getValue()}` : '-';
            }
        }
    },
    {
        accessorKey: 'transaction_number',
        header: 'transaction_no',
        enableSorting: false,
        cell: info => info.getValue(),
    },
    {
        accessorKey: 'amount',
        header: 'amount',
        cell: info => {
            const isTransfer = info.row.original.transaction_type === 'Transfer';
            const isInternalTransferWithSelectedWallet = info.row.original.transaction_type === 'InternalTransfer' && wallet.value === info.row.original.from_wallet_id;
            const isTradingAccountDeposit = info.row.original.category === 'trading_account' && info.row.original.transaction_type === 'Deposit';
            const isWalletWithdrawal = info.row.original.category === 'wallet' && info.row.original.transaction_type === 'Withdrawal';

            if (isTransfer || isInternalTransferWithSelectedWallet || isTradingAccountDeposit || isWalletWithdrawal) {
                const prefix = walletIds.includes(info.row.original.from_wallet_id) ? '$ -' : '$ ';
                return prefix + formatAmount(info.getValue());
            } else {
                return '$ ' + formatAmount(info.getValue());
            }
        },
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
        accessorKey: 'action',
        header: 'table_action',
        enableSorting: false,
        cell: ({ row }) => h(Action, {
            walletHistory: row.original,
        }),
    },
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
        getResults(1, pageSizeValue, search.value, type.value, wallet.value, date.value, sortingValue);
    }
);

watch(
    [search, type, wallet, date],
    debounce(([searchValue, typeValue, walletValue, dateValue]) => {
        getResults(1, pageSize.value, searchValue, typeValue, walletValue, dateValue, sorting.value);
    }, 300)
);

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
            <div v-for="wallet in wallets" :key="wallet.id" class="flex justify-between items-center p-6 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                <div class="flex flex-col gap-4">
                    <div>
                        {{ $t('public.' + wallet.type) }}
                    </div>
                    <div class="text-2xl font-bold">
                        $ {{
                            formatAmount(wallet.type === 'cash_wallet' ? cashWalletAmount : wallet.type === 'bonus_wallet' ? bonusWalletAmount : ewalletAmount)
                        }}
                    </div>
                </div>
                <div
                    class="rounded-full flex items-center justify-center w-14 h-14"
                    :class="{
                        'bg-primary-200' : wallet.type === 'cash_wallet',
                        'bg-purple-200': wallet.type === 'bonus_wallet',
                        'bg-gray-200': wallet.type === 'e_wallet',
                    }"
                >
                    <Wallet01Icon
                        class="w-8 h-8"
                        :class="{
                            'text-primary-500' : wallet.type === 'cash_wallet',
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
                        input-classes="py-2.5 w-full rounded-lg dark:placeholder:text-gray-500 focus:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500 bg-white dark:bg-gray-800 dark:text-white border border-gray-300 dark:border-gray-800"
                    />
                </div>
                <div class="w-full">
                    <BaseListbox
                        id="statusID"
                        class="rounded-lg text-base text-black w-full dark:text-white dark:bg-gray-800"
                        v-model="type"
                        :options="transactionTypeSel"
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
                v-if="walletHistories.data.length === 0"
                class="w-full flex items-center justify-center"
            >
                <NoData />
            </div>
            <div v-else>
                <TanStackTable
                    :data="walletHistories"
                    :columns="columns"
                    @update:sorting="sorting = $event"
                    @update:action="action = $event"
                    @update:currentPage="currentPage = $event"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
