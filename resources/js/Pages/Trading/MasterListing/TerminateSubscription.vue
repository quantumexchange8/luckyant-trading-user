<script setup>
import Button from "@/Components/Button.vue";
import {BanIcon} from "@heroicons/vue/outline";
import Tooltip from "@/Components/Tooltip.vue";
import Modal from "@/Components/Modal.vue";
import {ref} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import {useForm, usePage} from "@inertiajs/vue3";

const props = defineProps({
    subscriberAccount: Object
})

const terminationModal = ref(false);
const { formatDateTime, formatAmount } = transactionFormat();
const currentLocale = ref(usePage().props.locale);

const openTerminationModal = () => {
    terminationModal.value = true;
}

const form = useForm({
    subscription_id: props.subscriberAccount.subscription.id,
})

const closeModal = () => {
    terminationModal.value = false;
}

const submit = () => {
    form.post(route('trading.terminateSubscription'), {
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
</script>

<template>
    <Button
        type="button"
        variant="danger"
        size="sm"
        class="flex gap-2 justify-center w-full"
        v-slot="{ iconSizeClasses }"
        @click="openTerminationModal"
    >
        <BanIcon
            aria-hidden="true"
            :class="iconSizeClasses"
        />
        {{ $t('public.terminate') }}
    </Button>

    <Modal :show="terminationModal" :title="$t('public.terminate_subscription')" @close="closeModal">
        <div class="p-5 bg-gray-100 dark:bg-gray-600 rounded-lg">
            <div class="flex flex-col items-start gap-3 self-stretch">
                <div class="text-lg font-semibold">
                    <div v-if="currentLocale === 'en'">
                        {{ subscriberAccount.master.trading_user.name }}
                    </div>
                    <div v-if="currentLocale === 'cn'">
                        {{ subscriberAccount.master.trading_user.company ? subscriberAccount.master.trading_user.company : subscriberAccount.master.trading_user.name }}
                    </div>
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
                        {{$t('public.management_fee')}}
                    </div>
                    <div class="text-base flex flex-col text-gray-800 dark:text-white font-semibold">
                        <div
                            v-for="management_fee in subscriberAccount.master.master_management_fee"
                            class="font-semibold"
                        >
                            {{ management_fee.penalty_days }} {{ $t('public.day') }} - {{ formatAmount(management_fee.penalty_percentage, 0) }}%
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.roi_period')}} ({{ subscriberAccount.subscription.subscription_period }} {{ $t('public.days') }})
                    </div>
                    <div class="text-base text-gray-800 dark:text-white font-semibold">
                        {{ formatDateTime(subscriberAccount.subscription.next_pay_date, false) }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('public.max_drawdown') }}
                    </div>
                    <div class="text-base text-gray-800 dark:text-white font-semibold">
                        {{ formatAmount(subscriberAccount.master.max_drawdown ? subscriberAccount.master.max_drawdown : 0) }} %
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
            {{$t('public.confirm_terminate_warning_1')}} {{ subscriberAccount.subscription.meta_login }} {{$t('public.confirm_terminate_warning_2')}}
        </div>

        <div class="pt-5 grid grid-cols-2 gap-4 w-full md:w-1/3 md:float-right">
            <Button variant="transparent" type="button" class="justify-center" @click.prevent="closeModal">
                {{$t('public.cancel')}}
            </Button>
            <Button class="justify-center" @click="submit" :disabled="form.processing">{{$t('public.confirm')}}</Button>
        </div>
    </Modal>
</template>
