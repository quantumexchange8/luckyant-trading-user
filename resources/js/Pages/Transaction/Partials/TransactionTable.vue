<script setup>
import Loading from "@/Components/Loading.vue";
import {TailwindPagination} from "laravel-vue-pagination";
import {computed, ref, watch, watchEffect} from "vue";
import debounce from "lodash/debounce.js";
import {transactionFormat} from "@/Composables/index.js";
import {usePage} from "@inertiajs/vue3";
import Badge from "@/Components/Badge.vue";
import Button from "@/Components/Button.vue";
import Tooltip from "@/Components/Tooltip.vue";
import Modal from "@/Components/Modal.vue";
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue'
import {CheckIcon, ChevronDownIcon, SortAscendingIcon } from '@heroicons/vue/solid'

const props = defineProps({
    search: String,
    date: String,
    category: String,
    methods: String,
    refresh: Boolean,
    isLoading: Boolean,
    transactionType: String,
    exportStatus: Boolean,
})
const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});
const transactions = ref({data: []});
const currentPage = ref(1);
const refreshTransaction = ref(props.refresh);
const transactionLoading = ref(props.isLoading);
const emit = defineEmits(['update:loading', 'update:refresh', 'update:export']);
const { formatDateTime, formatAmount } = transactionFormat();
const transactionModal = ref(false);
const transactionDetail = ref(null);

const sortDescending = ref('desc');
const types = ref('')

const toggleSort = (sortType) => {
    sortDescending.value = sortDescending.value === 'desc' ? 'asc' : 'desc';
    types.value = sortType;
    console.log(sortType)
}

watch(
    [() => props.search, () => props.category, () => props.methods, () => props.date, () => props.transactionType, () => types.value, () => sortDescending.value],
    debounce(([searchValue, categoryValue, methodsValue, dateValue, typeValue, sortValue]) => {
        getResults(1, searchValue, categoryValue, methodsValue, dateValue, typeValue, sortValue);
    }, 300)
);

const getResults = async (page = 1, search = props.search, category = props.category, methods = props.methods, date = props.date, type = props.transactionType, sortType = types.value, sort = sortDescending.value) => {
    transactionLoading.value = true
    try {
        let url = `/transaction/getTransactionData?page=${page}`;

        if (search) {
            url += `&search=${search}`;
        }

        if (type) {
            url += `&type=${type}`;
        }

        if (category) {
            url += `&category=${category}`;
        }

        if (methods) {
            url += `&methods=${methods}`;
        }

        if (date) {
            url += `&date=${date}`;
        }

        if (sortType) {
            url += `&sortType=${sortType}`;
            url += `&sort=${sort}`;
        }

        const response = await axios.get(url);
        transactions.value = response.data;
    } catch (error) {
        console.error(error);
    } finally {
        transactionLoading.value = false
        emit('update:loading', false);
    }
}

getResults()

const handlePageChange = (newPage) => {
    if (newPage >= 1) {

        currentPage.value = newPage;

        getResults(currentPage.value, props.search, props.category, props.methods, props.date, props.kycStatus, types.value, sortDescending.value);
    }
};

watch(() => props.refresh, (newVal) => {
    refreshTransaction.value = newVal;
    if (newVal) {
        // Call the getResults function when refresh is true
        getResults();
        emit('update:refresh', false);
    }
});

watch(() => props.exportStatus, (newVal) => {
    refreshTransaction.value = newVal;
    if(newVal) {

        let url = `/transaction/getTransactionData?exportStatus=yes`;

        if (props.date) {
            url += `&date=${props.date}`;
        }

        if (props.search) {
            url += `&search=${props.search}`;
        }

        if (props.category) {
            url += `&category=${props.category}`;
        }

        if (props.methods) {
            url += `&methods=${props.methods}`;
        }

        window.location.href = url;
        emit('update:export', false);
    }
})

watchEffect(() => {
    if (usePage().props.title !== null) {
        getResults();
    }
});

const paginationClass = [
    'bg-transparent border-0 dark:text-gray-400'
];

const paginationActiveClass = [
    'border dark:border-gray-600 dark:bg-gray-600 rounded-full text-primary-500 dark:text-white'
];

const statusVariant = (transactionStatus) => {
    if (transactionStatus === 'Processing') return 'processing';
    if (transactionStatus === 'Success') return 'success';
    if (transactionStatus === 'Rejected') return 'danger';
}

const openTransactionModal = (transaction) => {
    transactionModal.value = true;
    transactionDetail.value = transaction;
}


const closeModal = () => {
    transactionModal.value = false
}
</script>

<template>
    <div class="relative overflow-x-auto sm:rounded-lg">
        <div v-if="transactionLoading" class="w-full flex justify-center my-8">
            <Loading />
        </div>
        <table v-else class="w-[850px] md:w-full text-sm text-left text-gray-500 dark:text-gray-400 mt-5">
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
                    {{ $t('public.payment_methods') }}
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
                <th colspan="7" class="py-4 text-lg text-center">
                    {{ $t('public.no_history') }}
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
                    {{ transaction.payment_method ?? '-' }}
                </td>
                <td class="p-3">
                    {{ transaction.transaction_number }}
                </td>
                <td class="p-3">
                    $ {{ transaction.amount }}
                </td>
                <td class="p-3 flex items-center justify-center">
                    <Badge :variant="statusVariant(transaction.status)">{{ transaction.status }}</Badge>
                </td>
            </tr>
            </tbody>
        </table>
        <div class="flex justify-center mt-4" v-if="!transactionLoading">
            <TailwindPagination
                :item-classes=paginationClass
                :active-classes=paginationActiveClass
                :data="transactions"
                :limit=2
                @pagination-change-page="handlePageChange"
            />
        </div>
    </div>

    <Modal :show="transactionModal" :title="$t('public.transaction_details')" @close="closeModal" max-width="xl">
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.transaction_type') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ transactionDetail.transaction_type }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.from') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ transactionDetail.from_wallet ? $t('public.' + transactionDetail.from_wallet.type) : (transactionDetail.from_meta_login ? $t('public.account_no') + ' - ' + transactionDetail.from_meta_login.meta_login : '-') }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.to') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ transactionDetail.to_wallet ? $t('public.' + transactionDetail.to_wallet.type) : (transactionDetail.to_meta_login ? $t('public.account_no') + ' - ' + transactionDetail.to_meta_login.meta_login : '-') }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.payment_methods') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ transactionDetail.payment_method ?? '-' }}</span>
        </div>
        <div v-if="transactionDetail.transaction_type === 'Deposit'" class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.to_account') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ transactionDetail.to_account_no ?? '-' }}</span>
        </div>
        <div v-if="transactionDetail.transaction_type === 'Withdrawal'" class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.to_account') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ transactionDetail.payment_account ? transactionDetail.payment_account.payment_platform_name + ' - ' + transactionDetail.payment_account.account_no : '-' }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.transaction_no') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ transactionDetail.transaction_number }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.amount') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">$ {{ transactionDetail.amount }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.payment_charges') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">$ {{ transactionDetail.transaction_charges }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.transaction_amount') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">$ {{ transactionDetail.transaction_amount }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.date_and_time') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ formatDateTime(transactionDetail.created_at) }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.status') }}</span>
            <Badge :variant="statusVariant(transactionDetail.status)" width="auto">{{ transactionDetail.status }}</Badge>
        </div>
        <div v-if="transactionDetail.status === 'Rejected'" class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.remarks') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ transactionDetail.remarks }}</span>
        </div>
    </Modal>
</template>
