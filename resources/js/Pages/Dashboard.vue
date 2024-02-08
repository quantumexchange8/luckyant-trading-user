<script setup>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import {DuplicateIcon, CashIcon, RefreshIcon} from "@heroicons/vue/outline"
import {usePage} from "@inertiajs/vue3";
import toast from "@/Composables/toast.js";
import {trans} from "laravel-vue-i18n";
import Button from "@/Components/Button.vue";
import BalanceChart from "@/Pages/Dashboard/BalanceChart.vue";
import Deposit from "@/Pages/Dashboard/Deposit.vue";
import Withdrawal from "@/Pages/Dashboard/Withdrawal.vue";
import InternalTransfer from "@/Pages/Dashboard/InternalTransfer.vue";
import Modal from "@/Components/Modal.vue";
import {onMounted, ref} from "vue";
import {transactionFormat} from "@/Composables/index.js";

const user = usePage().props.auth.user
const { formatDateTime, formatAmount } = transactionFormat();
const props = defineProps({
    announcement: Object,
    firstTimeLogin: Number,
    cashWallet: Object
})

const copyReferralCode = () => {
    const referralCode = document.querySelector('#userReferralCode').textContent;
    const url = window.location.origin + '/register/' + referralCode;

    const tempInput = document.createElement('input');
    tempInput.value = url;
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
            <div class="grid grid-cols-3 w-full gap-4">
                <div class="p-6 md:col-span-2 col-span-3 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                    <div class="flex flex-col sm:flex-row items-center self-stretch gap-4">
                        <div class="flex flex-col gap-4 w-full">
                            <div class="flex flex-col">
                                <div class="text-lg text-gray-600 dark:text-gray-400">
                                    Welcome back!
                                </div>
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
                        <div class="w-full flex justify-end">
                            <div
                                class="w-96 h-52 duration-500 group overflow-hidden relative rounded-xl bg-gray-800 shadow-lg text-neutral-50 p-4 flex flex-col justify-evenly"
                            >
                                <div
                                    class="absolute blur duration-500 group-hover:blur-none w-72 h-72 rounded-full group-hover:translate-x-12 group-hover:translate-y-12 bg-sky-800 right-1 -bottom-24"
                                ></div>
                                <div
                                    class="absolute blur duration-500 group-hover:blur-none w-12 h-12 rounded-full group-hover:translate-x-12 group-hover:translate-y-2 bg-blue-800 right-12 bottom-12"
                                ></div>
                                <div
                                    class="absolute blur duration-500 group-hover:blur-none w-36 h-36 rounded-full group-hover:translate-x-12 group-hover:-translate-y-12 bg-blue-900 right-1 -top-12"
                                ></div>
                                <div
                                    class="absolute blur duration-500 group-hover:blur-none w-24 h-24 bg-sky-900 rounded-full group-hover:-translate-x-12"
                                ></div>
                                <div class="z-10 flex flex-col justify-evenly w-full h-full">
                                    <div class="text-lg font-bold">{{ cashWallet.name }} ({{cashWallet.wallet_address }})</div>
                                    <div class="text-2xl">
                                        $ {{ formatAmount(cashWallet.balance) }}
                                    </div>
                                    <div class="flex justify-between w-full gap-2">
                                        <Deposit />
                                        <Withdrawal />
                                    </div>
                                    <div class="flex items-center justify-center w-full">
                                        <InternalTransfer />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="p-6 md:col-span-1 col-span-3 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                    Total Balance
                    <BalanceChart />
                </div>
            </div>

            <!-- TickerTape -->
<!--            <iframe scrolling="no" allowtransparency="true" frameborder="0" src="https://s.tradingview.com/embed-widget/ticker-tape/?locale=en#%7B%22symbols%22%3A%5B%7B%22proName%22%3A%22FOREXCOM%3ASPXUSD%22%2C%22title%22%3A%22S%26P%20500%22%7D%2C%7B%22proName%22%3A%22FOREXCOM%3ANSXUSD%22%2C%22title%22%3A%22US%20100%22%7D%2C%7B%22proName%22%3A%22FX_IDC%3AEURUSD%22%2C%22title%22%3A%22EUR%2FUSD%22%7D%2C%7B%22proName%22%3A%22BITSTAMP%3ABTCUSD%22%2C%22title%22%3A%22Bitcoin%22%7D%2C%7B%22proName%22%3A%22BITSTAMP%3AETHUSD%22%2C%22title%22%3A%22Ethereum%22%7D%5D%2C%22showSymbolLogo%22%3Atrue%2C%22colorTheme%22%3A%22dark%22%2C%22isTransparent%22%3Atrue%2C%22displayMode%22%3A%22adaptive%22%2C%22width%22%3A%22100%25%22%2C%22height%22%3A44%2C%22utm_source%22%3A%22currenttech.pro%22%2C%22utm_medium%22%3A%22widget%22%2C%22utm_campaign%22%3A%22ticker-tape%22%2C%22page-uri%22%3A%22currenttech.pro%2Fdemocrm%2Fpublic%2Fmember%2Fdashboard%22%7D" style="box-sizing: border-box; display: block; height: 50px; width: 100%;"></iframe>-->

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
