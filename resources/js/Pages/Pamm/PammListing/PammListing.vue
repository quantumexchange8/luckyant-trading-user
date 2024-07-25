<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import {SearchIcon} from "@heroicons/vue/outline";
import BaseListbox from "@/Components/BaseListbox.vue";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import Input from "@/Components/Input.vue";
import {computed, onMounted, ref, watch} from "vue";
import Button from "@/Components/Button.vue";
import SubscriptionForm from "@/Pages/Trading/MasterListing/SubscriptionForm.vue";
import {transactionFormat} from "@/Composables/index.js";
import debounce from "lodash/debounce.js";
import {usePage} from "@inertiajs/vue3";
import {Tab, TabGroup, TabList, TabPanel, TabPanels} from "@headlessui/vue";
import JoinPammForm from "@/Pages/Pamm/PammMaster/JoinPammForm.vue";
import NoData from "@/Components/NoData.vue";
import StatusBadge from "@/Components/StatusBadge.vue";
import PammReturn from "@/Pages/Pamm/PammListing/PammReturn.vue";
import TopUpPamm from "@/Pages/Pamm/PammListing/TopUpPamm.vue";

const props = defineProps({
    terms: Object,
    walletSel: Object,
})

const typeFilter = [
    {value: '', label:"All"},
    {value: 'max_equity', label:"Highest Equity to follow"},
    {value: 'min_equity', label:"Lowest Equity to follow"},
    {value: 'max_sub', label:"Most Subscribers"},
    {value: 'min_sub', label:"Least Subscribers"},
];

const pammTypes = [
    {value: 'ESG', label:"esg"},
    {value: 'Standard', label:"standard"},
]

const currentDomain = window.location.hostname;

onMounted(() => {
    if (currentDomain === 'member.luckyantfxgroup.com' || currentDomain === 'testmember.luckyantfxasia.com') {
        canTopUp.value = true
    }
})

const isLoading = ref(false);
const search = ref('');
const type = ref('');
const canTopUp = ref(false);
const pamm_subscriptions = ref({data: []})

const { formatAmount, formatDateTime } = transactionFormat();

const getResults = async (page = 1, search = '', type = '', date = '') => {
    isLoading.value = true
    try {
        let url = `/pamm/getPammSubscriptionData?page=${page}`;

        if (search) {
            url += `&search=${search}`;
        }

        if (type) {
            url += `&type=${type}`;
        }

        const response = await axios.get(url);
        pamm_subscriptions.value = response.data.pamm_subscriptions;
    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false
    }
}

getResults();

watch(
    [search, type],
    debounce(([searchValue, typeValue, dateValue]) => {
        getResults(1, searchValue, typeValue, dateValue);
    }, 300)
);

const clearFilter = () => {
    search.value = '';
    type.value = '';
}

const currentLocale = ref(usePage().props.locale);
</script>

