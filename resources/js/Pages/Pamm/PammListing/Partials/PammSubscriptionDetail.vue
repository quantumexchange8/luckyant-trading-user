<script setup>
import Badge from "@/Components/Badge.vue";
import {onMounted, ref} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import {useForm, usePage} from "@inertiajs/vue3";
import StatusBadge from "@/Components/StatusBadge.vue";
import Button from "@/Components/Button.vue";
import Label from "@/Components/Label.vue";
import Checkbox from "@/Components/Checkbox.vue";
import InputError from "@/Components/InputError.vue";
import Modal from "@/Components/Modal.vue";

const props =  defineProps({
    subscription: Object,
    terms: Object,
})

const emit = defineEmits(['update:subscriptionModal']);
const { formatDateTime, formatAmount } = transactionFormat();
const currentLocale = ref(usePage().props.locale);
const pammDetail = ref(null);
const managementFeeAmount = ref(0);

onMounted(async () => {
    try {
        const response = await axios.get('getPammSubscriptionDetail?subscription_id=' + props.subscription.id);
        pammDetail.value = response.data;
        managementFeeAmount.value = props.subscription.meta_balance * ((props.subscription.management_fee) / 100)
    } catch (error) {
        console.error(error);
    }
});

const statusVariant = (autoRenewal) => {
    if (autoRenewal === 1) {
        return 'success';
    } else {
        return 'danger'
    }
}

const form = useForm({
    id: props.subscription.id,
    terms: ''
})

</script>

<template>
    <div class="flex flex-col gap-5">
        <div class="p-5 bg-gray-100 dark:bg-gray-800 rounded-lg">
            <div class="flex flex-col items-start gap-3 self-stretch">
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="text-lg font-semibold dark:text-white">
                        <div v-if="currentLocale === 'en'">
                            {{ subscription.master.trading_user.name }} - {{ subscription.master_meta_login }}
                        </div>
                        <div v-if="currentLocale === 'cn'">
                            {{ subscription.master.trading_user.company ? subscription.master.trading_user.company : subscription.master.trading_user.name }} - {{ subscription.master_meta_login }}
                        </div>
                    </div>
                    <StatusBadge
                        :value="subscription.status"
                        width="w-20"
                    />
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.account_number')}}
                    </div>
                    <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                        {{ subscription.meta_login }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.subscription_number')}}
                    </div>
                    <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                        {{ subscription.subscription_number }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.join_date')}}
                    </div>
                    <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                        {{ subscription.approval_date ? formatDateTime(subscription.approval_date, false) : $t('public.pending') }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.join_day')}}
                    </div>
                    <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                        {{ subscription.join_days }} {{ $t('public.days') }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.roi_period')}}
                    </div>
                    <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                        {{ subscription.settlement_period }}
                    </div>
                </div>
                <div class="flex items-start justify-between gap-2 self-stretch">
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.amount')}}
                    </div>
                    <div class="text-sm sm:text-base text-primary-500 dark:text-primary-400 font-bold">
                        $ {{ formatAmount(subscription.subscription_amount) }}
                    </div>
                </div>
                <div v-if="subscription.type === 'ESG'" class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.subscription_package')}}
                    </div>
                    <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                        {{ subscription.subscription_package_product }}
                    </div>
                </div>
                <div
                    v-if="pammDetail && subscription.status === 'Terminated'"
                    class="flex items-start justify-between gap-2 self-stretch"
                >
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.termination_date')}}
                    </div>
                    <div class="text-sm sm:text-base text-error-500 font-bold">
                        {{ formatDateTime(subscription ? subscription.termination_date : '-', false) }}
                    </div>
                </div>
                <div
                    v-if="subscription.status === 'Rejected'"
                    class="flex items-center justify-between gap-2 self-stretch"
                >
                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.remarks')}}
                    </div>
                    <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                        {{ subscription.remarks }}
                    </div>
                </div>
            </div>
        </div>
    </div>

</template>
