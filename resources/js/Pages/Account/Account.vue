<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import CreateAccount from "@/Pages/Account/Partials/CreateAccount.vue";
import {transactionFormat} from "@/Composables/index.js";
import {ref} from "vue";
import {Tab, TabGroup, TabList, TabPanel, TabPanels} from "@headlessui/vue";
import InactiveAccount from "@/Pages/AccountInfo/TradingAccount/InactiveAccount.vue";
import MasterAccount from "@/Pages/AccountInfo/MasterAccount/MasterAccount.vue";
import TradingAccount from "@/Pages/Account/TradingAccount/TradingAccount.vue";

const props = defineProps({
    activeAccountCounts: Number,
    liveAccountQuota: Number,
    leverageSel: Array,
    walletSel: Array,
    masterAccountLogin: Array,
})

const {formatAmount} = transactionFormat();
const totalEquity = ref(null);
const totalBalance = ref(null);
</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar.account_info')">
        <template #header>
            <div class="flex flex-col md:items-center gap-2 md:flex-row md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    {{ $t('public.sidebar.account_info') }}
                </h2>

                <CreateAccount
                    :activeAccountCounts="activeAccountCounts"
                    :liveAccountQuota="liveAccountQuota"
                    :leverageSel="leverageSel"
                />
            </div>
        </template>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-8">
            <fieldset
                class="border-2 border-primary-500 dark:border-primary-800 p-4 rounded-lg shadow-md text-center bg-gradient-to-b from-transparent to-primary-300 dark:to-primary-800"
            >
                <legend class="text-lg px-4 uppercase font-semibold">{{ $t('public.total_equity') }}</legend>
                <div class="text-xl font-bold sm:text-3xl">
                    <div v-if="totalEquity !== null">
                        $ {{ formatAmount(totalEquity) }}
                    </div>
                    <div v-else>
                        {{ $t('public.loading') }}
                    </div>
                </div>
            </fieldset>

            <fieldset
                class="border-2 border-purple-500 dark:border-purple-700 p-4 rounded-lg shadow-md text-center bg-gradient-to-b from-transparent to-purple-300 dark:to-purple-700"
            >
                <legend class="text-lg px-4 uppercase font-semibold">{{ $t('public.total_balance') }}</legend>
                <div class="text-xl font-bold sm:text-3xl">
                    <div v-if="totalBalance !== null">
                        $ {{ formatAmount(totalBalance) }}
                    </div>
                    <div v-else>
                        {{ $t('public.loading') }}
                    </div>
                </div>
            </fieldset>
        </div>

        <div class="w-full">
            <TabGroup>
                <TabList v-if="activeAccountCounts > 0"
                         class="flex space-x-1 rounded-xl bg-blue-900/20 dark:bg-transparent p-1 w-full max-w-md">
                    <Tab
                        as="template"
                        v-slot="{ selected }"
                    >
                        <button
                            :class="[
                                'w-full rounded-lg py-2.5 text-sm font-medium leading-5',
                                 'ring-white/60 ring-offset-2 ring-offset-primary-200 focus:outline-none focus:ring-2',
                                 selected
                                 ? 'bg-white dark:bg-gray-200 text-primary-800 shadow'
                                 : 'text-blue-25 hover:bg-white/[0.12] hover:text-white',
                            ]"
                        >
                            {{ $t('public.trading_accounts') }}
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
                                 ? 'bg-white dark:bg-gray-200 text-primary-800 shadow'
                                 : 'text-blue-25 hover:bg-white/[0.12] hover:text-white',
                            ]"
                        >
                        {{ $t('public.inactive_accounts') }}
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
                                 ? 'bg-white dark:bg-gray-200 text-primary-800 shadow'
                                 : 'text-blue-25 hover:bg-white/[0.12] hover:text-white',
                            ]"
                        >
                            {{ $t('public.master_accounts') }}
                        </button>
                    </Tab>
                </TabList>

                <TabPanels v-if="activeAccountCounts > 0" class="mt-2">
                    <TabPanel
                        class="py-3"
                    >
                        <TradingAccount
                            :activeAccountCounts="activeAccountCounts"
                            :walletSel="walletSel"
                            :leverageSel="leverageSel"
                            @update:total-equity="totalEquity = $event"
                            @update:total-balance="totalBalance = $event"
                        />
                    </TabPanel>

                    <TabPanel
                        class="py-3"
                    >
                        <InactiveAccount
                            :walletSel="walletSel"
                            :leverageSel="leverageSel"
                            :accountCounts="activeAccountCounts"
                            :masterAccountLogin="masterAccountLogin"
                        />
                    </TabPanel>

                    <TabPanel
                        class="py-3"
                    >
                        <MasterAccount
                            :walletSel="walletSel"
                            :leverageSel="leverageSel"
                            :accountCounts="activeAccountCounts"
                            :masterAccountLogin="masterAccountLogin"
                        />
                    </TabPanel>
                </TabPanels>

                <div
                    v-else
                    class="flex flex-col justify-center items-center w-full min-h-52"
                >
                    <div class="text-2xl text-gray-600 dark:text-gray-200">
                        {{ $t('public.no_account') }}
                    </div>
                    <div class="text-lg text-gray-400 dark:text-gray-600">
                        {{ $t('public.no_account_message') }}
                    </div>
                </div>
            </TabGroup>
        </div>

    </AuthenticatedLayout>
</template>
