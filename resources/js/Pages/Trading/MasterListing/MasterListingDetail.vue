<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import {ChevronRightIcon} from "@heroicons/vue/outline";
import MasterConfiguration from "@/Pages/AccountInfo/MasterAccount/MasterConfiguration.vue";
import {transactionFormat} from "@/Composables/index.js";
import Label from "@/Components/Label.vue";

const props = defineProps({
    masterListingDetail: Object,
})

const { formatAmount } = transactionFormat();
</script>

<template>
    <AuthenticatedLayout title="Master Configuration">
        <template #header>
            <div class="flex flex-col gap-2 md:flex-row items-center">
                <h2 class="text-xl font-semibold leading-tight">
                    <a class="hover:text-primary-500 dark:hover:text-primary-500" href="/trading/master_listing">{{ $t('public.sidebar.Trading') }}</a>
                </h2>
                <ChevronRightIcon aria-hidden="true" class="w-5 h-5" />
                <h2 class="text-xl font-semibold leading-tight">
                    Master Listing - {{ masterListingDetail.id }}
                </h2>
            </div>
        </template>

        <div class="flex gap-5 items-stretch">
            <div class="flex flex-col gap-4 items-start bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 p-5 w-3/4 rounded-lg shadow-lg">
                <div class="flex justify-between items-center self-stretch border-b border-gray-300 dark:border-gray-500 pb-2">
            <div class="flex items-center gap-3">
                <div class="text-lg">
                    Master Listing Detail
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 w-full">
            <div class="space-y-2">
                <Label
                    value="Name"
                />
                <div>
                    {{ masterListingDetail.user.name }}
                </div>
            </div>

            <div class="space-y-2">
                <Label
                    for="meta_login"
                    value="Meta Login"
                />
                <div>
                    {{ masterListingDetail.meta_login }}
                </div>
            </div>

            <div class="space-y-2">
                <Label
                    for="sharing_profit"
                    value="Sharing Profit (%)"
                />
                <div>
                    {{ masterListingDetail.sharing_profit }}
                </div>
            </div>

            <div class="space-y-2">
                <Label
                    for="min_join_equity"
                    value="Minimun Join Equity"
                />
                <div>
                    {{ masterListingDetail.min_join_equity }}
                </div>
            </div>

            <div class="space-y-2">
                <Label
                    for="subscription_fee"
                    value="Subscription Fee"
                />
                <div>
                    {{ masterListingDetail.min_join_equity }}
                </div>
            </div>

            <div class="space-y-2">
                <Label
                    for="roi_period"
                    value="ROI Period"
                />
                <div>
                    {{ masterListingDetail.roi_period }}
                </div>
            </div>

            <div class="space-y-2">
                <Label
                    for="signal_status"
                    value="Copy Trading Status"
                />
                <div>
                    {{ masterListingDetail.status }}
                </div>
            </div>
        </div>


            </div>

            <div class="flex flex-col gap-4 w-1/4">
                <div class="flex flex-col gap-2 items-stretch bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 p-5 w-full h-full rounded-lg shadow-lg">
                    <div class="text-sm">
                        Total Subscribers
                    </div>
                    <div class="text-base font-semibold">
                        {{ formatAmount(0) }}
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
