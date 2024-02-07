<script setup>
import {ref, watch, watchEffect} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import debounce from "lodash/debounce.js";
import Loading from "@/Components/Loading.vue";
import Modal from "@/Components/Modal.vue";
import Badge from "@/Components/Badge.vue";

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
        let url = `/transaction/getTradingHistory?page=${page}`;

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
    if (transactionStatus === 'Failed') return 'danger';
}

</script>

<template>
    <div>
        <div v-if="transactionLoading" class="w-full flex justify-center my-8">
            <Loading />
        </div>
        <table v-else class="w-[650px] md:w-full text-sm text-left text-gray-500 dark:text-gray-400 mt-5">
            <thead class="text-xs font-medium text-gray-400 uppercase bg-gray-50 dark:bg-transparent dark:text-gray-400 border-b dark:border-gray-800">
                <tr>
                    <th scope="col" class="p-3">
                        Date
                    </th>
                    <th scope="col" class="p-3">
                        Type
                    </th>
                    <th scope="col" class="p-3">
                        Transaction No
                    </th>
                    <th scope="col" class="p-3">
                        Amount
                    </th>
                    <th scope="col" class="p-3 text-center">
                        Status
                    </th>
                </tr>
            </thead>
            <tbody>
                <tr v-if="transactions.data.length === 0">
                    <th colspan="5" class="py-4 text-lg text-center">
                        No History
                    </th>
                </tr>
                <tr 
                    v-for="transaction in transactions.data"
                    class="bg-white dark:bg-transparent text-xs text-gray-900 dark:text-white border-b dark:border-gray-800 hover:cursor-pointer dark:hover:bg-gray-600"
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
                    <td class="p-3 text-center">
                        <Badge :variant="statusVariant(transaction.status)">{{ transaction.status }}</Badge>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <Modal :show="transactionModal" title="Transaction Details" @close="closeModal">
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Name</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ transactionDetails.user.name }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Email</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ transactionDetails.user.email }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Transaction Type</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ transactionDetails.transaction_type }}</span>
        </div>
        <div v-if="transactionDetails.from_wallet_id != null" class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">From Wallet</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ transactionDetails.from_wallet.wallet_address }}</span>
        </div>
        <div v-if="transactionDetails.to_wallet_id != null" class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">To Wallet</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ transactionDetails.to_wallet.wallet_address }}</span>
        </div>
        <div v-if="transactionDetails.from_meta_login != null" class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">From account</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ transactionDetails.from_meta_login.meta_login }}</span>
        </div>
        <div v-if="transactionDetails.to_meta_login != null" class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">To account</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ transactionDetails.to_meta_login.meta_login }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Ticket Number</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ transactionDetails.ticket }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Amount</span>
            <span class="col-span-2 text-black dark:text-white py-2">$ {{ transactionDetails.amount }}</span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">Date & Time</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ formatDateTime(transactionDetails.created_at) }}</span>
        </div>
    </Modal>
</template>