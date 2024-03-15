<script setup>
import Button from "@/Components/Button.vue";
import {CreditCardXIcon, CreditCardCheckIcon} from "@/Components/Icons/outline.jsx";
import Modal from "@/Components/Modal.vue";
import {computed, ref} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import {useForm} from "@inertiajs/vue3";
import Badge from "@/Components/Badge.vue";

const props = defineProps({
    subscriberAccount: Object
})

const renewSubscriptionModal = ref(false);
const { formatDateTime, formatAmount } = transactionFormat();

const openRenewSubscriptionModal = () => {
    renewSubscriptionModal.value = true;
}

const form = useForm({
    subscription_id: props.subscriberAccount.subscription.id,
    action: ''
})

const closeModal = () => {
    renewSubscriptionModal.value = false;
}

const submit = () => {
    if (props.subscriberAccount.subscription.auto_renewal === 1) {
        form.action = 'stop_renewal'
    } else {
        form.action = 'request_auto_renewal'
    }
    form.post(route('trading.renewalSubscription'), {
        onSuccess: () => {
            closeModal();
        },
    })
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

const statusVariant = (autoRenewal) => {
    if (autoRenewal === 1) {
        return 'success';
    } else {
        return 'danger'
    }
}

const expiredDate = ref(props.subscriberAccount.subscription.expired_date);
const isExpiredWithin24Hours = computed(() => {
    if (!expiredDate.value) {
        return false; // No expiration date, button should be enabled
    }
    const currentTime = new Date(); // Current time in milliseconds
    const expirationTime = new Date(expiredDate.value); // Expiration time in milliseconds
    const diffInHours = (expirationTime - currentTime) / (1000 * 60 * 60); // Difference in hours

    return diffInHours <= 24; // Disable button if within 24 hours
});
</script>

<template>
    <Button
        v-if="subscriberAccount.subscription.auto_renewal === 1"
        type="button"
        variant="warning"
        size="sm"
        class="flex gap-2 justify-center w-full"
        v-slot="{ iconSizeClasses }"
        @click="openRenewSubscriptionModal"
    >
        <CreditCardXIcon
            aria-hidden="true"
            :class="iconSizeClasses"
        />
        {{ $t('public.stop_renewal') }}
    </Button>
    <Button
        v-else
        type="button"
        variant="warning"
        size="sm"
        class="flex gap-2 justify-center w-full"
        v-slot="{ iconSizeClasses }"
        :disabled="isExpiredWithin24Hours"
        @click="openRenewSubscriptionModal"
    >
        <CreditCardCheckIcon
            aria-hidden="true"
            :class="iconSizeClasses"
        />
        {{ $t('public.request_renewal') }}
    </Button>

    <Modal :show="renewSubscriptionModal" :title="subscriberAccount.subscription.auto_renewal === 1 ? $t('public.stop_renewal') : $t('public.request_renewal')" @close="closeModal">
        <div class="p-5 bg-gray-100 dark:bg-gray-600 rounded-lg">
            <div class="flex flex-col items-start gap-3 self-stretch">
                <div class="text-lg font-semibold">
                    {{$t('public.subscription_details')}}
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.account_number')}}
                    </div>
                    <div class="text-base text-gray-800 dark:text-white font-semibold">
                        {{ subscriberAccount.subscription.meta_login }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.subscription_number')}}
                    </div>
                    <div class="text-base text-gray-800 dark:text-white font-semibold">
                        {{ subscriberAccount.subscription.subscription_number }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.subscription_fee')}}
                    </div>
                    <div class="text-base text-gray-800 dark:text-white font-semibold">
                        $ {{ subscriberAccount.subscription.subscription_fee ? formatAmount(subscriberAccount.subscription.subscription_fee) : '0.00' }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.roi_date')}} ({{ subscriberAccount.subscription.subscription_period }} Days)
                    </div>
                    <div class="text-base text-gray-800 dark:text-white font-semibold">
                        {{ formatDateTime(subscriberAccount.subscription.next_pay_date, false) }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.auto_renewal')}}
                    </div>
                    <div class="text-base text-gray-800 dark:text-white font-semibold">
                        <Badge :variant="statusVariant(subscriberAccount.subscription.auto_renewal)">{{ subscriberAccount.subscription.auto_renewal === 1 ? 'Auto' : 'Stop' }}</Badge>
                    </div>
                </div>
                <div class="flex flex-col gap-2 self-stretch">
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.progress')}}
                    </div>
                    <div class="mb-1 flex h-2.5 w-full overflow-hidden rounded-full bg-gray-300 dark:bg-gray-400 text-xs">
                        <div
                            :style="{ width: `${calculateWidthPercentage(subscriberAccount.subscription.created_at, subscriberAccount.subscription.expired_date, subscriberAccount.subscription.subscription_period).widthResult}%` }"
                            class="rounded-full bg-gradient-to-r from-primary-300 to-success-400 dark:from-primary-400 dark:to-success-500 transition-all duration-500 ease-out"
                        >
                        </div>
                    </div>
                    <div class="mb-2 flex items-center justify-between text-xs">
                        <div class="dark:text-gray-400">
                            {{ formatDateTime(subscriberAccount.subscription.created_at, false) }}
                        </div>
                        <div class="dark:text-gray-400">
                            {{ formatDateTime(subscriberAccount.subscription.expired_date, false) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="text-gray-600 dark:text-gray-400 text-justify my-4">
            {{ subscriberAccount.subscription.auto_renewal === 1 ? $t('public.stop_renewal_confirmation', {meta_login: subscriberAccount.subscription.meta_login}) : $t('public.request_renewal_confirmation') }}
        </div>

        <div class="pt-5 grid grid-cols-2 gap-4 w-full md:w-1/3 md:float-right">
            <Button variant="transparent" type="button" class="justify-center" @click.prevent="closeModal">
                {{$t('public.cancel')}}
            </Button>
            <Button class="justify-center" @click="submit" :disabled="form.processing">{{$t('public.confirm')}}</Button>
        </div>
    </Modal>
</template>
