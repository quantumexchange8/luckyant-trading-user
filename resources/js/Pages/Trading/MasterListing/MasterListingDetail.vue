<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import {ChevronRightIcon, XIcon} from "@heroicons/vue/outline";
import MasterConfiguration from "@/Pages/AccountInfo/MasterAccount/MasterConfiguration.vue";
import {transactionFormat} from "@/Composables/index.js";
import Label from "@/Components/Label.vue";
import CountryLists from "../../../../../public/data/countries.json";
import Button from "@/Components/Button.vue";
import Input from "@/Components/Input.vue";
import InputError from "@/Components/InputError.vue";
import {RadioGroup, RadioGroupLabel, RadioGroupOption} from "@headlessui/vue";
import BaseListbox from "@/Components/BaseListbox.vue";
import Badge from "@/Components/Badge.vue";
import AvatarInput from "@/Pages/Profile/Partials/AvatarInput.vue";
import MasterTradeChart from "@/Pages/Trading/MasterListing/MasterTradeChart.vue";
import MasterTradeHistory from "@/Pages/Trading/MasterListing/MasterDetail/MasterTradeHistory.vue";
import {ref} from "vue";
import {usePage} from "@inertiajs/vue3";

const props = defineProps({
    masterListingDetail: Object,
})

const { formatAmount } = transactionFormat();
const currentLocale = ref(usePage().props.locale);

const statusVariant = (status) => {
    if (status === 'Pending') return 'processing';
    if (status === 'Active') return 'success';
    if (status === 'Rejected' || status === 'Terminated') return 'danger';
}
</script>

<template>
    <AuthenticatedLayout :title="$t('public.master_configuration')">
        <template #header>
            <div class="flex gap-2 items-center">
                <h2 class="text-xl font-semibold leading-tight">
                    <a class="hover:text-primary-500 dark:hover:text-primary-500" href="/trading/master_listing">{{ $t('public.sidebar.copy_trading') }}</a>
                </h2>
                <ChevronRightIcon aria-hidden="true" class="w-5 h-5" />
                <div class="flex gap-1 text-xl text-primary-500 font-semibold leading-tight">
                    <h2>
                        {{ $t('public.master_profile') }} -
                    </h2>
                    <div v-if="currentLocale === 'en'">
                        {{ masterListingDetail.user.username }}
                    </div>
                    <div v-if="currentLocale === 'cn'">
                        {{ masterListingDetail.trading_user.company ? masterListingDetail.trading_user.company : masterListingDetail.trading_user.name }}
                    </div>
                </div>
            </div>
        </template>

        <div class="flex flex-col sm:flex-row gap-5 items-stretch">
            <div class="flex flex-col gap-4 items-start bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 p-5 w-full sm:w-3/4 rounded-lg shadow-lg">
                <div class="flex justify-between items-center self-stretch">
                    <div class="flex items-center justify-between w-full gap-3">
                        <div class="text-lg">
                            {{ $t('public.master_detail') }}
                        </div>
                        <Badge
                            :variant="statusVariant(masterListingDetail.status)"
                            width="auto"
                        >
                            <span class="text-sm">{{ masterListingDetail.status }}</span>
                        </Badge>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row gap-5 w-full">
                    <div class="flex flex-col gap-4 items-center justify-center w-full sm:w-1/3">
                        <img
                            class="object-cover w-24 h-24 rounded-full"
                            :src="masterListingDetail.user.profile_photo_url ? masterListingDetail.user.profile_photo_url : 'https://img.freepik.com/free-icon/user_318-159711.jpg'"
                            alt="userPic"
                        />
                        <div class="flex flex-col items-center">
                            <div class="font-semibold text-gray-800 dark:text-white">
                                <div v-if="currentLocale === 'en'">
                                    {{ masterListingDetail.user.username }}
                                </div>
                                <div v-if="currentLocale === 'cn'">
                                    {{ masterListingDetail.trading_user.company ? masterListingDetail.trading_user.company : masterListingDetail.trading_user.name }}
                                </div>
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                {{ masterListingDetail.meta_login }}
                            </div>
                        </div>
                        <div class="flex w-full gap-4 items-center justify-center">
                            <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                                {{ $t('public.total_subscribers') }}
                            </div>
                            <div class="text-xl font-semibold">
                                {{ masterListingDetail.total_subscribers }}
                            </div>
                        </div>
                    </div>
                    <div class="w-full sm:w-2/3">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                                    {{ $t('public.sharing_profit') }}
                                </div>
                                <div class="text-xl">
                                    {{ masterListingDetail.sharing_profit }} %
                                </div>
                            </div>
                            <div class="space-y-1">
                                <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                                    {{ $t('public.min_join_equity') }}
                                </div>
                                <div class="text-xl">
                                    $ {{ formatAmount(masterListingDetail.min_join_equity) }}
                                </div>
                            </div>
                            <div class="space-y-1">
                                <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                                    {{ $t('public.subscription_fee') }}
                                </div>
                                <div class="text-xl">
                                    $ {{ formatAmount(masterListingDetail.subscription_fee) }}
                                </div>
                            </div>
                            <div class="space-y-1">
                                <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                                    {{ $t('public.roi_period') }}
                                </div>
                                <div class="text-xl">
                                    {{ masterListingDetail.roi_period }} Days
                                </div>
                            </div>

                            <div class="flex flex-col gap-2 self-stretch col-span-2">
                                <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                                    {{ $t('public.total_fund') }}
                                </div>
                                <div class="mb-1 flex h-2.5 w-full overflow-hidden rounded-full bg-gray-300 dark:bg-gray-400 text-xs">
                                    <div
                                        :style="{ width: `${masterListingDetail.totalFundWidth}%` }"
                                        class="rounded-full bg-gradient-to-r from-primary-300 to-primary-600 dark:from-primary-500 dark:to-primary-800 transition-all duration-500 ease-out"
                                    >
                                    </div>
                                </div>
                                <div class="mb-2 flex items-center justify-between text-xs">
                                    <div class="dark:text-gray-400">
                                        $ 1
                                    </div>
                                    <div class="dark:text-gray-400">
                                        $ {{ formatAmount(masterListingDetail.total_fund/2) }}
                                    </div>
                                    <div class="dark:text-gray-400">$ {{ masterListingDetail.total_fund }}</div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-4 w-full sm:w-1/4">
                <div class="flex flex-col gap-2 items-stretch bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 p-5 w-full h-full rounded-lg shadow-lg">
                    <div class="text-lg font-semibold">
                        {{ $t('public.most_traded_products') }}
                    </div>
                    <MasterTradeChart
                        :meta_login="masterListingDetail.meta_login"
                    />
                </div>
            </div>
        </div>

        <div class="p-5 my-5 mb-28 bg-white overflow-hidden md:overflow-visible rounded-lg shadow-lg dark:bg-gray-900 border border-gray-300">
            <MasterTradeHistory
                :meta_login="masterListingDetail.meta_login"
            />
        </div>
    </AuthenticatedLayout>
</template>
