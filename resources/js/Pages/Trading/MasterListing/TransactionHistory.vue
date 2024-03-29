<script setup>
import Button from "@/Components/Button.vue";
import Input from "@/Components/Input.vue";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import {ArrowLeftIcon, ArrowRightIcon, SearchIcon} from "@heroicons/vue/outline";
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
import { trans } from "laravel-vue-i18n";

const props = defineProps({
    exportStatus: Boolean,
})

const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});

// const statusFilter = [
//     {value: 'Success', label:"Success"},
//     {value: 'Processing', label:"Processing"},
//     {value: 'Rejected', label:"Rejected"},
// ];

const typeFilter = [
    { value: 'Deposit', label: trans('public.deposit') },
    { value: 'Withdrawal', label: 'Withdrawal' },
    { value: 'InternalTransfer', label: 'Internal Transfer' },
    { value: 'SubscriptionFee', label: 'Subscription Fee' },
];

const isLoading = ref(false);
const date = ref('');
const search = ref('');
// const status = ref('');
const refresh = ref(false);
const type = ref('');
const transactions = ref({data: []})
const currentPage = ref(1)
const { formatDateTime, formatAmount } = transactionFormat();

const getResults = async (page = 1, search = '', type = '', date = '') => {
    isLoading.value = true
    try {
        let url = `/transaction/getTransactionData?page=${page}&category=trading_account&type=deposit`;

        if (search) {
            url += `&search=${search}`;
        }

        // if (type) {
        //     url += `&type=${type}`;
        // }

        if (date) {
            url += `&date=${date}`;
        }

        // if (status) {
        //     url += `&status=${status}`;
        // }

        const response = await axios.get(url);
        transactions.value = response.data;

    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false
    }
}

getResults()

watch(
    [search, type, date],
    debounce(([searchValue, typeValue, dateValue]) => {
        getResults(1, searchValue, typeValue, dateValue);
    }, 300)
);

const handlePageChange = (newPage) => {
    if (newPage >= 1) {
        currentPage.value = newPage;

        getResults(currentPage.value, search.value, date.value);
    }
};

const TransactionHistoryModal = ref(false);
const TransactionHistoryDetail = ref()

const openTransactionModal = (transaction) => {
    TransactionHistoryModal.value = true
    TransactionHistoryDetail.value = transaction
}

const closeModal = () => {
    TransactionHistoryModal.value = false
}

const clearFilter = () => {
    search.value = '';
    type.value = '';
    date.value = [];
}

