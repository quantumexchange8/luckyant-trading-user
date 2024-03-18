<script setup>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import {DuplicateIcon, CashIcon, RefreshIcon} from "@heroicons/vue/outline"
import {usePage} from "@inertiajs/vue3";
import toast from "@/Composables/toast.js";
import {trans} from "laravel-vue-i18n";
import Modal from "@/Components/Modal.vue";
import {onMounted, ref} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import DashboardWallets from "@/Pages/Dashboard/DashboardWallets.vue";
import QrcodeVue from 'qrcode.vue';
import Tooltip from "@/Components/Tooltip.vue";
import Button from "@/Components/Button.vue";
import Input from "@/Components/Input.vue";
import Badge from "@/Components/Badge.vue";
import {LinkIcon} from "@/Components/Icons/outline.jsx"

const user = usePage().props.auth.user
const { formatDateTime, formatAmount } = transactionFormat();
const props = defineProps({
    announcement: Object,
    firstTimeLogin: Number,
    walletSel: Array,
    eWalletSel: Array,
    paymentAccountSel: Array,
    paymentDetails: Object,
    withdrawalFee: Object,
    registerLink: String,
    countries: Array,
})

const copyReferralCode = () => {
    const referralCode = document.querySelector('#userReferralCode').textContent;

    const tempInput = document.createElement('input');
    tempInput.value = referralCode;
    document.body.appendChild(tempInput);
    tempInput.select();
    document.execCommand('copy');
    document.body.removeChild(tempInput);

    toast.add({
        message: trans('public.copy_success'),
    });
}

const announcementModal = ref(false);
const firstTimeLogin = ref(props.firstTimeLogin);
const totalDeposit = ref('0.00');
const totalWithdrawal = ref('0.00');
const totalTradeLot = ref('0.00');
const totalRebateEarn = ref('0.00');

const closeModal = () => {
    announcementModal.value = false;
    firstTimeLogin.value = 0; // Set the value of firstTimeLogin to 0 when closing the modal
    setValueInSession(0); // Update the session value to 0
}

const setValueInSession = async (value) => {
    await axios.post('/update_session', {firstTimeLogin: value})
        .then(response => {
            // Session value has been updated successfully
            console.log('Session value updated:', value);
        })
        .catch(error => {
            // Handle the error, if any
            console.error('Error updating session value:', error);
        });
};

onMounted(() => {
    // Check if the modal has been shown already
    if (firstTimeLogin.value === 1) {
        announcementModal.value = true;
    }
});

const tooltipContent = ref('copy');

const copyReferralLink = () => {
    const referralCodeCopy = document.querySelector('#userReferralLink').value;
    const tempInput = document.createElement('input');
    tempInput.value = referralCodeCopy;
    document.body.appendChild(tempInput);
    tempInput.select();

    try {
        var successful = document.execCommand('copy');
        if (successful) {
            tooltipContent.value = 'copied';
            setTimeout(() => {
                tooltipContent.value = 'copy'; // Reset tooltip content to 'Copy' after 2 seconds
            }, 1000);
        } else {
            tooltipContent.value = 'try_again_later';
        }

    } catch (err) {
        alert('copy_error');
    }
    document.body.removeChild(tempInput);
    window.getSelection().removeAllRanges()

    toast.add({
        message: trans('public.copy_success'),
    });
}

const getTotalTransactions = async () => {
    try {
        const response = await axios.get('/getTotalTransactions');
        totalDeposit.value = response.data.totalDeposit;
        totalWithdrawal.value = response.data.totalWithdrawal;
    } catch (error) {
        console.error('Error refreshing transactions data:', error);
    }
};

