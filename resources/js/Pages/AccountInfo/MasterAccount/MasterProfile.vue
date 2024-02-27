<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import {ChevronRightIcon} from "@heroicons/vue/outline";
import MasterConfiguration from "@/Pages/AccountInfo/MasterAccount/MasterConfiguration.vue";
import {transactionFormat} from "@/Composables/index.js";

const props = defineProps({
    masterAccount: Object,
    subscriberCount: Number,
})
const { formatAmount } = transactionFormat();
</script>

<template>
    <AuthenticatedLayout title="Master Configuration">
        <template #header>
            <div class="flex flex-col gap-2 md:flex-row items-center">
                <h2 class="text-xl font-semibold leading-tight">
                    <a class="hover:text-primary-500 dark:hover:text-primary-500" href="/account_info/account_listing">{{ $t('public.sidebar.Account Info') }}</a>
                </h2>
                <ChevronRightIcon aria-hidden="true" class="w-5 h-5" />
                <h2 class="text-xl font-semibold leading-tight">
                    Master Profile - {{ masterAccount.meta_login }}
                </h2>
            </div>
        </template>

        <div class="flex gap-5 items-stretch">
            <div class="flex flex-col gap-4 items-start bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 p-5 w-3/4 rounded-lg shadow-lg">
                <MasterConfiguration
                    :masterAccount="masterAccount"
                />
            </div>

            <div class="flex flex-col gap-4 w-1/4">
                <div class="flex flex-col gap-2 items-stretch bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 p-5 w-full h-full rounded-lg shadow-lg">
                    <div class="text-sm">
                        Total Subscribers
                    </div>
                    <div class="text-base font-semibold">
                        {{ subscriberCount }}
                    </div>
                </div>
                <div class="flex flex-col gap-2 items-stretch bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 p-5 w-full h-full rounded-lg shadow-lg">
                    <div class="text-sm">
                        Total Subscription Fees ($)
                    </div>
                    <div class="text-base font-semibold">
                        $ {{ formatAmount(0) }}
                    </div>
                </div>
                <div class="flex flex-col gap-2 items-stretch bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 p-5 w-full h-full rounded-lg shadow-lg">
                    <div class="text-sm">
                        Total Copy Trade Earnings ($)
                    </div>
                    <div class="text-base font-semibold">
                        $ {{ formatAmount(0) }}
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>
