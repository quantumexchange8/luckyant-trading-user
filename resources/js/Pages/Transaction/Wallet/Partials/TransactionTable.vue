<script setup>
import {ref, watch, watchEffect} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import debounce from "lodash/debounce.js";
import Loading from "@/Components/Loading.vue";
import Modal from "@/Components/Modal.vue";
import Badge from "@/Components/Badge.vue";
import {ArrowLeftIcon, ArrowRightIcon} from "@heroicons/vue/outline";
import {TailwindPagination} from "laravel-vue-pagination";
import {usePage} from "@inertiajs/vue3";

const props = defineProps({
    walletId: Number,
    search: String,
    type: String,
    date: String,
    refresh: Boolean,
    isLoading: Boolean,
})

const transactionLoading = ref(props.isLoading);
const refreshTransaction = ref(props.refresh);
const transactions = ref({data: []})
const currentPage = ref(1);
const transactionModal = ref(false);
const transactionDetails = ref();
const { formatDateTime } = transactionFormat();
const emit = defineEmits(['update:loading', 'update:refresh']);

watch(
    [() => props.search, () => props.type, () => props.date],
    debounce(([searchValue, typeValue, dateValue]) => {
        getResults(1, searchValue, typeValue, dateValue);
    }, 300)
);

const getResults = async (page = 1, search = '', type = '', date = '') => {
    transactionLoading.value = true
    try {
        let url = `/transaction/getWalletHistory/` + props.walletId + `?page=${page}`;

        if (search) {
            url += `&search=${search}`;
        }

        if (type) {
            url += `&type=${type}`;
        }

        if (date) {
            url += `&date=${date}`;
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

        getResults(currentPage.value, props.search, props.date);
    }
};

watch(() => props.refresh, (newVal) => {
    refreshDeposit.value = newVal;
    if (newVal) {
        // Call the getResults function when refresh is true
        getResults();
        emit('update:refresh', false);
    }
});

const openHistoryModal = (transaction) => {
    transactionModal.value = true
    transactionDetails.value = transaction
}

const closeModal = () => {
    transactionModal.value = false
}

const statusVariant = (transactionStatus) => {
    if (transactionStatus === 'Processing') return 'processing';
    if (transactionStatus === 'Success') return 'success';
    if (transactionStatus === 'Rejected') return 'danger';
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
</script>

<template>
    <div>
        <div v-if="transactionLoading" class="w-full flex justify-center my-8">
            <Loading />
        </div>
        <table v-else class="w-[650px] md:w-full text-sm text-left text-gray-500 dark:text-gray-400 mt-5">
            <thead class="text-xs font-medium text-gray-400 uppercase dark:bg-transparent dark:text-gray-400 border-b dark:border-gray-800">
                <tr>
                    <th scope="col" class="p-3">
                        {{ $t('public.date') }}
                    </th>
                    <th scope="col" class="p-3">
                        {{ $t('public.type') }}
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
                    <th colspan="5" class="py-4 text-lg text-center">
                        {{ $t('public.no_history') }}
                    </th>
                </tr>
                <tr
                    v-for="transaction in transactions.data"
                    class="bg-white dark:bg-transparent text-xs text-gray-900 dark:text-white border-b dark:border-gray-800 hover:cursor-pointer hover:bg-primary-50 dark:hover:bg-gray-600"
                    @click="openHistoryModal(transaction)"
                >
                    <td class="pl-5 py-2">
                        <div class="inline-flex items-center gap-2">
                            {{ formatDateTime(transaction.created_at) }}
                        </div>
                    </td>
                    <td class="py-2">
                        {{ transaction.transaction_type }}
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
    </div>
    <div class="flex justify-center mt-4" v-if="!transactionLoading">
        <TailwindPagination
            :item-classes=paginationClass
            :active-classes=paginationActiveClass
            :data="transactions"
            :limit=2
            @pagination-change-page="handlePageChange"
        >
            <template #prev-nav>
                <span class="flex gap-2"><ArrowLeftIcon class="w-5 h-5" /> <span class="hidden sm:flex">{{ $t('public.previous') }}</span></span>
            </template>
            <template #next-nav>
                <span class="flex gap-2"><span class="hidden sm:flex">{{ $t('public.next') }}</span> <ArrowRightIcon class="w-5 h-5" /></span>
            </template>
        </TailwindPagination>
    </div>

    <Modal :show="transactionModal" :title="$t('public.transaction_details')" @close="closeModal">
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.name') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ transactionDetails.user.name }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.email') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ transactionDetails.user.email }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.transaction_type') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ transactionDetails.transaction_type }}</span>
        </div>
        <div v-if="transactionDetails.from_wallet_id != null" class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.from_wallet') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ transactionDetails.from_wallet.wallet_address }}</span>
        </div>
        <div v-if="transactionDetails.to_wallet_id != null" class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.to_wallet') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ transactionDetails.to_wallet.wallet_address }}</span>
        </div>
        <div v-if="transactionDetails.from_meta_login != null" class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.from_account') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ transactionDetails.from_meta_login.meta_login }}</span>
        </div>
        <div v-if="transactionDetails.to_meta_login != null" class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.to_account') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ transactionDetails.to_meta_login.meta_login }}</span>
        </div>
        <!-- <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Ticket Number</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ transactionDetails.ticket }}</span>
        </div> -->
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.amount') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">$ {{ transactionDetails.amount }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.date_and_time') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ formatDateTime(transactionDetails.created_at) }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.remarks') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ transactionDetails.remarks ? transactionDetails.remarks : '-' }}</span>
        </div>
    </Modal>
</template>
