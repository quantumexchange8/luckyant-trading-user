<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import FollowingMaster from "@/Pages/Trading/MasterListing/FollowingMaster.vue";
import MasterAccounts from "@/Pages/Trading/MasterListing/MasterAccounts.vue";
import Button from "@/Components/Button.vue";
import {Tab, TabGroup, TabList, TabPanel, TabPanels} from "@headlessui/vue";
import MasterAccount from "@/Pages/AccountInfo/MasterAccount/MasterAccount.vue";
import TradingAccount from "@/Pages/AccountInfo/TradingAccount/TradingAccount.vue";

const props = defineProps({
    terms: Object
})
</script>

<template>
    <AuthenticatedLayout :title="$t('public.master_trader_listing')">
        <template #header>
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    {{ $t('public.master_trader_listing') }}
                </h2>
            </div>
        </template>

        <MasterAccounts
            :terms="terms"
        />

    </AuthenticatedLayout>
</template>
