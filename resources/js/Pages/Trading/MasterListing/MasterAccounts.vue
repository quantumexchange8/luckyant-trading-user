<script setup>
import Button from "@/Components/Button.vue";
import BaseListbox from "@/Components/BaseListbox.vue";
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import {SearchIcon} from "@heroicons/vue/outline";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import Input from "@/Components/Input.vue";
import {ref, watch} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import debounce from "lodash/debounce.js";
import SubscriptionForm from "@/Pages/Trading/MasterListing/SubscriptionForm.vue";
import {usePage} from "@inertiajs/vue3";

const props = defineProps({
    terms: Object
})

const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});

const typeFilter = [
    {value: '', label:"All"},
    {value: 'max_equity', label:"Highest Equity to follow"},
    {value: 'min_equity', label:"Lowest Equity to follow"},
    {value: 'max_sub', label:"Most Subscribers"},
    {value: 'min_sub', label:"Least Subscribers"},
];

const categories = ref({});
const isLoading = ref(false);
const date = ref('');
const search = ref('');
const refresh = ref(false);
const type = ref('');
const masterAccounts = ref({data: []})
const currentPage = ref(1);
const { formatAmount } = transactionFormat();

const getResults = async (page = 1, search = '', type = '', date = '') => {
    isLoading.value = true
    try {
        let url = `/trading/getMasterAccounts?page=${page}`;

        if (search) {
            url += `&search=${search}`;
        }

        if (type) {
            url += `&type=${type}`;
        }

        if (date) {
            url += `&date=${date}`;
        }

        const response = await axios.get(url);
        masterAccounts.value = response.data;
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

const openDetails = (masterAccountID) => {
    const detailUrl = `/trading/master_listing/${masterAccountID}`;
    window.location.href = detailUrl;
}

const currentLocale = ref(usePage().props.locale);
</script>

<template>
    <div class="flex justify-end">
        <div class="flex flex-wrap gap-3 items-center sm:flex-nowrap w-full sm:w-1/2">
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
    </div>

    <div
        class="grid grid-cols-1 sm:grid-cols-3 gap-5 my-5"
    >
        <div
            v-for="masterAccount in masterAccounts.data"
            class="flex flex-col items-start gap-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg p-5 w-full shadow-lg hover:bg-gray-50 hover:shadow-primary-300"
        >
            <div class="flex justify-between w-full">
                <img
                    class="object-cover w-12 h-12 rounded-full"
                    :src="masterAccount.user.profile_photo_url ? masterAccount.user.profile_photo_url : 'https://img.freepik.com/free-icon/user_318-159711.jpg'"
                    alt="userPic"
                />
                <div class="flex flex-col text-right">
                    <div v-if="currentLocale === 'en'" class="text-sm">
                        {{ masterAccount.trading_user.name }}
                    </div>
                    <div v-if="currentLocale === 'cn'" class="text-sm">
                        {{ masterAccount.trading_user.company ? masterAccount.trading_user.company : masterAccount.trading_user.name }}
                    </div>
                    <div class="font-semibold">
                        {{ masterAccount.meta_login }}
                    </div>
                </div>
            </div>

            <div class="border-y border-gray-300 dark:border-gray-600 w-full py-1 flex items-center gap-2">
                <div class="text-sm">{{ $t('public.min_join_equity') }}:</div>
                <div class="text-sm font-semibold">$ {{ formatAmount(masterAccount.min_join_equity) }}</div>
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
                        {{ formatAmount(masterAccount.sharing_profit, 0) }} %
                    </div>
                </div>
                <div class="flex flex-col gap-1 items-center justify-center">
                    <div class="text-xs flex justify-center text-center">
                        {{ $t('public.roi_period') }}
                    </div>
                    <div class="flex justify-center items-center text-gray-800 dark:text-gray-100 font-semibold">
                        {{ masterAccount.roi_period }} {{ $t('public.days') }}
                    </div>
                </div>
                <div class="flex flex-col gap-1 items-center justify-center">
                    <div class="text-xs flex justify-center text-center">
                        {{ $t('public.estimated_monthly_returns') }}
                    </div>
                    <div class="flex justify-center items-center text-gray-800 dark:text-gray-100 font-semibold">
                        {{ masterAccount.estimated_monthly_returns }}
                    </div>
                </div>
                <div class="flex flex-col gap-1 items-center justify-center">
                    <div class="text-xs flex justify-center text-center">
                        {{ $t('public.estimated_lot_size') }}
                    </div>
                    <div class="flex justify-center items-center text-gray-800 dark:text-gray-100 font-semibold">
                        {{ masterAccount.estimated_lot_size }} {{ $t('public.lot') }}
                    </div>
                </div>
            </div>

            <div class="flex w-full gap-2 items-center">
                <SubscriptionForm
                    :masterAccount="masterAccount"
                    :terms="terms"
                />
                <Button
                    type="button"
                    variant="primary-transparent"
                    class="w-full flex justify-center"
                    @click.prevent="openDetails(masterAccount.id)"
                >
                    {{ $t('public.view_details') }}
                </Button>
            </div>

        </div>
    </div>
</template>
