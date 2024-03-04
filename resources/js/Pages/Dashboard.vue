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

const user = usePage().props.auth.user
const { formatDateTime, formatAmount } = transactionFormat();
const props = defineProps({
    announcement: Object,
    firstTimeLogin: Number,
    walletSel: Array,
    paymentAccountSel: Array,
    paymentDetails: Object,
    withdrawalFee: Object,
    registerLink: String,
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
        message: trans('public.Copy Successful!'),
    });
}

const announcementModal = ref(false);
const firstTimeLogin = ref(props.firstTimeLogin);

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
        message: trans('public.Copy Successful!'),
    });
}
</script>

<template>
    <AuthenticatedLayout title="Dashboard">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    Dashboard
                </h2>
            </div>
        </template>

        <div class="space-y-5">
            <div class="flex sm:flex-row flex-col items-stretch gap-5">
                <div class="flex flex-col gap-5 w-full">
                    <div class="p-6 w-full flex flex-col justify-center overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                        <div class="flex flex-col sm:flex-row items-center self-stretch gap-4">
                            <div class="flex flex-col gap-4 w-full">
                                <div class="flex flex-col">
                                    <div class="text-lg text-gray-600 dark:text-white">
                                        Welcome back!
                                    </div>
                                </div>
                                <div class="flex gap-3 items-center">
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
                            </div>
                        </div>
                    </div>
                    <div class="p-6 w-full flex flex-col justify-center overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                        <div class="flex flex-col">
                            <div class="text-lg text-gray-600 dark:text-white">
                                Referral Program
                            </div>
                        </div>
                        <div class="flex sm:flex-row flex-col gap-5 w-full">
                            <div class="flex flex-col justify-center gap-5 w-full sm:w-3/5">
                                <div class="text-gray-400 dark:text-gray-500">
                                    Share your referral link through QR link
                                </div>
                                <div class="flex w-full rounded-md shadow-sm">
                                    <button
                                        type="button"
                                        class="py-2 px-4 inline-flex flex-shrink-0 justify-center items-center gap-2 rounded-l-lg border border-transparent font-semibold bg-blue-500 text-white hover:bg-blue-600 focus:z-10 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all text-sm uppercase"
                                        @click="copyReferralLink"
                                    >
                                        Copy
                                    </button>
                                    <input
                                        ref="referralLinkInput"
                                        type="text"
                                        id="userReferralLink"
                                        readonly
                                        :class="[
                                            'py-2.5 w-full rounded-r-lg text-base font-normal shadow-xs border placeholder:text-gray-400 dark:placeholder:text-gray-500 text-gray-500 dark:text-gray-400',
                                            'bg-white dark:bg-dark-eval-2',
                                            'disabled:bg-gray-50 disabled:cursor-not-allowed dark:disabled:bg-gray-900',
                                            'border-gray-300 dark:border-dark-eval-2 focus:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500',
                                        ]"
                                        :value="registerLink">
                                </div>
                            </div>
                            <div class="flex flex-col gap-4 items-center justify-center w-full sm:w-2/5">
                                <qrcode-vue :class="['border-4 border-white']" :value="registerLink" :size="150"></qrcode-vue>

                                <div class="flex items-center gap-3">
                                    <span class="text-lg">Referral Code:</span> <span class="text-xl" id="userReferralCode">{{ user.referral_code }}</span>
                                    <div>
                                        <DuplicateIcon
                                            class="w-5 hover:cursor-pointer"
                                            @click.prevent="copyReferralCode"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex flex-col gap-5 w-full sm:w-[600px]">
                    <DashboardWallets
                        :walletSel="walletSel"
                        :paymentAccountSel="paymentAccountSel"
                        :paymentDetails="paymentDetails"
                        :withdrawalFee="withdrawalFee"
                    />
                </div>
            </div>

            <!-- TickerTape -->
            <iframe scrolling="no" allowtransparency="true" frameborder="0" src="https://s.tradingview.com/embed-widget/ticker-tape/?locale=en#%7B%22symbols%22%3A%5B%7B%22proName%22%3A%22FOREXCOM%3ASPXUSD%22%2C%22title%22%3A%22S%26P%20500%22%7D%2C%7B%22proName%22%3A%22FOREXCOM%3ANSXUSD%22%2C%22title%22%3A%22US%20100%22%7D%2C%7B%22proName%22%3A%22FX_IDC%3AEURUSD%22%2C%22title%22%3A%22EUR%2FUSD%22%7D%2C%7B%22proName%22%3A%22BITSTAMP%3ABTCUSD%22%2C%22title%22%3A%22Bitcoin%22%7D%2C%7B%22proName%22%3A%22BITSTAMP%3AETHUSD%22%2C%22title%22%3A%22Ethereum%22%7D%5D%2C%22showSymbolLogo%22%3Atrue%2C%22colorTheme%22%3A%22dark%22%2C%22isTransparent%22%3Atrue%2C%22displayMode%22%3A%22adaptive%22%2C%22width%22%3A%22100%25%22%2C%22height%22%3A44%2C%22utm_source%22%3A%22currenttech.pro%22%2C%22utm_medium%22%3A%22widget%22%2C%22utm_campaign%22%3A%22ticker-tape%22%2C%22page-uri%22%3A%22currenttech.pro%2Fdemocrm%2Fpublic%2Fmember%2Fdashboard%22%7D" style="box-sizing: border-box; display: block; height: 50px; width: 100%;"></iframe>

        </div>

        <Modal :show="announcementModal" title="Details" @close="closeModal">
            <div class="text-xs dark:text-gray-400">{{ formatDateTime(announcement.created_at) }}</div>
            <div v-if="announcement.image !== ''" class="my-5">
                <img class="rounded-lg w-full" :src="announcement.image" alt="announcement image" />
            </div>
            <div class="my-5 dark:text-white">{{ announcement.subject }}</div>
            <div class="dark:text-gray-300 text-sm prose leading-3" v-html="announcement.details"></div>
        </Modal>

    </AuthenticatedLayout>
</template>
