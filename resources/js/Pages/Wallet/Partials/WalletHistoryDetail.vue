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
import WalletHistory from "../WalletHistory.vue";

const props =  defineProps({
    walletHistory: Object,
})

const emit = defineEmits(['update:walletHistoryModal']);
const { formatDateTime, formatType } = transactionFormat();
const currentLocale = ref(usePage().props.locale);
const walletHistoryDetail = ref(null);

// onMounted(async () => {
//     try {
//         const response = await axios.get('getPenaltyDetail?subscription_batch_id=' + props.walletHistory.id);
//         walletHistoryDetail.value = response.data;
//     } catch (error) {
//         console.error(error);
//     }
// });

const closeModal = () => {
    emit('update:walletHistoryModal', false);
}

const statusVariant = (autoRenewal) => {
    if (autoRenewal === 1) {
        return 'success';
    } else {
        return 'danger'
    }
}
</script>

<template>
    <div class="flex flex-col gap-5">
        <div class="p-5 bg-gray-100 dark:bg-gray-800 rounded-lg">
            <div class="flex flex-col items-start gap-3 self-stretch">
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.date')}}
                    </div>
                    <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                        {{ formatDateTime(walletHistory.created_at, false) }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.transaction_type')}}
                    </div>
                    <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                        {{ $t('public.' + formatType(walletHistory.transaction_type).toLowerCase().replace(/\s+/g, '_')) }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.transaction_no')}}
                    </div>
                    <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                        {{ walletHistory.transaction_number ?? '-' }}
                    </div>
                </div>
                <div v-if="walletHistory.transaction_type === 'Withdrawal'" class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.withdrawal_amount')}}
                    </div>
                    <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                        $ {{ walletHistory.amount }}
                    </div>
                </div>
                <div v-if="walletHistory.transaction_type === 'Withdrawal'" class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.withdrawal_charges')}}
                    </div>
                    <div class="text-sm sm:text-base text-error-500 font-semibold">
                        - $ {{ walletHistory.transaction_charges }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.amount')}}
                    </div>
                    <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                        $ {{ walletHistory.transaction_amount }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch" v-if="walletHistory.transaction_type === 'WalletAdjustment'">
                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.wallet')}}
                    </div>
                    <div class="text-sm sm:text-base font-semibold">
                        <span class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">{{ $t('public.' + walletHistory.from_wallet.type) }}</span>
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch" v-if="(walletHistory.from_wallet || walletHistory.from_meta_login) && walletHistory.transaction_type !== 'WalletAdjustment' && walletHistory.transaction_type !== 'PerformanceIncentive'">
                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.transfer_from')}}
                    </div>
                    <div class="text-sm sm:text-base font-semibold">
                        <span class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold" v-if="walletHistory.transaction_type !== 'Transfer'">{{ walletHistory.from_wallet ? $t('public.' + walletHistory.from_wallet.type) : walletHistory.from_meta_login.meta_login }}</span>
                        <span class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold" v-if="walletHistory.transaction_type === 'Transfer'">{{ walletHistory.from_wallet ? walletHistory.from_wallet.wallet_address : walletHistory.from_meta_login }}</span>
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch" v-if="(walletHistory.to_wallet || walletHistory.to_meta_login || walletHistory.payment_account) && walletHistory.transaction_type !== 'WalletAdjustment'">
                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.transfer_to')}}
                    </div>
                    <div class="text-sm sm:text-base font-semibold">
                        <div
                            v-if="walletHistory.transaction_type === 'Withdrawal'"
                            class="flex flex-col text-sm sm:text-base text-gray-800 dark:text-white font-semibold"
                        >
                            <div>
                                {{ walletHistory.payment_account ? (walletHistory.payment_account.payment_account_name) : $t('public.' + walletHistory.to_wallet.type) }} -
                            </div>
                            <div class="break-words w-40 md:w-full md:break-normal">
                                {{ walletHistory.payment_account ? (walletHistory.payment_account.account_no) : $t('public.' + walletHistory.to_wallet.type) }}
                            </div>
                        </div>
                        <span class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold" v-if="walletHistory.transaction_type !== 'Transfer' && walletHistory.transaction_type !== 'Withdrawal'">{{ walletHistory.to_wallet ? $t('public.' + walletHistory.to_wallet.type) : walletHistory.to_meta_login.meta_login }}</span>
                        <span class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold" v-if="walletHistory.transaction_type === 'Transfer'">{{ walletHistory.to_wallet ? walletHistory.to_wallet.wallet_address : walletHistory.to_meta_login }}</span>
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.status')}}
                    </div>
                    <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                        <StatusBadge :value="walletHistory.status" width="w-20"/>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
