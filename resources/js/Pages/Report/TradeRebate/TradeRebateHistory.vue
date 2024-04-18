<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import {transactionFormat} from "@/Composables/index.js";
import Button from "@/Components/Button.vue";
import Input from "@/Components/Input.vue";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import {SearchIcon, ArrowLeftIcon, ArrowRightIcon, RefreshIcon} from "@heroicons/vue/outline";
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import TradeRebateHistoryTable from "@/Pages/Report/TradeRebate/TradeRebateHistoryTable.vue";
import {ref, watch, watchEffect} from "vue";
import {usePage} from "@inertiajs/vue3";
import toast from "@/Composables/toast.js";
import {Tab, TabGroup, TabList, TabPanel, TabPanels} from "@headlessui/vue";

const props = defineProps({
    totalRebate: Number,
    totalVolume: Number,
})

const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});

const { formatAmount } = transactionFormat();
const currentLocale = ref(usePage().props.locale);

const statusVariant = (status) => {
    if (status === 'Pending') return 'processing';
    if (status === 'Active') return 'success';
    if (status === 'Rejected' || status === 'Terminated') return 'danger';
}

const isLoading = ref(false);
const date = ref('');
const search = ref('');
const type = ref('');
const refresh = ref(false);

const updateHistoryType = (history_type) => {
    type.value = history_type
};

const selectedTab = ref(0);
function changeTab(index) {
    selectedTab.value = index;
}

const historyTypes = [
    { value: 'Affiliate', name: 'affiliate' },
    { value: 'Personal', name: 'personal' },
];

</script>

<template>
    <AuthenticatedLayout :title="$t('public.trade_rebate_report')">
        <template #header>
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    {{ $t('public.trade_rebate_report') }}
                </h2>
            </div>
        </template>

        <div class="grid grid-cols-2 w-full gap-4">
            <div class="flex flex-col gap-5 p-6 md:col-span-1 col-span-3 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                <div>
                    {{ $t('public.total_rebate_amount') }}
                </div>
                <div class="text-2xl font-bold">
                    $ {{ formatAmount(props.totalRebate) }}
                </div>
            </div>

            <div class="flex flex-col gap-5 p-6 md:col-span-1 col-span-3 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                <div>
                    {{ $t('public.total_trade_volume') }}
                </div>
                <div class="text-2xl font-bold">
                    {{ formatAmount(props.totalVolume) }}
                </div>
            </div>
        </div>

        <div class="p-5 my-5 mb-28 bg-white overflow-hidden md:overflow-visible rounded-lg shadow-lg dark:bg-gray-900 border border-gray-300 dark:border-gray-600">
            <div class="flex justify-between mb-3">
                <h4 class="font-semibold text-lg dark:text-white">{{$t('public.rebate_history')}}</h4>
                <RefreshIcon
                    :class="{ 'animate-spin': isLoading }"
                    class="flex-shrink-0 w-5 h-5 cursor-pointer dark:text-white"
                    aria-hidden="true"
                    @click="refresh = true"
                />
            </div>

            <div class="flex flex-wrap gap-3 w-full justify-end items-center sm:flex-nowrap">
                <div class="w-full sm:w-80">
                    <InputIconWrapper>
                        <template #icon>
                            <SearchIcon aria-hidden="true" class="w-5 h-5" />
                        </template>
                        <Input withIcon id="search" type="text" class="w-full block dark:border-transparent" :placeholder="$t('public.search')" v-model="search" />
                    </InputIconWrapper>
                </div>
                <div class="w-full sm:w-80">
                    <vue-tailwind-datepicker
                        :placeholder="$t('public.date_placeholder')"
                        :formatter="formatter"
                        separator=" - "
                        v-model="date"
                        input-classes="py-2.5 w-full rounded-lg dark:placeholder:text-gray-500 focus:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500 bg-white dark:bg-gray-800 dark:text-white border border-gray-300 dark:border-gray-800"
                    />
                </div>
                <!-- <div>
                    <Button
                        type="button"
                        variant="gray"
                        class="w-full flex gap-1 justify-center"
                        size="sm"
                        v-slot="{ iconSizeClasses }"
                        @click="exportMember"
                    >
                        <CloudDownloadIcon class="w-5 h-5" />
                        Export
                    </Button>
                </div> -->
            </div>

            <div class="w-full">
                <TabGroup :selectedIndex="selectedTab" @change="changeTab">
                    <TabList class="flex py-1 w-full flex-col gap-3 sm:flex-row sm:justify-between">
                        <div class="w-full">
                            <Tab
                                v-for="historyType in historyTypes"
                                as="template"
                                v-slot="{ selected }"
                            >
                                <button
                                    @click="updateHistoryType(historyType.value)"
                                    class="w-full sm:w-40"
                                    :class="[
                                    'py-2.5 text-sm font-semibold dark:text-gray-400',
                                    'ring-white ring-offset-0 focus:outline-none focus:ring-0',
                                       selected
                                    ? 'dark:text-white border-b-2 border-gray-400 dark:border-gray-500'
                                    : 'border-b border-gray-300 dark:border-gray-700',
                                ]"
                                >
                                    {{ $t('public.' + historyType.name) }}
                                </button>
                            </Tab>
                        </div>
                    </TabList>

                    <TabPanels>
                        <TabPanel
                            v-for="historyType in historyTypes"
                        >
                            <TradeRebateHistoryTable
                                :refresh="refresh"
                                :isLoading="isLoading"
                                :search="search"
                                :date="date"
                                :historyType=historyType.value
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
