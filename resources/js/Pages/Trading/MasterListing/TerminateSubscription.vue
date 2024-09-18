<script setup>
import Button from "@/Components/Button.vue";
import {BanIcon} from "@heroicons/vue/outline";
import Tooltip from "@/Components/Tooltip.vue";
import Modal from "@/Components/Modal.vue";
import {ref} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import {useForm, usePage} from "@inertiajs/vue3";
import Label from "@/Components/Label.vue";
import InputError from "@/Components/InputError.vue";
import Checkbox from "@/Components/Checkbox.vue";

const props = defineProps({
    subscription: Object,
    subscriberAccount: Object,
    terms: Object,
})

const { formatDateTime, formatAmount } = transactionFormat();
const currentLocale = ref(usePage().props.locale);
const terminationModal = ref(false);
const managementFeeAmount = ref(0);

const openTerminationModal = () => {
    terminationModal.value = true;
    managementFeeAmount.value = props.subscriberAccount.totalPenalty;
}

const closeModal = () => {
    terminationModal.value = false;
}

const form = useForm({
    subscription_id: props.subscription.id,
    terms: '',
})

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

const termsModal = ref(false);

const openTermsModal = () => {
    termsModal.value = true
}

const closeTermsModal = () => {
    termsModal.value = false
}
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
        <div class="p-5 bg-gray-100 dark:bg-gray-800 rounded-lg">
            <div class="flex flex-col items-start gap-3 self-stretch">
                <div class="text-lg font-semibold dark:text-white">
                    <div v-if="currentLocale === 'en'">
                        {{ subscriberAccount.master.trading_user.name }}
                    </div>
                    <div v-if="currentLocale === 'cn'">
                        {{ subscriberAccount.master.trading_user.company ? subscriberAccount.master.trading_user.company : subscriberAccount.master.trading_user.name }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.account_number')}}
                    </div>
                    <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                        {{ subscriberAccount.meta_login }}
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
                        {{$t('public.roi_period')}} ({{ subscription.subscription_period }} {{ $t('public.days') }})
                    </div>
                    <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                        {{ formatDateTime(subscription.next_pay_date, false) }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('public.max_drawdown') }}
                    </div>
                    <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                        {{ subscriberAccount.master.max_drawdown ? subscriberAccount.master.max_drawdown : '-' }}
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
                <div class="flex items-start justify-between gap-2 self-stretch">
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.management_fee')}}
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
                <div class="flex flex-col gap-2 self-stretch">
                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.progress')}}
                    </div>
                    <div class="mb-1 flex h-2.5 w-full overflow-hidden rounded-full bg-gray-300 dark:bg-gray-400 text-xs">
                        <div
                            :style="{ width: `${calculateWidthPercentage(subscription.approval_date, subscription.next_pay_date, subscription.subscription_period).widthResult}%` }"
                            class="rounded-full bg-gradient-to-r from-primary-300 to-success-400 dark:from-primary-400 dark:to-success-500 transition-all duration-500 ease-out"
                        >
                        </div>
                    </div>
                    <div class="mb-2 flex items-center justify-between text-xs">
                        <div class="dark:text-gray-400">
                            {{ formatDateTime(subscription.approval_date, false) }}
                        </div>
                        <div class="dark:text-gray-400">
                            {{ formatDateTime(subscription.next_pay_date, false) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="text-gray-600 dark:text-gray-400 text-sm sm:text-base text-justify my-4">
            {{$t('public.confirm_terminate_warning_1')}} {{ subscription.meta_login }} {{$t('public.confirm_terminate_warning_2')}}
        </div>

        <div class="flex items-center">
            <div class="flex items-center h-5">
                <Checkbox id="terms" v-model="form.terms"/>
            </div>
            <div class="ml-3">
                <label for="terms" class="flex gap-1 text-gray-500 dark:text-gray-400 text-xs">
                    {{ $t('public.agreement') }}
                    <div
                        class="text-xs underline hover:cursor-pointer text-primary-500 hover:text-gray-700 dark:text-primary-600 dark:hover:text-primary-400"
                        @click="openTermsModal"
                    >
                        {{ $t('public.terms_and_conditions') }}
                    </div>
                </label>
            </div>
        </div>
        <InputError :message="form.errors.terms" />

        <div class="pt-5 grid grid-cols-2 gap-4 w-full md:w-1/3 md:float-right">
            <Button variant="transparent" type="button" class="justify-center" @click.prevent="closeModal">
                {{$t('public.cancel')}}
            </Button>
            <Button class="justify-center" @click="submit" :disabled="form.processing">{{$t('public.confirm')}}</Button>
        </div>
    </Modal>

    <Modal :show="termsModal" :title="$t('public.terms_and_conditions')" @close="closeTermsModal">
        <div v-html="terms.contents" class="prose dark:text-white"></div>
        <div class="pt-4">
            <div class="text-gray-600 dark:text-gray-400">
                {{ $t('public.management_fee') }}
            </div>
            <div
                v-for="management_fee in subscriberAccount.master.master_management_fee"
                class="text-sm font-semibold dark:text-white"
            >
                {{ management_fee.penalty_days }} {{ $t('public.days') }} - {{ formatAmount(management_fee.penalty_percentage, 0) }} %
            </div>
        </div>
    </Modal>
</template>
