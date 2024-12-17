<script setup>
import Button from "primevue/button";
import {
    IconSettings
} from "@tabler/icons-vue";
import {ref} from "vue";
import Dialog from "primevue/dialog";
import Tag from "primevue/tag";
import {useLangObserver} from "@/Composables/localeObserver.js";
import {transactionFormat} from "@/Composables/index.js";
import dayjs from "dayjs";
import InputError from "@/Components/InputError.vue";
import Checkbox from "primevue/checkbox";
import TermsAndCondition from "@/Components/TermsAndCondition.vue";
import {useForm} from "@inertiajs/vue3";

const props = defineProps({
    strategyType: String,
    subscription: Object
})

const visible = ref(false);
const {locale} = useLangObserver();
const {formatAmount} = transactionFormat();
const terms = ref();

const openDialog = () => {
    visible.value = true;
    getTerms();
}

const getJoinedDays = (data) => {
    const approvalDate = dayjs(data.approval_date);
    const endDate =
        data.status === 'Terminated'
            ? dayjs(data.termination_date)
            : dayjs();

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

    return data.meta_balance * (managementFee/100);
};

const getSeverity = (status) => {
    switch (status) {
        case 'Terminated':
            return 'danger';

        case 'Rejected':
            return 'danger';

        case 'Active':
            return 'success';

        case 'Pending':
            return 'info';

        case 'Expiring':
            return 'warning';
    }
}

const getTerms = async () => {
    try {
        const response = await axios.get(`/${props.strategyType}_strategy/getTerms`);
        terms.value = response.data;

    } catch (error) {
        console.error('Error fetching trading accounts data:', error);
    }
};

const form = useForm({
    id: props.subscription.id,
    terms: false
})

const submitForm = () => {
    form.post(route('trading.terminateBatch'), {
        onSuccess: () => {
            closeDialog();
        },
    })
}

const closeDialog = () => {
    visible.value = false;
}
</script>

<template>
    <Button
        type="button"
        rounded
        size="small"
        severity="secondary"
        class="!p-2"
        @click="openDialog"
    >
        <IconSettings size="16" />
    </Button>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.subscription_details')"
        class="dialog-xs md:dialog-md"
    >
        <div class="flex flex-col items-center self-stretch gap-5 md:gap-8">
            <div class="py-5 px-6 flex flex-col items-center gap-4 bg-gray-50 dark:bg-gray-950 divide-y dark:divide-gray-700 self-stretch">
                <div class="w-full flex items-center gap-4">
                    <div class="flex flex-col items-start self-stretch">
                        <div class="self-stretch truncate w-[190px] md:w-64 text-gray-950 dark:text-white font-bold">
                            <div v-if="locale === 'cn'">
                                {{ subscription.master.trading_user.company ? subscription.master.trading_user.company : subscription.master.trading_user.name }}
                            </div>
                            <div v-else>
                                {{ subscription.master.trading_user.name }}
                            </div>
                        </div>
                        <div class="self-stretch truncate w-24 text-gray-500 text-sm">
                            {{ subscription.master.meta_login }}
                        </div>
                    </div>
                </div>
                <div class="flex flex-col items-start gap-3 w-full pt-4 self-stretch">
                    <div class="flex flex-col gap-1 items-center self-stretch">
                        <div class="flex py-1 gap-3 items-start self-stretch">
                            <span class="w-full text-gray-500 font-medium text-xs">{{ $t('public.account_no') }}</span>
                            <span class="w-full text-gray-950 dark:text-white font-medium text-sm">{{ subscription.meta_login }}</span>
                        </div>
                        <div class="flex py-1 gap-3 items-start self-stretch">
                            <span class="w-full text-gray-500 font-medium text-xs">{{ $t('public.subscription_number') }}</span>
                            <span class="w-full text-gray-950 dark:text-white font-medium text-sm">{{ subscription.subscription_number }}</span>
                        </div>
                        <div class="flex py-1 gap-3 items-start self-stretch">
                            <span class="w-full text-gray-500 font-medium text-xs">{{ $t('public.join_date') }}</span>
                            <span class="w-full text-gray-950 dark:text-white font-medium text-sm">{{ dayjs(subscription.approval_date).format('YYYY/MM/DD') }} ({{ getJoinedDays(subscription) }} {{ $t('public.days') }})</span>
                        </div>
                        <div class="flex py-1 gap-3 items-start self-stretch">
                            <span class="w-full text-gray-500 font-medium text-xs">{{ $t('public.investment_amount') }}</span>
                            <span class="w-full text-gray-950 dark:text-white font-medium text-sm">$ {{ formatAmount(subscription.meta_balance) }}</span>
                        </div>
                        <div class="flex py-1 gap-3 items-start self-stretch">
                            <span class="w-full text-gray-500 font-medium text-xs">{{ $t('public.status') }}</span>
                            <div class="w-full">
                                <Tag :severity="getSeverity(subscription.status)" :value="subscription.status" />
                            </div>
                        </div>
                        <div
                            v-if="subscription.status === 'Terminated'"
                            class="flex py-1 gap-3 items-start self-stretch"
                        >
                            <span class="w-full text-gray-500 font-medium text-xs">{{ $t('public.termination_date') }}</span>
                            <span class="w-full text-error-500 dark:text-white font-medium text-sm">{{ dayjs(subscription.termination_date).format('YYYY/MM/DD') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Terminate details -->
            <div
                v-if="(subscription.status === 'Active' || subscription.status === 'Expiring') && subscription.terminateBadgeStatus"
                class="flex flex-col gap-3 items-center self-stretch"
            >
                <div class="text-gray-600 dark:text-gray-400 w-full text-sm font-semibold">
                    {{ $t('public.terminate_details') }}
                </div>
                <div class="flex flex-col gap-1 items-start self-stretch">
                    <div class="flex items-start justify-between gap-2 text-xs self-stretch">
                        <div class="font-semibold text-gray-500 dark:text-gray-400">
                            {{$t('public.management_fee')}}
                        </div>
                        <div class="text-error-500 font-bold">
                            $ {{ formatAmount(calculateManagementFee(subscription)) }}
                        </div>
                    </div>
                    <div class="flex items-start justify-between gap-2 text-xs self-stretch">
                        <div class="font-semibold text-gray-500 dark:text-gray-400">
                            {{$t('public.return_amount')}}
                        </div>
                        <div class="text-success-500 font-bold">
                            $ {{ formatAmount(subscription.meta_balance - calculateManagementFee(subscription)) }}
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
                                :managementFee="subscription.master.master_management_fee"
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
        </div>
    </Dialog>
</template>
