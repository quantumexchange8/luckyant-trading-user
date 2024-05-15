<script setup>
import Button from "@/Components/Button.vue";
import {RefreshCw05Icon, ChevronRightDoubleIcon} from "@/Components/Icons/outline.jsx";
import {ref, watch} from "vue";
import Modal from "@/Components/Modal.vue";
import {transactionFormat} from "@/Composables/index.js";
import {useForm, usePage} from "@inertiajs/vue3";
import BaseListbox from "@/Components/BaseListbox.vue";

const props = defineProps({
    subscriberAccount: Object,
    subscription: Object,
})

const switchModal = ref(false);
const { formatDateTime, formatAmount } = transactionFormat();
const currentLocale = ref(usePage().props.locale);
const selectedNewMaster = ref(props.subscriberAccount.newMasterSel[0].value)
const newMaster = ref(null);

const openSwitchModal = () => {
    switchModal.value = true;
}

const closeModal = () => {
    switchModal.value = false
}

const getSelectedNewMaster = async (masterId = selectedNewMaster.value) => {
    try {
        const response = await axios.get('/trading/getSelectedNewMaster?master_id=' + masterId);
        newMaster.value = response.data;
    } catch (error) {
        console.error(error);
    }
}

getSelectedNewMaster();

watch(selectedNewMaster, (newMaster) => {
    getSelectedNewMaster(newMaster);
})

const form = useForm({
    new_master_id: '',
    subscriber_id: props.subscriberAccount.id
})

const submit = () => {
    form.new_master_id = selectedNewMaster.value

    form.post(route('trading.switchMaster'), {
        onSuccess: () => {
            closeModal();
        },
    })
}
</script>

<template>
    <Button
        v-if="subscriberAccount.status === 'Subscribing'"
        type="button"
        size="sm"
        v-slot="{ iconSizeClasses }"
        class="flex gap-2 justify-center items-center w-full"
        @click="openSwitchModal"
    >
        <RefreshCw05Icon
            aria-hidden="true"
            :class="iconSizeClasses"
        />
        {{ $t('public.switch_master') }}
    </Button>

    <Modal :show="switchModal" :title="$t('public.switch_master')" @close="closeModal" max-width="4xl">
        <div class="grid grid-cols-11">
            <div class="col-span-11 sm:col-span-5 bg-gray-100 dark:bg-gray-600 rounded-lg p-5">
                <div class="flex flex-col items-start gap-3 self-stretch">
                    <div class="flex items-center justify-center w-full">
                        <div class="flex flex-col items-start gap-3 self-stretch w-full">
                            <div class="text-lg font-semibold dark:text-white">
                                <div v-if="currentLocale === 'en'">
                                    {{ subscriberAccount.master.trading_user.name }}
                                </div>
                                <div v-if="currentLocale === 'cn'">
                                    {{ subscriberAccount.master.trading_user.company ? subscriberAccount.master.trading_user.company : subscriberAccount.master.trading_user.name }}
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-2 self-stretch">
                                <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                    {{$t('public.account_number')}}
                                </div>
                                <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                    {{ subscriberAccount.meta_login }}
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-2 self-stretch">
                                <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                    {{$t('public.subscription_number')}}
                                </div>
                                <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                    {{ subscription.subscription_number }}
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-2 self-stretch">
                                <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                    {{$t('public.join_date')}}
                                </div>
                                <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                    {{ formatDateTime(subscriberAccount.approval_date, false) }}
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-2 self-stretch">
                                <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                    {{ $t('public.join_day') }}
                                </div>
                                <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                    {{ subscriberAccount.join_days }} {{ $t('public.days') }}
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-2 self-stretch">
                                <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                    {{ $t('public.roi_period') }}
                                </div>
                                <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                    {{ subscriberAccount.master.roi_period }} {{ $t('public.days') }}
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-2 self-stretch">
                                <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                    {{ $t('public.sharing_profit') }}
                                </div>
                                <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                    {{ subscriberAccount.master.sharing_profit % 1 === 0 ? formatAmount(subscriberAccount.master.sharing_profit, 0) : formatAmount(subscriberAccount.master.sharing_profit) }}%
                                </div>
                            </div>
                            <div class="flex items-start justify-between gap-2 self-stretch">
                                <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                    {{$t('public.amount')}}
                                </div>
                                <div class="text-xs sm:text-sm text-primary-500 dark:text-primary-400 font-bold">
                                    $ {{ formatAmount(subscription.meta_balance) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-span-11 sm:col-span-1 rounded-lg p-2">
                <div class="flex items-center justify-center w-full h-full">
                    <ChevronRightDoubleIcon class="text-gray-400 rotate-90 sm:rotate-0" />
                </div>
            </div>
            <div class="col-span-11 sm:col-span-5 bg-gray-100 rounded-lg p-5">
                <div class="flex flex-col items-start gap-3 self-stretch">
                    <div class="flex items-center justify-center w-full">
                        <div class="flex flex-col items-start gap-3 self-stretch w-full">
                            <BaseListbox
                                v-model="selectedNewMaster"
                                :options="subscriberAccount.newMasterSel"
                                class="w-full"
                            />
                            <div class="flex items-center justify-between gap-2 self-stretch">
                                <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                    {{$t('public.account_number')}}
                                </div>
                                <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                    {{ subscriberAccount.meta_login }}
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-2 self-stretch">
                                <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                    {{$t('public.estimated_monthly_returns')}}
                                </div>
                                <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                    {{ newMaster ? newMaster.estimated_monthly_returns : $t('public.loading') }}
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-2 self-stretch">
                                <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                    {{$t('public.estimated_lot_size')}}
                                </div>
                                <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                    {{ newMaster ? newMaster.estimated_lot_size : $t('public.loading') }}
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-2 self-stretch">
                                <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                    {{ $t('public.max_drawdown') }}
                                </div>
                                <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                    {{ newMaster ? newMaster.max_drawdown : $t('public.loading') }}
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-2 self-stretch">
                                <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                    {{ $t('public.roi_period') }}
                                </div>
                                <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                    {{ newMaster ? newMaster.roi_period + $t('public.days') : $t('public.loading') }}
                                </div>
                            </div>
                            <div class="flex items-center justify-between gap-2 self-stretch">
                                <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                    {{ $t('public.sharing_profit') }}
                                </div>
                                <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                    {{ newMaster ? (newMaster.sharing_profit % 1 === 0 ? formatAmount(newMaster.sharing_profit, 0) : formatAmount(newMaster.sharing_profit)) + '%' : $t('public.loading') }}
                                </div>
                            </div>
                            <div class="flex items-start justify-between gap-2 self-stretch">
                                <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                    {{$t('public.min_join_equity')}}
                                </div>
                                <div class="text-xs sm:text-sm text-primary-500 dark:text-primary-400 font-bold">
                                    $ {{ newMaster ? formatAmount(newMaster.min_join_equity, 0) : $t('public.loading') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="pt-5 text-gray-600 dark:text-gray-400 text-sm sm:text-base">
            {{$t('public.switch_master_description')}}
        </div>

        <div class="pt-5 grid grid-cols-2 gap-4 w-full md:w-1/3 md:float-right">
            <Button variant="transparent" type="button" class="justify-center" @click.prevent="closeModal">
                {{$t('public.cancel')}}
            </Button>
            <Button class="justify-center" @click="submit" :disabled="form.processing">{{$t('public.confirm')}}</Button>
        </div>
    </Modal>
</template>
