<script setup>
import {
    IconCalendarCancel,
    IconCalendarCheck,
    IconUserDollar
} from "@tabler/icons-vue";
import Card from "primevue/card";
import Skeleton from "primevue/skeleton";
import {ref, watch, watchEffect} from "vue";
import Button from "primevue/button";
import Paginator from "primevue/paginator";
import Tag from "primevue/tag";
import {transactionFormat} from "@/Composables/index.js";
import {useLangObserver} from "@/Composables/localeObserver.js";
import dayjs from "dayjs";
import SubscriptionAccountAction from "@/Pages/CopyTrading/Partials/SubscriptionAccountAction.vue";
import {usePage} from "@inertiajs/vue3";

const props = defineProps({
    strategyType: String,
    copyTradesCount: Number,
})

const subscriptions = ref([]);
const isLoading = ref(false);
const currentPage = ref(1);
const rowsPerPage = ref(6);
const totalRecords = ref(0);
const {formatAmount, formatType} = transactionFormat();
const {locale} = useLangObserver();
const selectedSubscriber = ref();
const emit = defineEmits(['update:master_id', 'update:meta_login'])

const getResults = async (page = 1, rowsPerPage = 6) => {
    isLoading.value = true;

    try {
        let url = `/${props.strategyType}_strategy/getCopyTradeAccounts?page=${page}&limit=${rowsPerPage}`;

        const response = await axios.get(url);
        subscriptions.value = response.data.subscriptions.data;
        totalRecords.value = response.data.totalRecords;
        currentPage.value = response.data.currentPage;

        selectedSubscriber.value = subscriptions.value[0]
        if (selectedSubscriber.value) {
            emit('update:master_id', selectedSubscriber.value.master_id)
            emit('update:meta_login', selectedSubscriber.value.meta_login)
        }
    } catch (error) {
        console.error('Error getting subscriptions:', error);
    } finally {
        isLoading.value = false;
    }
};

// Initial call to populate data
getResults(currentPage.value, rowsPerPage.value);

const onPageChange = (event) => {
    currentPage.value = event.page + 1;
    getResults(currentPage.value, rowsPerPage.value);
};

const getSeverity = (status) => {
    switch (status) {
        case 'Terminated':
            return 'danger';

        case 'Unsubscribed':
            return 'danger';

        case 'Rejected':
            return 'danger';

        case 'Subscribing':
            return 'success';

        case 'Switched':
            return 'secondary';

        case 'Pending':
            return 'info';

        case 'Expiring':
            return 'warning';
    }
}

const getJoinedDays = (subscriber) => {
    const approvalDate = dayjs(subscriber.approval_date);
    const endDate =
        subscriber.status === 'Unsubscribed'
            ? dayjs(subscriber.unsubscribe_date)
            : dayjs(); // Use today's date if not terminated

    return endDate.diff(approvalDate, 'day'); // Calculate the difference in days
};

const selectSubscriberAccount = (subscriber) => {
    selectedSubscriber.value = subscriber;
    emit('update:master_id', subscriber.master_id)
    emit('update:meta_login', subscriber.meta_login)
}

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getResults(currentPage.value, rowsPerPage.value);
    }
});
</script>

