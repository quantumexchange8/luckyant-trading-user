<script setup>
import {ref, watch} from "vue";
import {SearchIcon, RefreshIcon} from "@heroicons/vue/outline";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import Input from "@/Components/Input.vue";
import BaseListbox from "@/Components/BaseListbox.vue";
import Loading from "@/Components/Loading.vue";
import Badge from "@/Components/Badge.vue";
import {usePage} from "@inertiajs/vue3";
import {transactionFormat} from "@/Composables/index.js";
import debounce from "lodash/debounce.js";
import Modal from "@/Components/Modal.vue";
import Button from "@/Components/Button.vue";

const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});

const typeFilter = [
    {value: '', label: "All"},
    {value: 'Pending', label: "Pending"},
    {value: 'Success', label: "Success"},
    {value: 'Rejected', label: "Rejected"},
];

const masterRequests = ref({});
const isLoading = ref(false);
const date = ref('');
const search = ref('');
const refresh = ref(false);
const type = ref('');
const { formatDateTime } = transactionFormat();

function refreshTable() {
    isLoading.value = !isLoading.value;
    refresh.value = true;
    getResults(1, search.value, type.value, date.value);
}

const clearFilter = () => {
    search.value = ''
    type.value = null
    date.value = ''
}

watch(
    [search, type, date],
    debounce(([searchValue, typeValue, dateValue]) => {
        getResults(1, searchValue, typeValue, dateValue);
    }, 300)
);

const getResults = async (page = 1, search = '', type = '', date = '') => {
    isLoading.value = true
    try {
        let url = `/account_info/getRequestHistory?page=${page}`;

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
        masterRequests.value = response.data;

    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false
    }
}

getResults()

const statusVariant = (transactionStatus) => {
    if (transactionStatus === 'Pending') return 'processing';
    if (transactionStatus === 'Success') return 'success';
    if (transactionStatus === 'Rejected') return 'danger';
}

const requestHistoryModal = ref(false);
const requestHistoryDetail = ref();

const openRequestHistoryModal = (detail) => {
    requestHistoryModal.value = true;
    requestHistoryDetail.value = detail
};

const closeModal = () => {
    requestHistoryModal.value = false
}

const currentLocale = ref(usePage().props.locale);

</script>

<template>
    <div class="flex justify-between mb-3">
        <h4 class="font-semibold dark:text-white">{{ $t('public.request_history') }}</h4>
        <RefreshIcon
            :class="{ 'animate-spin': isLoading }"
            class="flex-shrink-0 w-5 h-5 cursor-pointer hover:text-primary-500 dark:text-white"
            aria-hidden="true"
            @click="refreshTable"
        />
    </div>
    <div class="flex flex-wrap gap-3 items-center sm:flex-nowrap">
        <div class="w-full">
            <InputIconWrapper>
                <template #icon>
                    <SearchIcon aria-hidden="true" class="w-5 h-5"/>
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
                input-classes="py-2.5 w-full rounded-lg dark:placeholder:text-gray-500 focus:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500 bg-white dark:bg-gray-800 dark:text-white border border-gray-300 dark:border-gray-800"
            />
        </div>
        <div class="w-full">
            <BaseListbox
                v-model="type"
                :options="typeFilter"
                :placeholder="$t('public.filters_placeholder')"
                class="w-full"
            />
        </div>
        <div class="w-full sm:w-auto">
            <Button
                type="button"
                variant="primary-transparent"
                @click="clearFilter"
                class="w-full justify-center"
            >
                {{ $t('public.clear') }}
            </Button>
        </div>
    </div>

    <div class="w-full pt-5">
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
                    {{ $t('public.trading_account') }}
                </th>
                <th scope="col" class="p-3">
                    {{ $t('public.approval_date') }}
                </th>
                <th scope="col" class="p-3 text-center">
                    {{ $t('public.status') }}
                </th>
            </tr>
            </thead>
            <tbody>
            <tr v-if="masterRequests.data.length === 0">
                <th colspan="5" class="py-4 text-lg text-center">
                    {{ $t('public.no_history') }}
                </th>
            </tr>
            <tr
                v-for="request in masterRequests.data"
                class="bg-white dark:bg-transparent text-xs text-gray-900 dark:text-white border-b dark:border-gray-800 hover:cursor-pointer hover:bg-primary-50 dark:hover:bg-gray-600"
                @click="openRequestHistoryModal(request)"
            >
                <td class="p-3">
                    <div class="inline-flex items-center gap-2">
                        {{ formatDateTime(request.created_at) }}
                    </div>
                </td>
                <td class="p-3">
                    <div class="flex-col">
                        <span v-if="currentLocale === 'en'" class="col-span-2 text-black dark:text-white py-2 mr-3">
                            {{ request.trading_account.trading_user.name }}
                        </span>
                        <span v-if="currentLocale === 'cn'" class="col-span-2 text-black dark:text-white py-2 mr-3">
                            {{ request.trading_account.trading_user.company ? request.trading_account.trading_user.company : request.trading_account.trading_user.name }}
                        </span>
                        <span class="col-span-2 text-black dark:text-white py-2">({{ request.trading_account.meta_login }})</span>
                    </div>
                </td>
                <td class="p-3">
                    {{ formatDateTime(request.approval_date, false) }}
                </td>
                <td class="p-3 flex items-center justify-center">
                    <Badge :variant="statusVariant(request.status)">{{ $t('public.' + request.status.toLowerCase()) }}</Badge>
                </td>
            </tr>
            </tbody>
        </table>
    </div>

    <Modal :show="requestHistoryModal" :title="$t('public.request_history_details')" @close="closeModal">
        <div class="grid grid-cols-3 items-center gap-2 pb-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.trading_account') }}</span>
            <span class="flex-col">
                <span v-if="currentLocale === 'en'" class="col-span-2 text-black dark:text-white py-2 mr-3">
                    {{ requestHistoryDetail.trading_account.trading_user.name }}
                </span>
                <span v-if="currentLocale === 'cn'" class="col-span-2 text-black dark:text-white py-2 mr-3">
                    {{ requestHistoryDetail.trading_account.trading_user.company ? requestHistoryDetail.trading_account.trading_user.company : requestHistoryDetail.trading_account.trading_user.name }}
                </span>
                <span class="col-span-2 text-black dark:text-white py-2">({{ requestHistoryDetail.trading_account.meta_login }})</span>
            </span>
        </div>
        <div class="grid grid-cols-3 items-center gap-2 pb-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.status') }}</span>
            <div class="col-span-2 items-start">
                <Badge :variant="statusVariant(requestHistoryDetail.status)">{{ $t('public.' + requestHistoryDetail.status.toLowerCase()) }}</Badge>
            </div>
        </div>
        <div class="grid grid-cols-3 items-center gap-2 pb-2">
            <span class="col-span-1 text-sm font-semibold dark:text-gray-400">{{ $t('public.remarks') }}</span>
            <span class="col-span-2 text-black dark:text-white py-2">{{ requestHistoryDetail.remarks ? requestHistoryDetail.remarks : '-' }}</span>
        </div>
    </Modal>
</template>
