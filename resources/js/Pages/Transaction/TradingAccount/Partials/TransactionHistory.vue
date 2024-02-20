<script setup>
import {ref} from "vue";
import {SearchIcon, RefreshIcon} from "@heroicons/vue/outline";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import Input from "@/Components/Input.vue";
import BaseListbox from "@/Components/BaseListbox.vue";
import Button from "@/Components/Button.vue";
import {Tab, TabGroup, TabList, TabPanel, TabPanels} from "@headlessui/vue";
import {usePage} from "@inertiajs/vue3";
import TransactionTable from "@/Pages/Transaction/TradingAccount/Partials/TransactionTable.vue";

const props = defineProps({
    refresh: Boolean,
    isLoading: Boolean,
    search: String,
    date: String,
    filter: String,
    exportStatus: Boolean,
})

const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});

const typeFilter = [
    {value: '', label:"All"},
    {value: 'Deposit', label:"Deposit"},
    {value: 'Withdrawal', label:"Withdrawal"},
    {value: 'InternalTransfer', label:"Internal Transfer"},
];

const categories = ref({});
const isLoading = ref(false);
const date = ref('');
const search = ref('');
const refresh = ref(false);
const type = ref('');

function refreshTable() {
    isLoading.value = !isLoading.value;
    refresh.value = true;
}

const clearFilter = () => {
    search.value = ''
    type.value = null
    date.value = ''
}

</script>

<template>
    <div class="flex justify-between mb-3">
        <h4 class="font-semibold dark:text-white">Transaction History</h4>
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
                <!-- <Input withIcon id="search" type="text" class="w-full block dark:border-transparent" :placeholder="$t('public.report.search_placeholder')" v-model="search" /> -->
                <Input withIcon id="search" type="text" class="w-full block dark:border-transparent" placeholder="search" v-model="search" />
            </InputIconWrapper>
        </div>
        <div class="w-full">
            <vue-tailwind-datepicker
                placeholder="Select Date"
                :formatter="formatter"
                separator=" - "
                v-model="date"
                input-classes="py-2.5 w-full rounded-lg dark:placeholder:text-gray-500 focus:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500 bg-white dark:bg-gray-700 dark:text-white border border-gray-300 dark:border-dark-eval-2"
            />
        </div>
        <div class="w-full">
            <BaseListbox
                v-model="type"
                :options="typeFilter"
                placeholder="Filters"
                class="w-full"
            />
        </div>

        <div class="w-full sm:w-auto">
            <Button
                type="button"
                variant="transparent"
                @click="clearFilter"
                class="w-full justify-center"
            >
                Clear
            </Button>
        </div>
    </div>

    <div class="w-full pt-5">
        <TabGroup>
            <TabPanels>
                <TabPanel>
                    <TransactionTable
                        :refresh="refresh"
                        :isLoading="isLoading"
                        :search="search"
                        :date="date"
                        :type="type"
                        @update:loading="$emit('update:loading', $event)"
                        @update:refresh="$emit('update:refresh', $event)"
                        @update:export="$emit('update:export', $event)"
                    />
                </TabPanel>
            </TabPanels>
        </TabGroup>
    </div>
</template>