<template>
    <div v-if="isLoading" class="grid grid-cols-1 md:grid-cols-2 3xl:grid-cols-4 gap-5 self-stretch">
        <Card
            v-for="(master, index) in copyTradesCount > 6 ? 6 : copyTradesCount"
            :key="index"
        >
            <template #content>
                <div class="flex flex-col items-center gap-4 self-stretch">
                    <!-- Profile Section -->
                    <div class="w-full flex items-center gap-4 self-stretch">
                        <img
                            class="object-cover w-10 h-10 rounded-full"
                            :src="master.profile_photo ? master.profile_photo : 'https://img.freepik.com/free-icon/user_318-159711.jpg'"
                            alt="masterPic"
                        />
                        <div class="flex flex-col items-start">
                            <Skeleton width="10rem" class="my-1"></Skeleton>
                            <Skeleton width="5rem" class="mt-1"></Skeleton>
                        </div>
                    </div>

                    <!-- StatusBadge Section -->
                    <div class="flex items-center gap-2 self-stretch">
                        <Skeleton width="5rem" height="1.5rem"></Skeleton>
                        <Skeleton width="5rem" height="1.5rem"></Skeleton>
                    </div>

                    <!-- Performance Section -->
                    <div class="py-2 flex justify-center items-center gap-2 self-stretch border-y border-solid border-gray-200 dark:border-gray-700">
                        <div class="w-full flex flex-col items-center">
                            <Skeleton width="5rem" class="my-1"></Skeleton>
                            <div class="self-stretch text-gray-500 text-center text-xs">
                                {{ $t('public.settlement') }} ({{ $t('public.days') }})
                            </div>
                        </div>
                        <div class="w-full flex flex-col items-center">
                            <Skeleton width="5rem" class="my-1"></Skeleton>
                            <div class="self-stretch text-gray-500 text-center text-xs">
                                {{ $t('public.estimated_monthly_returns') }}
                            </div>
                        </div>
                        <div class="w-full flex flex-col items-center">
                            <Skeleton width="5rem" class="my-1"></Skeleton>
                            <div class="self-stretch text-gray-500 text-center text-xs">
                                {{ $t('public.estimated_lot_size') }}
                            </div>
                        </div>
                    </div>

                    <!-- Details Section -->
                    <div class="flex items-end justify-between self-stretch">
                        <div class="flex flex-col items-center gap-1 self-stretch w-full">
                            <div class="py-1 flex items-center gap-3 self-stretch w-full text-gray-500">
                                <IconUserDollar size="20" stroke-width="1.25" />
                                <Skeleton width="5rem"></Skeleton>
                            </div>
                            <div class="py-1 flex items-center gap-3 self-stretch text-gray-500">
                                <IconCalendarCheck size="20" stroke-width="1.25" />
                                <Skeleton width="5rem"></Skeleton>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </Card>
    </div>

    <div v-else class="w-full">
        <div class="grid grid-cols-1 md:grid-cols-2 3xl:grid-cols-4 gap-5 self-stretch">
            <Card
                v-for="(subscriber, index) in subscriptions"
                :key="index"
                @click="selectSubscriberAccount(subscriber)"
                class="hover:cursor-pointer hover:bg-gray-50 dark:hover:border-2 dark:hover:border-primary-500"
                :class="{
                    'border-2 border-primary-500 dark:border-primary-600': selectedSubscriber.id === subscriber.id,
                }"
            >
                <template #content>
                    <div class="flex flex-col items-center gap-4 self-stretch">
                        <!-- Profile Section -->
                        <div class="w-full flex items-start gap-4 self-stretch">
                            <div class="flex flex-col items-start">
                                <div class="self-stretch truncate text-gray-950 dark:text-white font-bold">
                                    <div v-if="locale === 'cn'">
                                        {{ subscriber.master.trading_user.company ? subscriber.master.trading_user.company : subscriber.master.trading_user.name }}
                                    </div>
                                    <div v-else>
                                        {{ subscriber.master.trading_user.name }}
                                    </div>
                                </div>
                                <div class="self-stretch truncate text-gray-500 text-sm">
                                    {{ subscriber.master.meta_login }}
                                </div>
                            </div>
                            <Tag :severity="getSeverity(subscriber.status)" class="text-nowrap">
                                {{ $t(`public.${formatType(subscriber.status).toLowerCase().replace(/\s+/g, '_')}`) }}
                            </Tag>
                            <div class="w-full hidden md:flex justify-end text-xl font-semibold dark:text-white">
                                $ {{ formatAmount(subscriber.subscription.meta_balance, 0) }}
                            </div>
                        </div>
                        <div class="w-full flex md:hidden text-lg font-semibold dark:text-white">
                            $ {{ formatAmount(subscriber.subscription.meta_balance, 0) }}
                        </div>
                        <!-- StatusBadge Section -->
                        <div class="flex items-center gap-2 self-stretch">
                            <Tag severity="primary">
                                {{ subscriber.subscription.subscription_number }}
                            </Tag>
                            <Tag severity="secondary">
                                {{ getJoinedDays(subscriber) }} {{ $t('public.join_day') }}
                            </Tag>
                        </div>

                        <!-- Performance Section -->
                        <div class="py-2 flex justify-center items-start gap-2 self-stretch border-y border-solid border-gray-200 dark:border-gray-700">
                            <div class="min-w-16 sm:w-full flex flex-col items-center">
                                <div class="self-stretch text-gray-950 dark:text-white text-center font-semibold">
                                    {{ formatAmount(subscriber.roi_period, 0) }} {{ $t('public.days') }}
                                </div>
                                <div class="self-stretch text-gray-500 text-center text-xs">
                                    {{ $t('public.settlement') }}
                                </div>
                            </div>
                            <div class="min-w-[80px] sm:w-full flex flex-col items-center">
                                <div class="self-stretch text-gray-950 dark:text-white text-center font-semibold">
                                    {{ formatAmount(subscriber.master.sharing_profit,0) }}%
                                </div>
                                <div class="self-stretch text-gray-500 text-center text-xs">
                                    {{ $t('public.sharing_profit') }}
                                </div>
                            </div>
                            <div class="min-w-[100px] sm:w-full flex flex-col items-center">
                                <div class="self-stretch text-gray-950 dark:text-white text-center font-semibold">
                                    {{ subscriber.master.estimated_monthly_returns }}
                                </div>
                                <div class="self-stretch text-gray-500 text-center text-xs">
                                    {{ $t('public.estimated_roi') }}
                                </div>
                            </div>
                        </div>

                        <!-- Details Section -->
                        <div class="flex items-end justify-between self-stretch">
                            <div class="flex flex-col items-center gap-1 self-stretch w-full">
                                <div class="py-1 flex items-center gap-3 self-stretch w-full text-gray-500">
                                    <IconUserDollar size="20" stroke-width="1.25" />
                                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                                        {{ subscriber.meta_login }}
                                    </div>
                                </div>
                                <div class="py-1 flex items-center gap-3 self-stretch text-gray-500">
                                    <IconCalendarCheck size="20" stroke-width="1.25" />
                                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                                        <span class="text-primary-500">{{ subscriber.approval_date ? dayjs(subscriber.approval_date).format('YYYY/MM/DD') : $t('public.pending') }}</span> {{ $t('public.joined') }}
                                    </div>
                                </div>
                                <div v-if="subscriber.status === 'Unsubscribed'" class="py-1 flex items-center gap-3 self-stretch text-gray-500">
                                    <IconCalendarCancel size="20" stroke-width="1.25" />
                                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                                        <span class="text-error-500">{{ subscriber.unsubscribe_date ? dayjs(subscriber.unsubscribe_date).format('YYYY/MM/DD') : $t('public.pending') }}</span> {{ $t('public.terminated') }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Actions -->
                        <SubscriptionAccountAction
                            :strategyType="strategyType"
                            :subscriber="subscriber"
                        />
                    </div>
                </template>
            </Card>
        </div>
        <Paginator
            :first="(currentPage - 1) * rowsPerPage"
            :rows="rowsPerPage"
            :totalRecords="totalRecords"
            @page="onPageChange"
        />
    </div>
</template>
