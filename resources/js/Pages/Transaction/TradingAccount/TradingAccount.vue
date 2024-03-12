<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import TransactionHistory from "@/Pages/Transaction/TradingAccount/Partials/TransactionHistory.vue";

</script>

<template>

    <AuthenticatedLayout :title="$t('public.sidebar.transaction')">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    {{ $t('public.trading_account') }}
                </h2>
            </div>
        </template>

        <div class="p-5 mb-28 bg-white overflow-hidden md:overflow-visible rounded-xl shadow-md dark:bg-gray-900">
            <TransactionHistory/>
        </div>
    </AuthenticatedLayout>
</template>
