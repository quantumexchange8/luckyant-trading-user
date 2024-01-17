<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import Button from "@/Components/Button.vue";
import { ref } from 'vue'
import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue'
import TransactionTable from "@/Pages/Transaction/TransactionTable.vue";

const categories = ref([
    {
        label: 'Trading Account',
        category: 'TradingAccount',
    },
    {
        label: 'Cash Wallet',
        category: 'CashWallet'
    },
])
</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar.Transaction')">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    {{ $t('public.sidebar.Transaction') }}
                </h2>
            </div>
        </template>

        <div class="w-full">
            <TabGroup>
                <TabList class="flex space-x-1 rounded-xl bg-[#9cb6dd70] dark:bg-[#839dd170] max-w-md p-1">
                    <Tab
                        v-for="category in categories"
                        as="template"
                        :key="category"
                        v-slot="{ selected }"
                    >
                        <button
                            :class="[
                                'w-full rounded-lg py-2.5 text-sm font-medium leading-5',
                                'ring-white/60 ring-offset-2 ring-offset-blue-400 focus:outline-none focus:ring-2',
                                selected
                                ? 'bg-white text-purple-500 shadow'
                                : 'text-primary-700 dark:text-primary-300 hover:bg-white/[0.12] hover:text-white',
                            ]"
                        >
                            {{ category.label }}
                        </button>
                    </Tab>
                </TabList>

                <TabPanels class="mt-2">
                    <TabPanel
                        v-for="(category, idx) in categories"
                        :key="idx"
                        :class="[
                            'rounded-xl bg-white dark:bg-dark-eval-1 p-3',
                        ]"
                    >
                        <TransactionTable
                            :category="category.category"
                        />
                    </TabPanel>
                </TabPanels>
            </TabGroup>
        </div>
    </AuthenticatedLayout>
</template>
