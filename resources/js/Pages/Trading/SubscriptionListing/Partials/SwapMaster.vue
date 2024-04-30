<script setup>
import {useForm, usePage} from "@inertiajs/vue3";
import Badge from "@/Components/Badge.vue";
import {transactionFormat} from "@/Composables/index.js";
import {ref} from "vue";
import Label from "@/Components/Label.vue";
import BaseListbox from "@/Components/BaseListbox.vue";
import InputError from "@/Components/InputError.vue";

const props = defineProps({
    subscription: Object,
    swapMasterSel: Array,
})

const { formatDateTime, formatAmount } = transactionFormat();
const currentLocale = ref(usePage().props.locale);
const emit = defineEmits(['update:subscriptionModal']);

const closeModal = () => {
    emit('update:subscriptionModal', false);
}

const form = useForm({
    subscription_id: '',
    new_master_id: ''
})
</script>

<template>
    <div class="flex flex-col gap-8">
        <div class="p-5 bg-gray-100 dark:bg-gray-600 rounded-lg">
            <div class="flex flex-col items-start gap-3 self-stretch">
                <div class="text-lg font-semibold dark:text-white">
                    <div v-if="currentLocale === 'en'">
                        {{ subscription.master.trading_user.name }}
                    </div>
                    <div v-if="currentLocale === 'cn'">
                        {{ subscription.master.trading_user.company ? subscription.master.trading_user.company : subscription.master.trading_user.name }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.account_number')}}
                    </div>
                    <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                        {{ subscription.meta_login }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.subscription_number')}}
                    </div>
                    <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                        {{ subscription.subscription_number }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                        {{$t('public.roi_period')}} ({{ subscription.subscription_period }} {{ $t('public.days') }})
                    </div>
                    <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                        {{ formatDateTime(subscription.settlement_date, false) }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('public.max_drawdown') }}
                    </div>
                    <div class="text-sm sm:text-base text-gray-800 dark:text-white font-semibold">
                        {{ subscription.master.max_drawdown ? subscription.master.max_drawdown : '-' }}
                    </div>
                </div>
            </div>
        </div>
        <div class="w-full">
            <div class="space-y-2">
                <Label
                    for="leverage"
                    :value="$t('public.master')"
                />
                <BaseListbox
                    :options="swapMasterSel"
                    v-model="form.new_master_id"
                />
                <InputError :message="form.errors.new_master_id" />
            </div>
        </div>
    </div>
</template>
