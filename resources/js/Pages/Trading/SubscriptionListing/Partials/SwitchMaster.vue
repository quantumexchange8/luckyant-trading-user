<script setup>
import {
    IconChevronsRight
} from "@tabler/icons-vue"
import {useLangObserver} from "@/Composables/localeObserver.js";
import {transactionFormat} from "@/Composables/index.js";
import dayjs from "dayjs";
import Select from "primevue/select";
import {ref} from "vue";
import Button from "primevue/button";
import {useForm} from "@inertiajs/vue3";

const props = defineProps({
    strategyType: String,
    subscriber: Object,
})

const {locale} = useLangObserver();
const {formatAmount} = transactionFormat();

const getJoinedDays = (account) => {
    const approvalDate = dayjs(account.approval_date);
    const endDate =
        account.status === 'Terminated'
            ? dayjs(account.termination_date)
            : dayjs(); // Use today's date if not terminated

    return endDate.diff(approvalDate, 'day'); // Calculate the difference in days
};

const masters = ref([]);
const masterLoading = ref(false);
const selectedMaster = ref(null);
const emit = defineEmits(['update:visible']);

const getSelectedNewMaster = async () => {
    masterLoading.value = true;
    try {
        const response = await axios.get(`/${props.strategyType}_strategy/getNewMaster?current_master_id=${props.subscriber.master_id}`);
        masters.value = response.data;
        selectedMaster.value = masters.value[0];
    } catch (error) {
        console.error(error);
    } finally {
        masterLoading.value = false;
    }
}

getSelectedNewMaster();

const form = useForm({
    new_master_id: '',
    subscriber_id: props.subscriber.id
})

const submitForm = () => {
    form.new_master_id = selectedMaster.value.id

    form.post(route('trading.switchMaster'), {
        onSuccess: () => {
            closeDialog();
        },
    })
}

const closeDialog = () => {
    emit("update:visible", false);
}
</script>

