<script setup>
import {ref, watch, watchEffect} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import debounce from "lodash/debounce.js";
import {usePage} from "@inertiajs/vue3";
import NoData from "@/Components/NoData.vue";
import StatusBadge from "@/Components/StatusBadge.vue";
import TopUpPamm from "@/Pages/Pamm/PammListing/TopUpPamm.vue";;

const props = defineProps({
    terms: Object,
    search: String,
    type: String,
    walletSel: Object
})

const selectedPammSubscription = ref();
const pamm_subscriptions = ref({data: []})
const currentLocale = ref(usePage().props.locale);
const { formatAmount, formatDateTime } = transactionFormat();
const emit = defineEmits(['update:master', 'update:meta_login'])

const getResults = async (page = 1, search = '', type = '') => {
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
        selectedPammSubscription.value = pamm_subscriptions.value[0];
        if (selectedPammSubscription.value) {
            emit('update:master', selectedPammSubscription.value.master_id)
            emit('update:meta_login', selectedPammSubscription.value.meta_login)
        }
    } catch (error) {
        console.error(error);
    }
}

getResults();

const selectPammSubscription = (pamm) => {
    selectedPammSubscription.value = pamm;
    emit('update:master', pamm.master_id)
    emit('update:meta_login', pamm.meta_login)
}

watch(
    [() => props.search, () => props.type],
    debounce(([searchValue, typeValue]) => {
        getResults(1, searchValue, typeValue);
    }, 300)
);


watchEffect(() => {
    if (usePage().props.title !== null) {
        getResults();
    }
});
</script>

<template>
    <div class="py-5 w-full">
        <div
            v-if="pamm_subscriptions.length > 0"
            class="grid grid-cols-1 sm:grid-cols-3 gap-5 my-5"
        >
            <div
                v-for="pamm in pamm_subscriptions"
                class="flex flex-col items-start gap-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg p-5 w-full shadow-lg hover:bg-gray-50 hover:shadow-primary-300 hover:cursor-pointer"
                :class="{
                    'border-2 border-primary-400 dark:border-primary-300': selectedPammSubscription.id === pamm.id,
                }"
                @click="selectPammSubscription(pamm)"
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
                        <div class="text-sm">{{ $t('public.join_day') }}:</div>
                        <div class="text-sm font-semibold">{{ pamm.join_days }}</div>
                    </div>
                    <div class="flex gap-1" v-if="pamm.type === 'ESG'">
                        <div class="text-sm">{{ $t('public.valid_until') }}:</div>
                        <div class="text-sm font-semibold">{{ formatDateTime(pamm.expired_date, false) }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 w-full">
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
                            {{ $t('public.subscription_number') }}
                        </div>
                        <div class="flex justify-center items-center text-gray-800 dark:text-gray-100 font-semibold">
                            {{ pamm.subscription_number }}
                        </div>
                    </div>
                    <div v-if="pamm.master.type !== 'StandardGroup'" class="flex flex-col gap-1 items-center justify-center">
                        <div class="text-xs flex justify-center text-center">
                            {{ $t('public.product') }}
                        </div>
                        <div class="flex justify-center items-center text-gray-800 dark:text-gray-100 font-semibold">
                            {{ pamm.package ? '$ ' + formatAmount(pamm.package.amount, 2) + ' - ' : null }} {{ pamm.package_amount }}棵沉香树
                        </div>
                    </div>
                    <div class="flex flex-col gap-1 items-center justify-center">
                        <div class="text-xs flex justify-center">
                            {{ $t('public.account_no') }}
                        </div>
                        <div class="flex justify-center">
                            <span class="text-gray-800 dark:text-gray-100 font-semibold">{{ pamm.meta_login }} - $ {{ formatAmount(pamm.total_amount, 0) }}</span>
                        </div>
                    </div>
                    <div v-if="pamm.master.type !== 'StandardGroup' && pamm.max_out_amount" class="flex flex-col gap-1 items-center justify-center">
                        <div class="text-xs flex justify-center text-center">
                            {{ $t('public.max_out_amount') }}
                        </div>
                        <div class="flex justify-center items-center text-gray-800 dark:text-gray-100 font-semibold">
                            $ {{ formatAmount(pamm.max_out_amount, 0) }}
                        </div>
                    </div>
                    <!-- <div v-if="pamm.master.type !== 'StandardGroup'" class="flex flex-col gap-1 items-center justify-center">
                        <div class="text-xs flex justify-center text-center">
                            {{ $t('public.valid_until') }}
                        </div>
                        <div class="flex justify-center items-center text-gray-800 dark:text-gray-100 font-semibold">
                            {{ formatDateTime(pamm.expired_date, false) }}
                        </div>
                    </div> -->
                </div>
                <TopUpPamm
                    v-if="pamm.canTopUp"
                    :pamm="pamm"
                    :terms="terms"
                    :walletSel="walletSel"
                />
            </div>
        </div>
        <div v-else class="text-2xl flex w-full items-center justify-center">
            <NoData />
        </div>
    </div>
</template>
