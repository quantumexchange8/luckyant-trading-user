<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import Button from "@/Components/Button.vue";
import { ref } from 'vue'
import {Tab, TabGroup, TabList, TabPanel, TabPanels} from "@headlessui/vue";
import { CloudDownloadIcon, SearchIcon } from "@heroicons/vue/outline";
import TransactionTable from "@/Pages/Transaction/Partials/TransactionTable.vue";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import Input from "@/Components/Input.vue";
import BaseListbox from "@/Components/BaseListbox.vue";

const search = ref('');
const date = ref('');
const type = ref('');
const category = ref('');
const methods = ref('');
const isLoading = ref(false);
const refresh = ref(false);
const exportStatus = ref(false)
const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});

const typeFilter = [
    {value: '', label:"All"},
    {value: 'wallet', label:"Wallet"},
    {value: 'trading_account', label:"Trading Account"},
];

const paymentMethods = [
    {value: '', label:"All"},
    {value: 'Bank', label:"Bank"},
    {value: 'Crypto', label:"Crypto"},
    {value: 'Payment Merchant', label:"Payment Merchant"},
];

const updateTransactionType = (transaction_type) => {
    type.value = transaction_type
};

const selectedTab = ref(0);
function changeTab(index) {
    selectedTab.value = index;
}

const transactionTypes = [
    { value: 'Deposit', name: 'deposit' },
    { value: 'Withdrawal', name: 'withdrawal' },
    { value: 'InternalTransfer', name: 'internal_transfer' },
    { value: 'WalletAdjustment', name: 'wallet_adjustment' },
];

const exportMember = () => {
    exportStatus.value = true;
}
</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar.transaction')">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    {{ $t('public.sidebar.transaction') }}
                </h2>
            </div>
        </template>

        <div class="p-5 mb-28 bg-white overflow-hidden md:overflow-visible rounded-xl shadow-md dark:bg-gray-900">
            <div class="flex justify-between mb-3">
                <h4 class="font-semibold dark:text-white">{{ $t('public.transaction_history')}}</h4>
                <!-- <RefreshIcon
                    :class="{ 'animate-spin': isLoading }"
                    class="flex-shrink-0 w-5 h-5 cursor-pointer dark:text-white"
                    aria-hidden="true"
                    @click="refreshTable"
                /> -->
            </div>
            <div class="flex flex-wrap gap-3 items-center sm:flex-nowrap my-4">
                <div class="flex items-center gap-5 w-full">
                    <div class="w-full">
                        <InputIconWrapper class="md:col-span-2">
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
                            :placeholder="$t('public.date_placeholder')"
                            :formatter="formatter"
                            separator=" - "
                            v-model="date"
                            input-classes="py-2.5 w-full rounded-lg dark:placeholder:text-gray-500 focus:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500 bg-white dark:bg-gray-700 dark:text-white border border-gray-300 dark:border-dark-eval-2"
                        />
                    </div>
                    <div class="w-full">
                        <BaseListbox
                            id="category"
                            class="w-full rounded-lg text-base text-black dark:text-white dark:bg-gray-600"
                            v-model="category"
                            :options="typeFilter"
                            placeholder="Filter Category"
                        />
                    </div>
                    <div class="w-full">
                        <BaseListbox
                            id="methods"
                            class="w-full rounded-lg text-base text-black dark:text-white dark:bg-gray-600"
                            v-model="methods"
                            :options="paymentMethods"
                            placeholder="Filter Methods"
                        />
                    </div>
                </div>
            </div>
            <div class="w-full">
                <TabGroup :selectedIndex="selectedTab" @change="changeTab">
                    <TabList class="flex py-1 w-full flex-col gap-3 sm:flex-row sm:justify-between">
                        <div class="w-full">
                            <Tab
                                v-for="transactionType in transactionTypes"
                                as="template"
                                v-slot="{ selected }"
                            >
                                <button
                                    @click="updateTransactionType(transactionType.value)"
                                    class="w-full sm:w-40"
                                    :class="[
                                    'py-2.5 text-sm font-semibold dark:text-gray-400',
                                    'ring-white ring-offset-0 focus:outline-none focus:ring-0',
                                       selected
                                    ? 'dark:text-white border-b-2 border-gray-400 dark:border-gray-500'
                                    : 'border-b border-gray-300 dark:border-gray-700',
                                ]"
                                >
                                    {{ $t('public.' + transactionType.name) }}
                                </button>
                            </Tab>
                        </div>
<!--                        <div>-->
<!--                            <Button-->
<!--                                type="button"-->
<!--                                variant="gray"-->
<!--                                class="w-full flex gap-1 justify-center"-->
<!--                                size="sm"-->
<!--                                v-slot="{ iconSizeClasses }"-->
<!--                                @click="exportMember"-->
<!--                            >-->
<!--                                <CloudDownloadIcon class="w-5 h-5" />-->
<!--                                Export-->
<!--                            </Button>-->
<!--                        </div>-->
                    </TabList>

                    <TabPanels>
                        <TabPanel
                            v-for="transactionType in transactionTypes"
                        >
                            <TransactionTable
                                :refresh="refresh"
                                :isLoading="isLoading"
                                :search="search"
                                :date="date"
                                :category="category"
                                :methods="methods"
                                :transactionType=transactionType.value
                                :exportStatus="exportStatus"
                                @update:loading="isLoading = $event"
                                @update:refresh="refresh = $event"
                                @update:export="exportStatus = $event"
                            />
                        </TabPanel>
                    </TabPanels>
                </TabGroup>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
