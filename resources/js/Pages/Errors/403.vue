<script setup>
import {Head, Link} from '@inertiajs/vue3'
import ApplicationLogo from '@/Components/ApplicationLogo.vue'
import PageFooter from '@/Components/PageFooter.vue'
import Button from '@/Components/Button.vue'
</script>

<template>
    <Head :title="$t('public.error_403')" />
    <div
        class="flex flex-col items-center justify-center min-h-screen gap-4 py-6 mt-10 bg-white dark:bg-dark-eval-0"
    >
        <div class="fixed left-0 top-0 flex-shrink-0 inline-flex items-center gap-3 p-5">
            <Link :href="route('dashboard')" class="inline-flex items-center gap-2">
                <span class="sr-only">K-UI</span>
                <div class="flex items-center">
                    <ApplicationLogo aria-hidden="true" class="w-10 h-10" />
                    <div
                        class="text-lg font-bold text-gray-800 dark:text-white"
                    >
                        LuckyAnt Trading
                    </div>
                </div>
            </Link>
        </div>

        <div class="flex flex-1 items-center sm:items-start w-full sm:max-w-3xl">
            <div
                class="w-full px-6 py-4 overflow-hidden sm:rounded-lg"
            >
            <div class="flex flex-col items-center justify-center gap-8">
                <img src="/assets/403.svg" class="" alt="">
                <div class="flex flex-col mt-3 gap-5">
                    <div class="flex flex-col dark:text-gray-400 text-center gap-2">
                        <h2 class="dark:text-white font-semibold text-3xl">{{$t('public.not_right_roles')}}</h2>
                        {{$t('public.access_denied')}}
                    </div>
                    <div class="flex flex-row gap-5 justify-center ">
                        <Button
                            :href="route('dashboard')"
                            class="sm:w-1/3 text-nowrap justify-center"
                        >
                        {{$t('public.back_to_dashboard')}}
                        </Button>
                        <Button
                            :href="route('logout')"
                            method="post"
                            variant="gray"
                            class="sm:w-1/3 text-nowrap justify-center"
                        >
                        {{$t('public.logout')}}
                        </Button>
                    </div>
                </div>
            </div>
            </div>
        </div>

        <PageFooter />

    </div>
</template>
