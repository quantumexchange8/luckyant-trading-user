<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import StopRenewSubscription from "@/Pages/Trading/MasterListing/StopRenewSubscription.vue";
import Badge from "@/Components/Badge.vue";
import TerminateSubscription from "@/Pages/Trading/MasterListing/TerminateSubscription.vue";
import {ref} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import {usePage} from "@inertiajs/vue3";
import Button from "@/Components/Button.vue";
import CopyTradeTransaction from "@/Pages/Trading/SubscriptionListing/CopyTradeTransaction.vue";
import {EyeIcon} from "@heroicons/vue/outline";
import Modal from "@/Components/Modal.vue";
import SubscriptionHistory from "@/Pages/Trading/MasterListing/SubscriptionHistory.vue";

const subscriberAccounts = ref({data: []})
const { formatAmount, formatDateTime } = transactionFormat();
const currentLocale = ref(usePage().props.locale);
const isLoading = ref(false);
const approvalDate = ref('');
const unsubscribeDate = ref('');
const getResults = async (page = 1, search = '', type = '', date = '') => {
    isLoading.value = true
    try {
        let url = `/trading/getSubscriptions?page=${page}`;

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
        subscriberAccounts.value = response.data;
        approvalDate.value = response.data.approval_date;
        unsubscribeDate.value = response.data.unsubscribe_date;
    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false
    }
}

getResults();

const statusVariant = (status) => {
    if (status === 'Pending') return 'processing';
    if (status === 'Active') return 'success';
    if (status === 'Rejected' || 'Terminated') return 'danger';
}

const historyModal = ref(false);

const openHistoryModal = () => {
    historyModal.value = true;
}

const closeModal = () => {
    historyModal.value = false;
}
</script>

<template>
    <AuthenticatedLayout :title="$t('public.subscriptions')">
        <template #header>
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    {{ $t('public.subscriptions') }}
                </h2>

                 <Button
                     type="button"
                     size="sm"
                     v-slot="{ iconSizeClasses }"
                     class="flex gap-1"
                     external
                     :href="route('trading.subscription_history')"
                 >
                     <EyeIcon :class="iconSizeClasses" />
                     {{ $t('public.view_details') }}
                 </Button>
            </div>
        </template>

        <div
            class="grid grid-cols-1 sm:grid-cols-3 gap-5 my-5"
        >
            <div
                v-for="subscriberAccount in subscriberAccounts.data"
                class="flex flex-col items-start gap-3 bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg p-5 w-full shadow-lg hover:cursor-pointer hover:bg-gray-50 hover:shadow-primary-300"
            >
                <div class="flex justify-between items-center w-full">
                    <div class="flex gap-2 items-center">
                        <img
                            class="object-cover w-12 h-12 rounded-full"
                            :src="subscriberAccount.master.user.profile_photo ? subscriberAccount.master.user.profile_photo : 'https://img.freepik.com/free-icon/user_318-159711.jpg'"
                            alt="userPic"
                        />
                        <div class="flex flex-col">
                            <div class="flex gap-1 items-center">
                                <div v-if="currentLocale === 'en'" class="text-sm">
                                    {{ subscriberAccount.master.trading_user.name }}
                                </div>
                                <div v-if="currentLocale === 'cn'" class="text-sm">
                                    {{ subscriberAccount.master.trading_user.company ? subscriberAccount.master.trading_user.company : subscriberAccount.master.trading_user.name }}
                                </div>
                                <div class="bg-primary-100 text-primary-800 text-xs font-medium me-2 px-2.5 py-0.5 rounded-full dark:bg-primary-900 dark:text-primary-300">
                                    Master
                                </div>
                            </div>
                            <div class="font-semibold">
                                {{ subscriberAccount.master.meta_login }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-y border-gray-300 dark:border-gray-600 w-full py-1 flex items-center gap-2 flex justify-between">
                    <div class="flex gap-1">
                        <div class="text-sm">{{ $t('public.join_date') }}:</div>
                        <div class="text-sm font-semibold">{{ formatDateTime(subscriberAccount.subscription.approval_date, false) }}</div>
                    </div>
                    <div class="flex gap-1">
                        <div class="text-sm">{{ $t('public.join_day') }}:</div>
                        <div class="text-sm font-semibold">{{ subscriberAccount.join_days }}</div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 w-full">
                    <div class="space-y-1">
                        <div class="text-xs flex justify-center">
                            {{ $t('public.live_account') }}
                        </div>
                        <div class="flex justify-center">
                            <div v-if="currentLocale === 'en'" class="text-gray-800 dark:text-gray-100 font-semibold">
                                {{ subscriberAccount.trading_user.name }}
                            </div>
                            <div v-if="currentLocale === 'cn'" class="text-gray-800 dark:text-gray-100 font-semibold">
                                {{ subscriberAccount.trading_user.company ? subscriberAccount.trading_user.company : subscriberAccount.trading_user.name }}
                            </div>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <div class="text-xs flex justify-center">
                            {{ $t('public.account_number') }}
                        </div>
                        <div class="flex justify-center">
                            <span class="text-gray-800 dark:text-gray-100 font-semibold">{{ subscriberAccount.meta_login }}</span>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <div class="text-xs flex justify-center">
                            {{ $t('public.sharing_profit') }}
                        </div>
                        <div class="flex justify-center">
                            <span class="text-gray-800 dark:text-gray-100 font-semibold">{{ subscriberAccount.master.sharing_profit % 1 === 0 ? formatAmount(subscriberAccount.master.sharing_profit, 0) : formatAmount(subscriberAccount.master.sharing_profit) }}%</span>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <div class="text-xs flex justify-center">
                            {{ $t('public.amount') }}
                        </div>
                        <div class="flex justify-center">
                            <span class="text-gray-800 dark:text-gray-100 font-semibold">$ {{ formatAmount(subscriberAccount.subscription.meta_balance ? subscriberAccount.subscription.meta_balance : 0) }}</span>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <div class="text-xs flex justify-center">
                            {{ $t('public.estimated_roi') }}
                        </div>
                        <div class="flex justify-center">
                            <span class="text-gray-800 dark:text-gray-100 font-semibold">{{ subscriberAccount.master.estimated_monthly_returns }}</span>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <div class="text-xs flex justify-center">
                            {{ $t('public.roi_period') }}
                        </div>
                        <div class="flex justify-center">
                            <span class="text-gray-800 dark:text-gray-100 font-semibold">{{ subscriberAccount.master.roi_period }} {{ $t('public.days') }}</span>
                        </div>
                    </div>
                </div>

                <div class="flex w-full gap-2 items-center">
                    <StopRenewSubscription
                        v-if="subscriberAccount.subscription.status === 'Active'"
                        :subscriberAccount="subscriberAccount"
                    />
                    <TerminateSubscription
                        v-if="subscriberAccount.subscription.status === 'Active'"
                        :subscriberAccount="subscriberAccount"
                    />
                </div>
            </div>
        </div>

        <div class="p-5 my-5 bg-white overflow-hidden md:overflow-visible rounded-xl shadow-md dark:bg-gray-900">
            <div class="mb-4 font-semibold">
                {{ $t('public.copy_trade_transaction') }}
            </div>
            <CopyTradeTransaction />
        </div>

    </AuthenticatedLayout>
</template>