<template>
    <div class="grid grid-cols-11">
        <div class="col-span-11 sm:col-span-5 bg-gray-100 dark:bg-gray-950 p-5">
            <div class="flex flex-col items-start gap-3 self-stretch">
                <div class="flex items-center justify-center w-full">
                    <div class="flex flex-col items-start gap-3 self-stretch w-full">
                        <div class="flex flex-col items-start">
                            <div class="self-stretch truncate text-gray-950 dark:text-white font-bold">
                                <div v-if="locale === 'cn'">
                                    {{ subscriber.master.trading_user.company ? subscriber.master.trading_user.company : subscriber.master.trading_user.name }}
                                </div>
                                <div v-else>
                                    {{ subscriber.master.trading_user.name }}
                                </div>
                            </div>
                            <div class="self-stretch truncate text-gray-500 text-sm">
                                {{ subscriber.master.meta_login }}
                            </div>
                        </div>
                        <div class="flex items-start justify-between gap-2 self-stretch">
                            <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                {{$t('public.subscription_number')}}
                            </div>
                            <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                {{ subscriber.subscription.subscription_number }}
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-2 self-stretch">
                            <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                {{$t('public.join_date')}}
                            </div>
                            <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                {{ dayjs(subscriber.approval_date).format('YYYY/MM/DD') }}
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-2 self-stretch">
                            <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                {{ $t('public.join_day') }}
                            </div>
                            <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                {{ getJoinedDays(subscriber) }} {{ $t('public.days') }}
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-2 self-stretch">
                            <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                {{ $t('public.roi_period') }}
                            </div>
                            <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                {{ subscriber.roi_period }} {{ $t('public.days') }}
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-2 self-stretch">
                            <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                {{ $t('public.sharing_profit') }}
                            </div>
                            <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                {{ subscriber.master.sharing_profit % 1 === 0 ? formatAmount(subscriber.master.sharing_profit, 0) : formatAmount(subscriber.master.sharing_profit) }}%
                            </div>
                        </div>
                        <div class="flex items-start justify-between gap-2 self-stretch">
                            <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                {{$t('public.amount')}}
                            </div>
                            <div class="text-xs sm:text-sm text-primary-500 dark:text-primary-400 font-bold">
                                $ {{ formatAmount(subscriber.subscription.meta_balance) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-span-11 sm:col-span-1 p-2">
            <div class="flex items-center justify-center w-full h-full">
                <IconChevronsRight size="24" />
            </div>
        </div>
        <div class="col-span-11 sm:col-span-5 bg-gray-100 dark:bg-gray-950 p-5">
            <div class="flex flex-col items-start gap-3 self-stretch">
                <div class="flex items-center justify-center w-full">
                    <div class="flex flex-col items-start gap-3 self-stretch w-full">
                        <Select
                            v-model="selectedMaster"
                            :options="masters"
                            optionLabel="name"
                            class="w-full"
                            :loading="masterLoading"
                            :placeholder="$t('public.placeholder')"
                            :disabled="!masters.length"
                        >
                            <template #value="slotProps">
                                <div v-if="slotProps.value" class="flex items-center">
                                    <div v-if="locale === 'cn'">
                                        {{ slotProps.value.trading_user.company ? slotProps.value.trading_user.company : slotProps.value.trading_user.name }} ({{ slotProps.value.meta_login }})
                                    </div>
                                    <div v-else>
                                        {{ slotProps.value.trading_user.name }} ({{ slotProps.value.meta_login }})
                                    </div>
                                </div>
                                <span v-else>{{ slotProps.placeholder }}</span>
                            </template>
                            <template #option="slotProps">
                                <div v-if="locale === 'cn'">
                                    {{ slotProps.option.trading_user.company ? slotProps.option.trading_user.company : slotProps.option.trading_user.name }} ({{ slotProps.option.meta_login }})
                                </div>
                                <div v-else>
                                    {{ slotProps.option.trading_user.name }} ({{ slotProps.option.meta_login }})
                                </div>
                            </template>
                        </Select>
                        <div class="flex items-center justify-between gap-2 self-stretch">
                            <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                {{$t('public.estimated_roi')}}
                            </div>
                            <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                {{ selectedMaster ? selectedMaster.estimated_monthly_returns : $t('public.loading') }}
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-2 self-stretch">
                            <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                {{$t('public.estimated_lot_size')}}
                            </div>
                            <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                {{ selectedMaster ? selectedMaster.estimated_lot_size + ` ${$t('public.lot')}` : $t('public.loading') }}
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-2 self-stretch">
                            <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                {{ $t('public.max_drawdown') }}
                            </div>
                            <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                {{ selectedMaster ? selectedMaster.max_drawdown : $t('public.loading') }}
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-2 self-stretch">
                            <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                {{ $t('public.roi_period') }}
                            </div>
                            <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                {{ selectedMaster ? selectedMaster.roi_period + ` ${$t('public.days')}` : $t('public.loading') }}
                            </div>
                        </div>
                        <div class="flex items-center justify-between gap-2 self-stretch">
                            <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                {{ $t('public.sharing_profit') }}
                            </div>
                            <div class="text-xs sm:text-sm text-gray-800 dark:text-white font-semibold">
                                {{ selectedMaster ? (selectedMaster.sharing_profit % 1 === 0 ? formatAmount(selectedMaster.sharing_profit, 0) : formatAmount(selectedMaster.sharing_profit)) + '%' : $t('public.loading') }}
                            </div>
                        </div>
                        <div class="flex items-start justify-between gap-2 self-stretch">
                            <div class="font-semibold text-xs sm:text-sm text-gray-500 dark:text-gray-400">
                                {{$t('public.min_join_equity')}}
                            </div>
                            <div class="text-xs sm:text-sm text-primary-500 dark:text-primary-400 font-bold">
                                $ {{ selectedMaster ? formatAmount(selectedMaster.min_join_equity, 0) : $t('public.loading') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="pt-5 text-gray-600 dark:text-gray-400 text-sm">
        {{$t('public.switch_master_description')}}
    </div>

    <div class="flex w-full justify-end gap-3 mt-5">
        <Button
            type="button"
            severity="secondary"
            text
            class="justify-center w-full md:w-auto px-6"
            @click="closeDialog"
            :disabled="form.processing"
        >
            {{ $t('public.cancel') }}
        </Button>
        <Button
            type="submit"
            class="justify-center w-full md:w-auto px-6"
            @click.prevent="submitForm"
            :disabled="form.processing"
        >
            {{ $t('public.confirm') }}
        </Button>
    </div>
</template>
