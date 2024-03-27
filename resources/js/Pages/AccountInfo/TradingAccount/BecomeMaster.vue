<script setup>
import Button from "@/Components/Button.vue";
import Label from "@/Components/Label.vue";
import InputError from "@/Components/InputError.vue";
import Input from "@/Components/Input.vue";
import { useForm } from "@inertiajs/vue3";
import {transactionFormat} from "@/Composables/index.js";
import BaseListbox from "@/Components/BaseListbox.vue";
import { ref } from "vue";

const props = defineProps({
    account: Object
});

const { formatAmount } = transactionFormat();
const emit = defineEmits(['update:accountActionModal']);

const closeModal = () => {
    emit('update:accountActionModal', false);
}

const form = useForm({
    meta_login: props.account.meta_login,
    min_join_equity: '',
    roi_period: '',
    sharing_profit: '',
    subscription_fee: '',
})

const submit = () => {
    form.post(route('account_info.becomeMaster'), {
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    });
}

const roiPeriod = [
    { label: 'Weekly', value: 7 },
    { label: '2 Week', value: 14 },
    { label: 'Monthly', value: 30 },
]

</script>

<template>
    <form class="space-y-2">
        <!-- Account information -->
        <div class="flex p-5 bg-gray-300 dark:bg-gray-600 rounded-lg justify-between items-center">
            <div class="flex flex-col items-start">
                <div class="text-gray-800 dark:text-gray-400 text-sm font-semibold">
                    {{ account.account_type.name }}
                </div>
                <div class="text-gray-600 dark:text-gray-200 text-base">
                    {{ account.meta_login }}
                </div>
            </div>
            <div class="flex flex-col items-start">
                <div class="text-gray-800 dark:text-gray-400 text-sm font-semibold text-right">
                    {{ $t('public.account_equity') }}
                </div>
                <div class="text-gray-600 dark:text-gray-200 text-base w-full text-right">
                    ${{ formatAmount(account.equity) }}
                </div>
            </div>
        </div>
        <!-- Message for becoming master -->
        <div class="text-gray-600 dark:text-gray-400 text-justify text-sm">
            {{ $t('public.become_master_request_message') }}
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 w-full">
            <!-- Input field for minimum equity to join -->
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
                />
                <InputError :message="form.errors.min_join_equity" />
            </div>

            <!-- Input field for ROI period -->
            <div class="space-y-2">
                <Label
                    for="roi_period"
                    :value="$t('public.roi_period')"
                />
                <BaseListbox
                    :options="roiPeriod"
                    v-model="form.roi_period"
                    :placeholder="$t('public.placeholder')"
                />
                <InputError :message="form.errors.roi_period" />
            </div>

            <!-- Input field for sharing profit -->
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
                />
                <InputError :message="form.errors.sharing_profit" />
            </div> -->

            <!-- Input field for subscription fee -->
            <!-- <div class="space-y-2">
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
                />
                <InputError :message="form.errors.subscription_fee" />
            </div> -->
        </div>

        <!-- Submit button -->
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

