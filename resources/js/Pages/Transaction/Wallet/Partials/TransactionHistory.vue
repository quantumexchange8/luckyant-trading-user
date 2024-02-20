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
import TransactionTable from "@/Pages/Transaction/Wallet/Partials/TransactionTable.vue";

const props = defineProps({
    conversion_rate: Object,
});

const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});

const typeFilter = [
    {value: '', label:"All"},
    {value: 'Deposit', label:"Deposit"},
    {value: 'Withdrawal', label:"Withdrawal"},
    {value: 'WalletAdjustment', label:"Wallet Adjustment"},
];

const wallets = ref(usePage().props.auth.user.wallets)
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

wallets.value.forEach(wallet => {
    if (!categories.value[wallet.name]) {
        categories.value[wallet.name] = [];
    }

    categories.value[wallet.name].push({
        id: wallet.id,
        name: wallet.name,
        type: wallet.type,
        balance: wallet.balance,
    });
});
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
            <TabList class="max-w-xs flex py-1">
                <Tab
                    v-for="walletName in Object.keys(categories)"
                    as="template"
                    :key="walletName"
                    v-slot="{ selected }"
                >
                    <button
                        :class="[
                            'w-full py-2.5 text-sm font-semibold dark:text-gray-400',
                            'ring-white ring-offset-0 focus:outline-none focus:ring-0',
                            selected
                            ? 'dark:text-white border-b-2'
                            : 'border-b border-gray-400',
                        ]"
                    >
                        {{ walletName }}
                    </button>
                </Tab>
            </TabList>

            <TabPanels class="mt-2">
                <TabPanel
                    v-for="(wallets, idx) in Object.values(categories)"
                    :key="idx"
                >
                    <div
                        v-for="wallet in wallets"
                        class="relative overflow-x-auto sm:rounded-lg"
                    >
                        <TransactionTable
                            :walletId="wallet.id"
                            :refresh="refresh"
                            :isLoading="isLoading"
                            :search="search"
                            :type="type"
                            :date="date"
                            @update:loading="isLoading = $event"
                            @update:refresh="refresh = $event"
                        />
                    </div>
                </TabPanel>
            </TabPanels>
        </TabGroup>
    </div>
</template>
