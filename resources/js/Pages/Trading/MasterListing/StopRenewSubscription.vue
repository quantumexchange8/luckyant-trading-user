<script setup>
import Button from "primevue/button";
import {ref} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import {useForm} from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import Checkbox from "primevue/checkbox";
import {useLangObserver} from "@/Composables/localeObserver.js";
import dayjs from "dayjs";
import TermsAndCondition from "@/Components/TermsAndCondition.vue";

const props = defineProps({
    strategyType: String,
    subscriber: Object,
    subscription: Object,
})

const {locale} = useLangObserver();
const {formatAmount} = transactionFormat();
const terms = ref();
const emit = defineEmits(['update:visible']);

const form = useForm({
    subscription_id: props.subscription.id,
    action: '',
    terms: false
})

const getTerms = async () => {
    try {
        const response = await axios.get(`/${props.strategyType}_strategy/getTerms`);
        terms.value = response.data;

    } catch (error) {
        console.error('Error fetching trading accounts data:', error);
    }
};

getTerms();

const submitForm = () => {
    if (props.subscription.auto_renewal === 1) {
        form.action = 'stop_renewal'
    } else {
        form.action = 'request_auto_renewal'
    }
    form.post(route('trading.renewalSubscription'), {
        onSuccess: () => {
            closeModal();
        },
    })
}

const closeDialog = () => {
    emit("update:visible", false);
}

const getJoinedDays = (data) => {
    const approvalDate = dayjs(data.approval_date);
    const endDate =
        data.status === 'Terminated'
            ? dayjs(data.termination_date)
            : dayjs(); // Use today's date if not terminated

    return endDate.diff(approvalDate, 'day'); // Calculate the difference in days
};

const calculateManagementFee = (data) => {
    const joinedDays = getJoinedDays(data);

    let managementFee = 0;

    // Sort the management_fee array to ensure rules are applied in order of penalty_days
    const sortedFees = [...data.master.master_management_fee].sort((a, b) => a.penalty_days - b.penalty_days);

    for (const feeRule of sortedFees) {
        if (joinedDays <= feeRule.penalty_days) {
            managementFee = feeRule.penalty_percentage;
            break;
        }
    }

    return data.subscription.meta_balance * (managementFee / 100);
};
</script>

<template>
    <div class="flex flex-col items-center self-stretch gap-5 md:gap-8">
        <div
            class="py-5 px-6 flex flex-col items-center gap-4 bg-gray-50 dark:bg-gray-950 divide-y dark:divide-gray-700 self-stretch">
            <div class="w-full flex items-center gap-4">
                <div class="flex flex-col items-start self-stretch">
                    <div class="self-stretch truncate w-[190px] md:w-64 text-gray-950 dark:text-white font-bold">
                        <div v-if="locale === 'cn'">
                            {{
                                subscriber.master.trading_user.company ? subscriber.master.trading_user.company : subscriber.master.trading_user.name
                            }}
                        </div>
                        <div v-else>
                            {{ subscriber.master.trading_user.name }}
                        </div>
                    </div>
                    <div class="self-stretch truncate w-24 text-gray-500 text-sm">
                        {{ subscriber.master.meta_login }}
                    </div>
                </div>
            </div>
            <div class="flex flex-col items-start gap-3 w-full pt-4 self-stretch">
                <div class="flex flex-col gap-1 items-center self-stretch">
                    <div class="flex py-1 gap-3 items-start self-stretch">
                        <span class="w-full text-gray-500 font-medium text-xs">{{ $t('public.account_no') }}</span>
                        <span class="w-full text-gray-950 dark:text-white font-medium text-sm">{{
                                subscriber.meta_login
                            }}</span>
                    </div>
                    <div class="flex py-1 gap-3 items-start self-stretch">
                        <span class="w-full text-gray-500 font-medium text-xs">{{
                                $t('public.subscription_number')
                            }}</span>
                        <span class="w-full text-gray-950 dark:text-white font-medium text-sm">{{
                                subscription.subscription_number
                            }}</span>
                    </div>
                    <div class="flex py-1 gap-3 items-start self-stretch">
                        <span class="w-full text-gray-500 font-medium text-xs">{{
                                $t('public.roi_date')
                            }} ({{ subscriber.roi_period }} {{ $t('public.days') }} )</span>
                        <span class="w-full text-gray-950 dark:text-white font-medium text-sm">{{
                                dayjs(subscription.next_pay_date).format('YYYY/MM/DD')
                            }}</span>
                    </div>
                    <div class="flex py-1 gap-3 items-start self-stretch">
                        <span class="w-full text-gray-500 font-medium text-xs">{{ $t('public.max_drawdown') }}</span>
                        <span class="w-full text-gray-950 dark:text-white font-medium text-sm">{{
                                formatAmount(subscriber.master.max_drawdown, 0)
                            }}%</span>
                    </div>
                    <div class="flex py-1 gap-3 items-start self-stretch">
                        <span class="w-full text-gray-500 font-medium text-xs">{{
                                $t('public.investment_amount')
                            }}</span>
                        <span class="w-full text-gray-950 dark:text-white font-medium text-sm">$ {{
                                formatAmount(subscription.meta_balance)
                            }}</span>
                    </div>
                    <div class="flex py-1 gap-3 items-start self-stretch">
                        <span class="w-full text-gray-500 font-medium text-xs">{{ $t('public.management_fee') }}</span>
                        <span class="w-full text-error-500 font-semibold text-sm">$ {{
                                formatAmount(calculateManagementFee(subscriber))
                            }}</span>
                    </div>
                    <div class="flex py-1 gap-3 items-start self-stretch">
                        <span class="w-full text-gray-500 font-medium text-xs">{{ $t('public.return_amount') }}</span>
                        <span class="w-full text-success-500 font-semibold text-sm">$ {{
                                formatAmount(subscription.meta_balance - calculateManagementFee(subscriber))
                            }}</span>
                    </div>
                    <div class="flex py-1 gap-3 items-start self-stretch">
                        <span class="w-full text-gray-500 font-medium text-xs">{{
                                $t('public.termination_date')
                            }}</span>
                        <span class="w-full text-gray-950 dark:text-white font-medium text-sm lowercase">{{
                                dayjs(subscription.expired_date).add(1, 'day').format('YYYY/MM/DD')
                            }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- t&c -->
        <div class="flex flex-col gap-1 items-start self-stretch">
            <div class="flex items-start gap-2 self-stretch w-full">
                <Checkbox
                    v-model="form.terms"
                    inputId="terms"
                    binary
                    :invalid="!!form.errors.terms"
                />
                <label for="terms" class="text-gray-600 dark:text-gray-400 text-xs">
                    {{ $t('public.agreement') }}
                    <TermsAndCondition
                        :termsLabel="$t('public.terms_and_conditions')"
                        :terms="terms"
                        :managementFee="subscriber.master.master_management_fee"
                    />
                </label>
            </div>
            <InputError :message="form.errors.terms"/>
        </div>

        <div class="flex w-full justify-end gap-3">
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
    </div>
</template>
