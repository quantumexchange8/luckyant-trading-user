<script setup>
import Button from "@/Components/Button.vue";
import Label from "@/Components/Label.vue";
import InputError from "@/Components/InputError.vue";
import Input from "@/Components/Input.vue";
import {useForm} from "@inertiajs/vue3";
import {ref, watch} from "vue";
import BaseListbox from "@/Components/BaseListbox.vue";
import {
    RadioGroup,
    RadioGroupLabel,
    RadioGroupDescription,
    RadioGroupOption,
} from '@headlessui/vue'

const props = defineProps({
    masterAccount: Object
})

const form = useForm({
    master_id: props.masterAccount.id,
    min_join_equity: props.masterAccount.min_join_equity,
    sharing_profit: props.masterAccount.sharing_profit,
    subscription_fee: props.masterAccount.subscription_fee,
    roi_period: props.masterAccount.roi_period,
    signal_status: ''
})

const plans = [
    {
        name: 'enable',
        value: 1,
    },
    {
        name: 'disable',
        value: 0,
    },
]

const getSelectedPlan = (status) => {
    return plans.find(plan => plan.value === status);
}
const selected = ref(getSelectedPlan(props.masterAccount.signal_status));

const submit = () => {
    form.signal_status = selected.value.value;
    form.post(route('account_info.updateMasterConfiguration'))
}

const badgeVariant = (status) => {
    if (status === 'Active') {
        return 'bg-success-400 dark:bg-success-500'
    } else if(status === 'Inactive') {
        return 'bg-warning-400 dark:bg-warning-500'
    }
};

const roiPeriod = [
    { label: 'Weekly', value: 7 },
    { label: '2 Week', value: 14 },
    { label: 'Monthly', value: 30 },
]
</script>

<template>
    <div class="flex justify-between items-center self-stretch border-b border-gray-300 dark:border-gray-500 pb-2">
        <div class="flex items-center gap-3">
            <div class="text-lg">
                {{ $t('public.copy_trade_configuration') }}
            </div>
        </div>
        <div class="flex justify-end">
            <div
                class="flex w-20 px-2 py-1 justify-center text-white mx-auto rounded-lg hover:-translate-y-1 transition-all duration-300 ease-in-out"
                :class="badgeVariant(masterAccount.status)">{{ masterAccount.status }}
            </div>
        </div>
    </div>

    <form class="w-full">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 w-full">
            <div class="space-y-2">
                <Label
                    for="min_join_equity"
                    :value="$t('public.minimum_equity_to_join')"
                />
                <Input
                    id="min_join_equity"
                    type="number"
                    min="0"
                    :placeholder="$t('public.min_join_equity_placeholder')"
                    class="block w-full"
                    v-model="form.min_join_equity"
                    :invalid="form.errors.min_join_equity"
                />
                <InputError :message="form.errors.min_join_equity" />
            </div>

            <!-- <div class="space-y-2">
                <Label
                    for="sharing_profit"
                    :value="$t('public.sharing_profit') + ' (%)'"
                />
                <Input
                    id="sharing_profit"
                    type="number"
                    min="0"
                    :placeholder="$t('public.sharing_profit_placeholder')"
                    class="block w-full"
                    v-model="form.sharing_profit"
                    :invalid="form.errors.sharing_profit"
                />
                <InputError :message="form.errors.sharing_profit" />
            </div>

            <div class="space-y-2">
                <Label
                    for="subscription_fee"
                    :value="$t('public.subscription_fee')"
                />
                <Input
                    id="subscription_fee"
                    type="number"
                    min="0"
                    :placeholder="$t('public.subscription_fee_placeholder')"
                    class="block w-full"
                    v-model="form.subscription_fee"
                    :invalid="form.errors.subscription_fee"
                />
                <InputError :message="form.errors.subscription_fee" />
            </div> -->

            <div class="space-y-2">
                <Label
                    for="roi_period"
                    :value="$t('public.roi_period')"
                />
                <BaseListbox
                    :options="roiPeriod"
                    v-model="form.roi_period"
                />
                
                <InputError :message="form.errors.roi_period" />
            </div>

            <div class="space-y-2">
                <Label
                    for="signal_status"
                    :value="$t('public.copy_trading_status')"
                />
                <RadioGroup v-model="selected">
                    <RadioGroupLabel class="sr-only">Signal Status</RadioGroupLabel>
                    <div class="flex gap-3 items-center self-stretch w-full">
                        <RadioGroupOption
                            as="template"
                            v-for="(plan, index) in plans"
                            :key="index"
                            :value="plan"
                            v-slot="{ active, checked }"
                        >
                            <div
                                :class="[
                                    active
                                      ? 'ring-0 ring-white ring-offset-0'
                                      : '',
                                    checked ? 'border-primary-600 dark:border-white bg-primary-500 dark:bg-gray-600 text-white' : 'border-gray-300 bg-white dark:bg-gray-700',
                                ]"
                                class="relative flex cursor-pointer rounded-xl border p-3 focus:outline-none w-full"
                            >
                                <div class="flex items-center w-full">
                                    <div class="text-sm flex flex-col gap-3 w-full">
                                        <RadioGroupLabel
                                            as="div"
                                            class="font-medium"
                                        >
                                            <div class="flex justify-center items-center gap-3">
                                                {{ $t('public.' + plan.name) }}
                                            </div>
                                        </RadioGroupLabel>
                                    </div>
                                </div>
                            </div>
                        </RadioGroupOption>
                    </div>
                    <InputError :message="form.errors.signal_status" class="mt-2" />
                </RadioGroup>
            </div>
        </div>

        <div class="pt-5 flex justify-end">
            <Button
                class="flex justify-center"
                @click="submit"
                :disabled="form.processing"
            >
                {{ $t('public.confirm') }}
            </Button>
        </div>
    </form>
</template>
