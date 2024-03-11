<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import FollowingMaster from "@/Pages/Trading/MasterListing/FollowingMaster.vue";
import MasterAccounts from "@/Pages/Trading/MasterListing/MasterAccounts.vue";
import Button from "@/Components/Button.vue";
import {Tab, TabGroup, TabList, TabPanel, TabPanels} from "@headlessui/vue";
import MasterAccount from "@/Pages/AccountInfo/MasterAccount/MasterAccount.vue";
import TradingAccount from "@/Pages/AccountInfo/TradingAccount/TradingAccount.vue";


</script>

<template>
    <AuthenticatedLayout title="Master Trader's Listing">
        <template #header>
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    Master Trader's Listing
                </h2>
            </div>
        </template>

        <div class="w-full">
            <TabGroup>
                <TabList class="flex space-x-1 rounded-xl bg-blue-900/20 dark:bg-gray-800 p-1 max-w-md">
                    <Tab
                        as="template"
                        v-slot="{ selected }"
                    >
                        <button
                            :class="[
                                    'w-full rounded-lg py-2.5 text-sm font-medium leading-5',
                                     'ring-white/60 ring-offset-2 ring-offset-primary-200 focus:outline-none focus:ring-2',
                                     selected
                                     ? 'bg-white text-primary-800 shadow'
                                     : 'text-blue-25 hover:bg-white/[0.12] hover:text-white',
                                ]"
                        >
                            Master Accounts
                        </button>
                    </Tab>

                    <Tab
                        as="template"
                        v-slot="{ selected }"
                    >
                        <button
                            :class="[
                                    'w-full rounded-lg py-2.5 text-sm font-medium leading-5',
                                     'ring-white/60 ring-offset-2 ring-offset-primary-200 focus:outline-none focus:ring-2',
                                     selected
                                     ? 'bg-white text-primary-800 shadow'
                                     : 'text-blue-25 hover:bg-white/[0.12] hover:text-white',
                                ]"
                        >
                            Subscriptions
                        </button>
                    </Tab>
                </TabList>

                <TabPanels class="mt-2">
                    <TabPanel
                        class="py-3"
                    >
                        <MasterAccounts
                        />
                    </TabPanel>

                    <TabPanel
                        class="py-3"
                    >
                        <FollowingMaster
                        />
                    </TabPanel>
                </TabPanels>
            </TabGroup>
        </div>

    </AuthenticatedLayout>
</template>
