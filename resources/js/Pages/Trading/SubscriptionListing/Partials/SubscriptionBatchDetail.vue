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
const batchDetail = ref(null);
const managementFeeAmount = ref(0);

onMounted(async () => {
    try {
        const response = await axios.get('getPenaltyDetail?subscription_batch_id=' + props.subscription.id);
        batchDetail.value = response.data;
        managementFeeAmount.value = props.subscription.meta_balance * ((props.subscription.management_fee) / 100)
    } catch (error) {
        console.error(error);
    }
});

const closeModal = () => {
    emit('update:subscriptionModal', false);
}

const statusVariant = (autoRenewal) => {
    if (autoRenewal === 1) {
        return 'success';
    } else {
        return 'danger'
    }
}

const calculateWidthPercentage = (starting_date, expired_date, period) => {
    const startDate = new Date(starting_date);
    const endDate = new Date(expired_date);

    const currentDate = new Date();
    const elapsedMilliseconds = currentDate - startDate;
    const elapsedDays = Math.ceil(elapsedMilliseconds / (1000 * 60 * 60 * 24));

    const totalMilliseconds = endDate - startDate;
    const totalDays = Math.ceil(totalMilliseconds / (1000 * 60 * 60 * 24));

    // Adjust remaining time display based on the unit of the period
    const remainingTime = Math.ceil(period - elapsedDays);

    const widthResult = Math.max(0, Math.min(100, (elapsedDays / totalDays) * 100));

    return { widthResult, remainingTime };
};

const form = useForm({
    id: props.subscription.id,
    terms: ''
})

const submit = () => {
    form.post(route('trading.terminateBatch'), {
        onSuccess: () => {
            closeModal();
        },
    })
}

const termsModal = ref(false);

const openTermsModal = () => {
    termsModal.value = true
}

const closeTermsModal = () => {
    termsModal.value = false
}
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
                <div class="flex items-start justify-between gap-2 self-stretch">
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.amount')}}
                    </div>
                    <div class="text-sm sm:text-base text-primary-500 dark:text-primary-400 font-bold">
                        $ {{ formatAmount(subscription.meta_balance) }}
                    </div>
                </div>
                <div
                    v-if="batchDetail && subscription.status === 'Terminated'"
                    class="flex items-start justify-between gap-2 self-stretch"
                >
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.management_fee')}} (<span :class="{'text-error-500': batchDetail.penalty_percent > 0}">{{ formatAmount(batchDetail && Object.keys(batchDetail).length !== 0 ? batchDetail.penalty_percent : 0, 0) }}%</span>)
                    </div>
                    <div class="text-sm sm:text-base text-error-500 font-bold">
                        $ {{ formatAmount(batchDetail && Object.keys(batchDetail).length !== 0 ? batchDetail.penalty_amount : 0) }}
                    </div>
                </div>
                <div
                    v-if="batchDetail && subscription.status === 'Terminated'"
                    class="flex items-start justify-between gap-2 self-stretch"
                >
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.return_amount')}}
                    </div>
                    <div class="text-sm sm:text-base text-success-500 font-bold">
                        $ {{ formatAmount(subscription.meta_balance - (batchDetail && Object.keys(batchDetail).length !== 0 ? batchDetail.penalty_amount : 0)) }}
                    </div>
                </div>
                <div
                    v-if="batchDetail && subscription.status === 'Terminated'"
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
                    v-if="subscription.status === 'Active' || subscription.status === 'Expiring'"
                    class="flex items-center justify-between gap-2 self-stretch"
                >
                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.auto_renewal')}}
                    </div>
                    <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                        <Badge :variant="statusVariant(subscription.auto_renewal)">{{ subscription.auto_renewal === 1 ? $t('public.auto') : $t('public.expiring') }}</Badge>
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
        <div v-if="(subscription.status === 'Active' || subscription.status === 'Expiring') && subscription.terminateBadgeStatus" class="flex flex-col gap-3">
            <div class="text-gray-600 font-semibold">
                {{ $t('public.terminate_details') }}
            </div>
            <div class="grid gap-1">
                <div class="flex items-start justify-between gap-2 self-stretch">
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.management_fee')}} ({{ formatAmount(subscription.management_fee, 0) }}%)
                    </div>
                    <div class="text-sm sm:text-base text-error-500 font-bold">
                        $ {{ formatAmount(managementFeeAmount) }}
                    </div>
                </div>
                <div class="flex items-start justify-between gap-2 self-stretch">
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.return_amount')}}
                    </div>
                    <div class="text-sm sm:text-base text-success-500 font-bold">
                        $ {{ formatAmount(subscription.meta_balance - managementFeeAmount) }}
                    </div>
                </div>
            </div>
            <div class="flex items-center">
                <div class="flex items-center h-5">
                    <Checkbox id="terms" v-model="form.terms"/>
                </div>
                <div class="ml-3">
                    <label for="terms" class="text-gray-500 dark:text-gray-400 text-xs">
                        <span>{{ $t('public.agreement') }}</span>
                        <span
                            class="text-xs underline hover:cursor-pointer text-primary-500 hover:text-gray-700 dark:text-primary-600 dark:hover:text-primary-400"
                            @click="openTermsModal"
                        >
                            {{ $t('public.terms_and_conditions') }}
                        </span>
                    </label>
                </div>
            </div>
            <InputError :message="form.errors.terms" />
            <div class="flex justify-end">
                <Button
                    variant="transparent"
                    type="button"
                    class="justify-center"
                    @click.prevent="closeModal"
                >
                    {{$t('public.cancel')}}
                </Button>
                <Button
                    class="justify-center"
                    @click="submit"
                    :disabled="form.processing"
                >
                    {{$t('public.confirm')}}
                </Button>
            </div>
        </div>
    </div>

    <Modal :show="termsModal" :title="$t('public.terms_and_conditions')" @close="closeTermsModal">
        <div v-html="terms.contents" class="prose dark:text-white"></div>
        <div class="pt-4">
            <div class="text-gray-600 dark:text-gray-400">
                {{ $t('public.management_fee') }}
            </div>
            <div
                v-for="management_fee in subscription.master.master_management_fee"
                class="text-sm font-semibold dark:text-white"
            >
                {{ management_fee.penalty_days }} {{ $t('public.days') }} - {{ formatAmount(management_fee.penalty_percentage, 0) }} %
            </div>
        </div>
    </Modal>
</template>