<template>
    <AuthenticatedLayout :title="$t('public.pamm_subscriptions')">
        <template #header>
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    {{ $t('public.pamm_subscriptions') }}
                </h2>
            </div>
        </template>

        <div class="flex flex-col sm:flex-row items-center gap-5 self-stretch">
            <div class="w-full">
                <InputIconWrapper>
                    <template #icon>
                        <SearchIcon aria-hidden="true" class="w-5 h-5" />
                    </template>
                    <Input
                        withIcon
                        id="search"
                        type="text"
                        class="w-full block dark:border-transparent"
                        :placeholder="$t('public.search_name_and_account_no_placeholder')"
                        v-model="search"
                    />
                </InputIconWrapper>
            </div>
            <div class="w-full">
                <BaseListbox
                    v-model="type"
                    :options="typeFilter"
                    :placeholder="$t('public.filters_placeholder')"
                    class="w-full"
                />
            </div>

            <div class="w-full sm:w-auto">
                <Button
                    type="button"
                    variant="primary-transparent"
                    @click="clearFilter"
                    class="w-full justify-center"
                >
                    {{ $t('public.clear') }}
                </Button>
            </div>
        </div>

        <div class="py-5 w-full">
            <div
                v-if="pamm_subscriptions.data.length > 0"
                class="grid grid-cols-1 sm:grid-cols-3 gap-5 my-5"
            >
                <div
                    v-for="pamm in pamm_subscriptions.data"
                    class="flex flex-col items-start gap-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg p-5 w-full shadow-lg hover:bg-gray-50 hover:shadow-primary-300"
                >
                    <div class="flex justify-between items-center self-stretch">
                        <div class="flex gap-2 w-full">
                            <img
                                class="object-cover w-12 h-12 rounded-full"
                                :src="pamm.master.profile_pic ? pamm.master.profile_pic : 'https://img.freepik.com/free-icon/user_318-159711.jpg'"
                                alt="userPic"
                            />
                            <div class="flex flex-col">
                                <div v-if="currentLocale === 'en'" class="text-sm">
                                    {{ pamm.trading_user.name }}
                                </div>
                                <div v-if="currentLocale === 'cn'" class="text-sm">
                                    {{ pamm.trading_user.company ? pamm.trading_user.company : pamm.trading_user.name }}
                                </div>
                                <div class="font-semibold">
                                    {{ pamm.master_meta_login }}
                                </div>
                            </div>
                        </div>
                        <StatusBadge :value="pamm.status" width="w-20"/>
                    </div>

                    <div class="border-y border-gray-300 dark:border-gray-600 w-full py-1 flex items-center gap-2 justify-between">
                        <div class="flex gap-1">
                            <div class="text-sm">{{ $t('public.join_date') }}:</div>
                            <div class="text-sm font-semibold">{{ pamm.approval_date ? formatDateTime(pamm.approval_date, false) : $t('public.pending') }}</div>
                        </div>
                        <div class="flex gap-1">
                            <div class="text-sm">{{ $t('public.join_day') }}:</div>
                            <div class="text-sm font-semibold">{{ pamm.join_days }}</div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 w-full">
                        <!--                <div class="col-span-2">-->
                        <!--                    chart-->
                        <!--                </div>-->
                        <div class="flex flex-col gap-1 items-center justify-center">
                            <div class="text-xs flex justify-center text-center">
                                {{ $t('public.sharing_profit') }}
                            </div>
                            <div class="flex justify-center items-center text-gray-800 dark:text-gray-100 font-semibold">
                                {{ formatAmount(pamm.master.sharing_profit, 0) }} %
                            </div>
                        </div>
                        <div class="flex flex-col gap-1 items-center justify-center">
                            <div class="text-xs flex justify-center text-center">
                                {{ $t('public.roi_period') }}
                            </div>
                            <div class="flex justify-center items-center text-gray-800 dark:text-gray-100 font-semibold">
                                {{ pamm.settlement_period }} {{ $t('public.days') }}
                            </div>
                        </div>
                        <div class="flex flex-col gap-1 items-center justify-center">
                            <div class="text-xs flex justify-center text-center">
                                {{ $t('public.subscription_number') }}
                            </div>
                            <div class="flex justify-center items-center text-gray-800 dark:text-gray-100 font-semibold">
                                {{ pamm.subscription_number }}
                            </div>
                        </div>
                        <div class="flex flex-col gap-1 items-center justify-center">
                            <div class="text-xs flex justify-center text-center">
                                {{ $t('public.product') }}
                            </div>
                            <div class="flex justify-center items-center text-gray-800 dark:text-gray-100 font-semibold">
                                {{ pamm.package ? '$ ' + formatAmount(pamm.package.amount, 2) + ' - ' : null }} {{ pamm.subscription_package_product }}
                            </div>
                        </div>
                        <div class="flex flex-col gap-1 items-center justify-center">
                            <div class="text-xs flex justify-center">
                                {{ $t('public.fund') }}
                            </div>
                            <div class="flex justify-center">
                                <span class="text-gray-800 dark:text-gray-100 font-semibold">$ {{ formatAmount(pamm.subscription_amount, 0) }}</span>
                            </div>
                        </div>
                        <div v-if="pamm.max_out_amount" class="flex flex-col gap-1 items-center justify-center">
                            <div class="text-xs flex justify-center text-center">
                                {{ $t('public.max_out_amount') }}
                            </div>
                            <div class="flex justify-center items-center text-gray-800 dark:text-gray-100 font-semibold">
                                $ {{ formatAmount(pamm.max_out_amount, 0) }}
                            </div>
                        </div>
                        <div v-else class="flex flex-col gap-1 items-center justify-center">
                            <div class="text-xs flex justify-center text-center">
                                {{ $t('public.valid_until') }}
                            </div>
                            <div class="flex justify-center items-center text-gray-800 dark:text-gray-100 font-semibold">
                                {{ formatDateTime(pamm.expired_date, false) }}
                            </div>
                        </div>
                    </div>
                    <TopUpPamm
                        v-if="canTopUp"
                        :pamm="pamm"
                        :walletSel="walletSel"
                    />
                </div>
            </div>
            <div v-else class="text-2xl flex w-full items-center justify-center">
                <NoData />
            </div>
        </div>
        <PammReturn />
    </AuthenticatedLayout>
</template>
