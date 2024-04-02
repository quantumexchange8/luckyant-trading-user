<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import {ChevronRightIcon, XIcon} from "@heroicons/vue/outline";
import MasterConfiguration from "@/Pages/AccountInfo/MasterAccount/MasterConfiguration.vue";
import {transactionFormat} from "@/Composables/index.js";
import Label from "@/Components/Label.vue";
import CountryLists from "../../../../../public/data/countries.json";
import Button from "@/Components/Button.vue";
import Input from "@/Components/Input.vue";
import InputError from "@/Components/InputError.vue";
import {RadioGroup, RadioGroupLabel, RadioGroupOption} from "@headlessui/vue";
import BaseListbox from "@/Components/BaseListbox.vue";
import Badge from "@/Components/Badge.vue";
import AvatarInput from "@/Pages/Profile/Partials/AvatarInput.vue";
import TradeRebateHistoryTable from "@/Pages/Report/TradeRebate/TradeRebateHistoryTable.vue";
import {ref} from "vue";
import {usePage} from "@inertiajs/vue3";

const props = defineProps({
    totalRebate: Number,
    totalVolume: Number,
})

const { formatAmount } = transactionFormat();
const currentLocale = ref(usePage().props.locale);

const statusVariant = (status) => {
    if (status === 'Pending') return 'processing';
    if (status === 'Active') return 'success';
    if (status === 'Rejected' || status === 'Terminated') return 'danger';
}
</script>

<template>
    <AuthenticatedLayout :title="$t('public.master_configuration')">
        <template #header>
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    {{ $t('public.trade_rebate_report') }}
                </h2>
            </div>
        </template>

        <div class="grid grid-cols-2 w-full gap-4">
            <div class="flex flex-col gap-5 p-6 md:col-span-1 col-span-3 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                <div>
                    {{ $t('public.total_rebate_amount') }}
                </div>
                <div class="text-2xl font-bold">
                    $ {{ formatAmount(props.totalRebate) }}
                </div>
            </div>

            <div class="flex flex-col gap-5 p-6 md:col-span-1 col-span-3 overflow-hidden bg-white rounded-lg shadow-md dark:bg-gray-900">
                <div>
                    {{ $t('public.total_trade_volume') }}
                </div>
                <div class="text-2xl font-bold">
                    {{ formatAmount(props.totalVolume) }}
                </div>
            </div>
        </div>

        <div class="p-5 my-5 mb-28 bg-white overflow-hidden md:overflow-visible rounded-lg shadow-lg dark:bg-gray-900 border border-gray-300 dark:border-gray-600">
            <TradeRebateHistoryTable
            />
        </div>
    </AuthenticatedLayout>
</template>
