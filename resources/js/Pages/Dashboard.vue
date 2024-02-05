<script setup>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import {DuplicateIcon} from "@heroicons/vue/outline"
import {usePage} from "@inertiajs/vue3";
import toast from "@/Composables/toast.js";
import {trans} from "laravel-vue-i18n";
import Button from "@/Components/Button.vue";
import BalanceChart from "@/Pages/Dashboard/BalanceChart.vue";

const user = usePage().props.auth.user

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
                                class="w-96 h-52 duration-500 group overflow-hidden relative rounded-xl bg-gray-800 text-neutral-50 p-4 flex flex-col justify-evenly"
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
                                    <div class="text-lg font-bold">Cash Wallet</div>
                                    <div class="text-2xl">
                                        $ {{ user.cash_wallet }}
                                    </div>
                                    <Button
                                        type="button"
                                        variant="success"
                                        class="w-full flex justify-center"
                                    >
                                        Deposit
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="p-6 md:col-span-1 col-span-3 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                    trading account balance (pie chart)
<!--                    <BalanceChart />-->
                </div>
            </div>

            <!-- TickerTape -->
            <iframe scrolling="no" allowtransparency="true" frameborder="0" src="https://s.tradingview.com/embed-widget/ticker-tape/?locale=en#%7B%22symbols%22%3A%5B%7B%22proName%22%3A%22FOREXCOM%3ASPXUSD%22%2C%22title%22%3A%22S%26P%20500%22%7D%2C%7B%22proName%22%3A%22FOREXCOM%3ANSXUSD%22%2C%22title%22%3A%22US%20100%22%7D%2C%7B%22proName%22%3A%22FX_IDC%3AEURUSD%22%2C%22title%22%3A%22EUR%2FUSD%22%7D%2C%7B%22proName%22%3A%22BITSTAMP%3ABTCUSD%22%2C%22title%22%3A%22Bitcoin%22%7D%2C%7B%22proName%22%3A%22BITSTAMP%3AETHUSD%22%2C%22title%22%3A%22Ethereum%22%7D%5D%2C%22showSymbolLogo%22%3Atrue%2C%22colorTheme%22%3A%22dark%22%2C%22isTransparent%22%3Atrue%2C%22displayMode%22%3A%22adaptive%22%2C%22width%22%3A%22100%25%22%2C%22height%22%3A44%2C%22utm_source%22%3A%22currenttech.pro%22%2C%22utm_medium%22%3A%22widget%22%2C%22utm_campaign%22%3A%22ticker-tape%22%2C%22page-uri%22%3A%22currenttech.pro%2Fdemocrm%2Fpublic%2Fmember%2Fdashboard%22%7D" style="box-sizing: border-box; display: block; height: 50px; width: 100%;"></iframe>

            <div>
                Avatar
            </div>
        </div>

    </AuthenticatedLayout>
</template>