const statusVariant = (status) => {
    if (status === 'Pending') return 'processing';
    if (status === 'Active') return 'success';
    if (status === 'Rejected' || status === 'Terminated') return 'danger';
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

const currentLocale = ref(usePage().props.locale);

</script>

<template>
    <div class="flex justify-between mb-3">
<!--        <h4 class="font-semibold dark:text-white">{{$t('public.transaction_history')}}</h4>-->
        <!-- <RefreshIcon
            :class="{ 'animate-spin': isLoading }"
            class="flex-shrink-0 w-5 h-5 cursor-pointer dark:text-white"
            aria-hidden="true"
            @click="refreshTable"
        /> -->
    </div>

    <div class="flex flex-wrap gap-3 items-center sm:flex-nowrap">
        <div class="w-full">
            <InputIconWrapper>
                <template #icon>
                    <SearchIcon aria-hidden="true" class="w-5 h-5" />
                </template>
                <Input withIcon id="search" type="text" class="w-full block dark:border-transparent" :placeholder="$t('public.search')" v-model="search" />
            </InputIconWrapper>
        </div>
        <div class="w-full">
            <vue-tailwind-datepicker
                :placeholder="$t('public.date_placeholder')"
                :formatter="formatter"
                separator=" - "
                v-model="date"
                input-classes="py-2.5 w-full rounded-lg dark:placeholder:text-gray-500 focus:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500 bg-white dark:bg-gray-700 dark:text-white border border-gray-300 dark:border-dark-eval-2"
            />
        </div>
        <!-- <div class="w-full">
            <BaseListbox
                v-model="type"
                :options="typeFilter"
                :placeholder="$t('public.filters_placeholder')"
                class="w-full"
            />
        </div> -->
        <!-- <div class="w-full">
            <BaseListbox
                v-model="status"
                :options="statusFilter"
                :placeholder="$t('public.filters_placeholder')"
                class="w-full"
            />
        </div> -->
        <div class="w-full sm:w-auto">
            <Button
                type="button"
                variant="transparent"
                @click="clearFilter"
                class="w-full justify-center"
            >
                {{$t('public.clear')}}
            </Button>
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
                    {{ $t('public.date') }}
                </th>
                <th scope="col" class="p-3">
                    {{ $t('public.from') }}
                </th>
                <th scope="col" class="p-3">
                    {{ $t('public.to') }}
                </th>
                <th scope="col" class="p-3">
                    {{ $t('public.transaction_type') }}
                </th>
                <th scope="col" class="p-3">
                    {{ $t('public.transaction_no') }}
                </th>
                <th scope="col" class="p-3">
                    {{ $t('public.amount') }}
                </th>
                <th scope="col" class="p-3 text-center">
                    {{ $t('public.status') }}
                </th>
            </tr>
            </thead>
            <tbody>
            <tr v-if="transactions.data.length === 0">
                <th colspan="6" class="py-4 text-lg text-center">
                    {{$t('public.no_history')}}
                </th>
            </tr>
            <tr
                v-for="transaction in transactions.data"
                class="bg-white dark:bg-transparent text-xs text-gray-900 dark:text-white border-b dark:border-gray-800 hover:cursor-pointer hover:bg-primary-50 dark:hover:bg-gray-600"
                @click="openTransactionModal(transaction)"
            >
                <td class="p-3">
                    <div class="inline-flex items-center gap-2">
                        {{ formatDateTime(transaction.created_at) }}
                    </div>
                </td>
                <td class="p-3">
                    {{ transaction.from_wallet ? $t('public.' + transaction.from_wallet.type) : (transaction.from_meta_login ? $t('public.account_no') + ' - ' + transaction.from_meta_login.meta_login : '-') }}
                </td>
                <td class="p-3">
                    {{ transaction.to_wallet ? $t('public.' + transaction.to_wallet.type) : (transaction.to_meta_login ? $t('public.account_no') + ' - ' + transaction.to_meta_login.meta_login : '-') }}
                </td>
                <td class="p-3">
                    {{ transaction.transaction_type ? $t('public.' + transaction.transaction_type.toLowerCase()) : '-' }}
                </td>
                <td class="p-3">
                    {{ transaction.transaction_number }}
                </td>
                <td class="p-3">
                    $ {{ transaction.amount }}
                </td>
                <td class="p-3 flex items-center justify-center">
                    <Badge :variant="statusVariant(transaction.status)">{{ $t('public.' + transaction.status.toLowerCase()) }}</Badge>
                </td>
            </tr>
            </tbody>
        </table>
        <div class="flex justify-center mt-4" v-if="!isLoading">
            <TailwindPagination
                :item-classes=paginationClass
                :active-classes=paginationActiveClass
                :data="transactions"
                :limit=2
                @pagination-change-page="handlePageChange"
            />
        </div>
    </div>
    <Modal :show="TransactionHistoryModal" :title="$t('public.transaction_details')" @close="closeModal">
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.transaction_type') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ $t('public.' + TransactionHistoryDetail.transaction_type.toLowerCase()) }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.from') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ TransactionHistoryDetail.from_wallet ? $t('public.' + TransactionHistoryDetail.from_wallet.type) : (TransactionHistoryDetail.from_meta_login ? $t('public.account_no') + ' - ' + TransactionHistoryDetail.from_meta_login.meta_login : '-') }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.to') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ TransactionHistoryDetail.to_wallet ? $t('public.' + TransactionHistoryDetail.to_wallet.type) : (TransactionHistoryDetail.to_meta_login ? $t('public.account_no') + ' - ' + TransactionHistoryDetail.to_meta_login.meta_login : '-') }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.payment_methods') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ TransactionHistoryDetail.payment_method ? $t('public.' + TransactionHistoryDetail.payment_method.toLowerCase()) : '-'  }}</span>
        </div>
        <div v-if="TransactionHistoryDetail.transaction_type === 'Withdrawal'" class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.to_account') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ TransactionHistoryDetail.payment_account ? TransactionHistoryDetail.payment_account.payment_platform_name + ' - ' + TransactionHistoryDetail.payment_account.account_no : '-' }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.transaction_no') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ TransactionHistoryDetail.transaction_number }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.amount') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">$ {{ TransactionHistoryDetail.amount }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.payment_charges') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">$ {{ TransactionHistoryDetail.transaction_charges }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.transaction_amount') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">$ {{ TransactionHistoryDetail.transaction_amount }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.date_and_time') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ formatDateTime(TransactionHistoryDetail.created_at) }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.status') }}</span>
            <Badge :variant="statusVariant(TransactionHistoryDetail.status)" width="auto">{{ $t('public.' + TransactionHistoryDetail.status.toLowerCase()) }}</Badge>
        </div>
        <div v-if="TransactionHistoryDetail.status === 'Rejected'" class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.remarks') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ TransactionHistoryDetail.remarks }}</span>
        </div>
    </Modal>
</template>
