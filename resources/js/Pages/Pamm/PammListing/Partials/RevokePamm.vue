<script setup>
import {BanIcon} from "@heroicons/vue/outline";
import Button from "@/Components/Button.vue";
import Modal from "@/Components/Modal.vue";
import {ref} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import {useForm, usePage} from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import Checkbox from "@/Components/Checkbox.vue";
import Label from "@/Components/Label.vue";

const props = defineProps({
    pamm: Object,
    terms: Object,
})

const terminationModal = ref(false);
const { formatDateTime, formatAmount } = transactionFormat();
const currentLocale = ref(usePage().props.locale);

const openTerminationModal = () => {
    terminationModal.value = true;
    // managementFeeAmount.value = props.subscriberAccount.totalPenalty;
}

const closeModal = () => {
    terminationModal.value = false;
}

const termsModal = ref(false);

const openTermsModal = () => {
    termsModal.value = true
}

const closeTermsModal = () => {
    termsModal.value = false
}

const form = useForm({
    meta_login: props.pamm.meta_login,
    meta_master_login: props.pamm.meta_master_login,
    terms: '',
})

const submit = () => {
    form.post(route('pamm.revokePamm'), {
        onSuccess: () => {
            closeModal();
        },
    })
}
</script>

<template>
    <Button
        type="button"
        variant="danger"
        class="flex gap-2 justify-center w-full"
        v-slot="{ iconSizeClasses }"
        @click="openTerminationModal"
    >
        <BanIcon
            aria-hidden="true"
            :class="iconSizeClasses"
        />
        {{ $t('public.revoke') }}
    </Button>

    <Modal
        :show="terminationModal"
        :title="$t('public.revoke_pamm')"
        @close="closeModal"
    >
        <div class="p-5 bg-gray-100 dark:bg-gray-800 rounded-lg">
            <div class="flex flex-col items-start gap-3 self-stretch">
                <div class="text-lg font-semibold dark:text-white">
                    <div v-if="currentLocale === 'en'">
                        {{ pamm.master.trading_user.name }}
                    </div>
                    <div v-if="currentLocale === 'cn'">
                        {{ pamm.master.trading_user.company ? pamm.master.trading_user.company : pamm.master.trading_user.name }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.account_number')}}
                    </div>
                    <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                        {{ pamm.meta_login }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.subscription_number')}}
                    </div>
                    <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                        {{ pamm.subscription_number }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.roi_period')}} ({{ pamm.subscription_period }} {{ $t('public.days') }})
                    </div>
                    <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                        {{ formatDateTime(pamm.settlement_date, false) }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('public.max_drawdown') }}
                    </div>
                    <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                        {{ pamm.master.max_drawdown ? pamm.master.max_drawdown : '-' }}
                    </div>
                </div>
                <div class="flex items-start justify-between gap-2 self-stretch">
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.amount')}}
                    </div>
                    <div class="text-sm sm:text-base text-primary-500 dark:text-primary-400 font-bold">
                        $ {{ formatAmount(pamm.subscription_amount) }}
                    </div>
                </div>
                <div class="flex items-start justify-between gap-2 self-stretch">
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.management_fee')}}
                    </div>
                    <div class="text-sm sm:text-base text-error-500 font-bold">
                        $ {{ formatAmount(pamm.master.totalManagementPenalty) }}
                    </div>
                </div>
                <div class="flex items-start justify-between gap-2 self-stretch">
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.return_amount')}}
                    </div>
                    <div class="text-sm sm:text-base text-success-500 font-bold">
                        $ {{ formatAmount(pamm.subscription_amount - pamm.master.totalManagementPenalty) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="text-gray-600 dark:text-gray-400 text-sm sm:text-base text-justify my-4">
            {{$t('public.confirm_terminate_warning_1')}} {{ pamm.meta_login }} {{$t('public.confirm_terminate_warning_2')}}
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
            <Button
                variant="transparent"
                type="button"
                class="justify-center"
                @click.prevent="closeModal"
                :disabled="form.processing"
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
    </Modal>

    <Modal :show="termsModal" :title="$t('public.terms_and_conditions')" @close="closeTermsModal">
        <div v-html="terms.contents" class="prose dark:text-white"></div>
        <div class="pt-4">
            <div class="text-gray-600 dark:text-gray-400">
                {{ $t('public.management_fee') }}
            </div>
            <div
                v-for="management_fee in pamm.master.master_management_fee"
                class="text-sm font-semibold dark:text-white"
            >
                {{ management_fee?.penalty_days }} {{ $t('public.days') }} - {{ formatAmount(management_fee?.penalty_percentage, 0) }} %
            </div>
        </div>
    </Modal>
</template>