getTotalTransactions();
</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar.dashboard')">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    {{ $t('public.sidebar.dashboard') }}
                </h2>
            </div>
        </template>

        <div class="flex flex-col sm:flex-row gap-5">
            <div class="flex flex-col items-stretch gap-5 w-full">
                <div class="flex flex-col sm:flex-row gap-5 w-full">
                    <div class="p-6 w-full flex flex-col overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                        <div class="flex flex-col sm:flex-row items-center self-stretch gap-4">
                            <div class="flex flex-col gap-4 w-full">
                                <div class="flex flex-col gap-4 sm:flex-row sm:justify-between">
                                    <div class="flex gap-3 items-center justify-center">
                                        <img
                                            class="object-cover w-14 h-14 rounded-full"
                                            :src="user.profile_photo ? user.profile_photo : 'https://img.freepik.com/free-icon/user_318-159711.jpg'"
                                            alt="userPic"
                                        />
                                        <div>
                                            <div class="text-lg font-semibold text-gray-600 dark:text-gray-400">
                                                {{ user.name }}
                                            </div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                                {{ user.email }}
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <Badge
                                            variant="primary"
                                            width="auto"
                                        >
                                            <span class="text-sm">{{ user.rank.name }}</span>
                                        </Badge>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-2 mt-5">
                                    <div class="text-lg text-gray-600 dark:text-white border-b border-gray-300 pb-2">
                                        {{ $t('public.overview') }}
                                    </div>
                                    <div class="grid grid-cols-2 gap-4 w-full mt-4">
                                        <div class="space-y-2 w-full">
                                            <div class="text-sm font-semibold flex justify-center">
                                                {{ $t('public.total_deposit') }}
                                            </div>
                                            <div class="py-2 flex justify-center rounded-md border border-success-500">
                                                <span class="text-success-500 font-semibold">$ {{ formatAmount(totalDeposit) }}</span>
                                            </div>
                                        </div>
                                        <div class="space-y-2 w-full">
                                            <div class="text-sm font-semibold flex justify-center">
                                                {{ $t('public.total_withdrawal') }}
                                            </div>
                                            <div class="py-2 flex justify-center rounded-md border border-error-500">
                                                <span class="text-error-500 font-semibold">$ {{ formatAmount(totalWithdrawal) }}</span>
                                            </div>
                                        </div>
                                        <div class="space-y-2 w-full">
                                            <div class="text-sm font-semibold flex justify-center">
                                                {{ $t('public.total_rebate_earn') }}
                                            </div>
                                            <div class="py-2 flex justify-center rounded-md border border-success-500">
                                                <span class="text-success-500 font-semibold">$ {{ formatAmount(totalRebateEarn) }}</span>
                                            </div>
                                        </div>
                                        <div class="space-y-2 w-full">
                                            <div class="text-sm font-semibold flex justify-center">
                                                {{ $t('public.total_trade_lots') }}
                                            </div>
                                            <div class="py-2 flex justify-center rounded-md border border-gray-500">
                                                <span class="text-gray-500 font-semibold">{{ formatAmount(totalTradeLot) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="p-6 w-full flex flex-col gap-4 justify-center overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                        <div class="flex flex-col">
                            <div class="text-lg text-gray-600 dark:text-white">
                                {{ $t('public.sidebar.referral_program') }}
                            </div>
                        </div>
                        <div class="flex flex-col gap-5 w-full">
                            <div class="flex flex-col justify-center gap-5 w-full">
                                <div class="flex flex-col gap-2 items-center justify-center w-full">
                                    <qrcode-vue :class="['border-4 border-white']" :value="registerLink" :size="150"></qrcode-vue>

                                    <div class="flex items-center gap-3">
                                        <span class="text-lg">{{ $t('public.referral_code') }}:</span> <span class="text-xl" id="userReferralCode">{{ user.referral_code }}</span>
                                        <div>
                                            <DuplicateIcon
                                                class="w-5 hover:cursor-pointer"
                                                @click.prevent="copyReferralCode"
                                            />
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-1">
                                    <div class="text-gray-400 text-sm dark:text-gray-500">
                                        {{ $t('public.referral_qr') }}
                                    </div>
                                    <div class="flex w-full rounded-md">
                                        <Tooltip :content="$t('public.' + tooltipContent)" placement="top">
                                            <button
                                                type="button"
                                                class="px-4 py-3 inline-flex flex-shrink-0 justify-center items-center gap-2 rounded-l-lg border border-transparent font-semibold bg-primary-500 dark:bg-primary-700 text-white hover:bg-primary-60 dark:hover:bg-primary-500 focus:z-10 focus:outline-none focus:ring-2 focus:ring-primary-500 transition-all text-sm uppercase"
                                                @click="copyReferralLink"
                                            >
                                                <LinkIcon class="text-white w-5 h-5" />
                                            </button>
                                        </Tooltip>
                                        <input
                                            ref="referralLinkInput"
                                            type="text"
                                            id="userReferralLink"
                                            readonly
                                            :class="[
                                            'py-2.5 w-full rounded-r-lg text-sm font-normal shadow-xs border placeholder:text-gray-400 dark:placeholder:text-gray-500 text-gray-500 dark:text-gray-400',
                                            'bg-white dark:bg-dark-eval-2',
                                            'disabled:bg-gray-50 disabled:cursor-not-allowed dark:disabled:bg-gray-900',
                                            'border-gray-300 dark:border-dark-eval-2 focus:ring-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:focus:border-primary-500',
                                        ]"
                                            :value="registerLink">
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
                <!-- TickerTape -->
                <iframe scrolling="no" allowtransparency="true" frameborder="0" src="https://s.tradingview.com/embed-widget/ticker-tape/?locale=en#%7B%22symbols%22%3A%5B%7B%22proName%22%3A%22FOREXCOM%3ASPXUSD%22%2C%22title%22%3A%22S%26P%20500%22%7D%2C%7B%22proName%22%3A%22FOREXCOM%3ANSXUSD%22%2C%22title%22%3A%22US%20100%22%7D%2C%7B%22proName%22%3A%22FX_IDC%3AEURUSD%22%2C%22title%22%3A%22EUR%2FUSD%22%7D%2C%7B%22proName%22%3A%22BITSTAMP%3ABTCUSD%22%2C%22title%22%3A%22Bitcoin%22%7D%2C%7B%22proName%22%3A%22BITSTAMP%3AETHUSD%22%2C%22title%22%3A%22Ethereum%22%7D%5D%2C%22showSymbolLogo%22%3Atrue%2C%22colorTheme%22%3A%22dark%22%2C%22isTransparent%22%3Atrue%2C%22displayMode%22%3A%22adaptive%22%2C%22width%22%3A%22100%25%22%2C%22height%22%3A44%2C%22utm_source%22%3A%22currenttech.pro%22%2C%22utm_medium%22%3A%22widget%22%2C%22utm_campaign%22%3A%22ticker-tape%22%2C%22page-uri%22%3A%22currenttech.pro%2Fdemocrm%2Fpublic%2Fmember%2Fdashboard%22%7D" style="box-sizing: border-box; display: block; height: 50px; width: 100%;"></iframe>
            </div>

            <div class="flex flex-col gap-5 w-full sm:w-[480px] sm:pl-5 sm:border-l-2 sm:border-gray-300 h-full">
                <DashboardWallets
                    :walletSel="walletSel"
                    :eWalletSel="eWalletSel"
                    :paymentAccountSel="paymentAccountSel"
                    :paymentDetails="paymentDetails"
                    :withdrawalFee="withdrawalFee"
                    :countries="countries"
                />
            </div>

        </div>

        <Modal :show="announcementModal" :title="$t('public.details')" @close="closeModal">
            <div class="text-xs dark:text-gray-400">{{ formatDateTime(announcement.created_at) }}</div>
            <div v-if="announcement.image !== ''" class="my-5">
                <img class="rounded-lg w-full" :src="announcement.image" alt="announcement image" />
            </div>
            <div class="my-5 dark:text-white">{{ announcement.subject }}</div>
            <div class="dark:text-gray-300 text-sm prose leading-3" v-html="announcement.details"></div>
        </Modal>

    </AuthenticatedLayout>
</template>
