<script setup>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import Button from '@/Components/Button.vue'
import { GithubIcon } from '@/Components/Icons/brands'
import {usePage} from "@inertiajs/vue3";

const user = usePage().props.auth.user
</script>

<template>
    <AuthenticatedLayout title="Dashboard">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    Dashboard
                </h2>

                <Button
                    external
                    variant="black"
                    target="_blank"
                    class="items-center gap-2 max-w-xs"
                    v-slot="{ iconSizeClasses }"
                    href="https://github.com/kamona-wd/kui-laravel-breeze"
                >
                    <GithubIcon aria-hidden="true" :class="iconSizeClasses" />

                    <span>Star on Github</span>
                </Button>
            </div>
        </template>

        <div class="space-y-5">
            <div class="grid grid-cols-3 w-full gap-4">
                <div class="p-6 md:col-span-2 col-span-3 overflow-hidden bg-white rounded-lg shadow-md dark:bg-dark-eval-1">
                    <div class="flex items-center self-stretch gap-4">
                        <div class="flex flex-col gap-4 w-full">
                            <div class="flex flex-col">
                                <div class="text-lg text-gray-600 dark:text-gray-400">
                                    Welcome back!
                                </div>
                                <div class="text-xl font-semibold">
                                    ID: 101010
                                </div>
                            </div>
                            <div class="flex gap-3 items-center">
                                <div class="bg-gray-400 rounded-full w-14 h-14 grow-0 shrink-0 flex justify-center items-center">
                                    pic
                                </div>
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
                        <div
                            class="cursor-pointer group overflow-hidden p-5 duration-1000 hover:duration-1000 relative w-full h-52 bg-gray-100 dark:bg-dark-eval-3 rounded-xl"
                        >
                            <div
                                class="group-hover:-top-3 bg-transparent -top-8 -left-2 absolute shadow-yellow-800 shadow-inner rounded-xl transition-all ease-in-out group-hover:duration-1000 duration-1000 w-24 h-24"
                            ></div>
                            <div
                                class="group-hover:top-40 bg-transparent top-32 left-32 absolute shadow-red-800 shadow-inner rounded-xl transition-all ease-in-out group-hover:duration-1000 duration-1000 w-24 h-24"
                            ></div>
                            <div
                                class="group-hover:-left-12 bg-transparent top-24 left-72 absolute shadow-sky-800 shadow-inner rounded-xl transition-all ease-in-out group-hover:duration-1000 duration-1000 w-24 h-24"
                            ></div>
                            <div
                                class="group-hover:-top-44 bg-transparent top-12 left-28 absolute shadow-red-800 shadow-inner rounded-xl transition-all ease-in-out group-hover:duration-1000 duration-1000 w-12 h-12"
                            ></div>
                            <div
                                class="group-hover:left-44 bg-transparent top-12 left-48 absolute shadow-green-800 shadow-inner rounded-xl transition-all ease-in-out group-hover:duration-1000 duration-1000 w-44 h-44"
                            ></div>
                            <div
                                class="group-hover:-left-2 bg-transparent -top-24 -left-5 absolute shadow-sky-800 shadow-inner rounded-xl transition-all ease-in-out group-hover:duration-1000 duration-1000 w-64 h-64"
                            ></div>
                            <div
                                class="group-hover:top-44 bg-transparent top-24 left-12 absolute shadow-sky-500 shadow-inner rounded-xl transition-all ease-in-out group-hover:duration-1000 duration-1000 w-4 h-4"
                            ></div>
                            <div
                                class="w-full h-full shadow-xl shadow-neutral-900 p-3 bg-gray-200 dark:bg-white/30 rounded-xl flex-col gap-2 flex justify-center"
                            >
                                <span class="text-lg text-gray-600 dark:text-gray-300">Cash Wallet</span>
                                <p class="text-gray-900 dark:text-white text-4xl font-bold">
                                    $ {{ user.cash_wallet }}
                                </p>
                            </div>
                        </div>
                    </div>

                </div>
                <div class="p-6 md:col-span-1 col-span-3 overflow-hidden bg-white rounded-lg shadow-md dark:bg-dark-eval-1">
                    trading account balance (pie chart)
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
