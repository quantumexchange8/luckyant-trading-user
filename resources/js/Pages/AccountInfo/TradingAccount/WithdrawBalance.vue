<script setup>
import {useForm} from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import Button from "primevue/button";
import {transactionFormat} from "@/Composables/index.js";
import InputNumber from "primevue/inputnumber";
import InputLabel from "@/Components/Label.vue";

const props = defineProps({
    account: Object,
    walletSel: Array,
});

const emit = defineEmits(['update:accountActionModal']);
const { formatAmount } = transactionFormat();
const form = useForm({
    to_wallet_id: props.walletSel[0].value,
    amount: null,
    from_meta_login: props.account.meta_login,
    type: props.account.account_type.id,
});

const toggleFullAmount = () => {
    if (form.amount) {
        form.amount = null;
    } else {
        form.amount = Number(props.account.balance);
    }
}

const closeDialog = () => {
    emit('update:accountActionModal', false);
}

const submitForm = () => {
    form.post(route('account_info.withdrawTradingAccount'), {
        onSuccess: () => {
            closeDialog();
            form.reset();
        },
    });
}
</script>

<template>
    <form class="flex flex-col items-center gap-5 self-stretch">
        <div class="flex flex-col justify-center items-center py-4 px-8 gap-2 self-stretch bg-gray-200 dark:bg-gray-950">
            <div class="w-full text-gray-500 text-center text-sm font-medium">
                #{{ account.meta_login }} - {{ $t('public.balance') }}
            </div>
            <div class="w-full text-gray-950 dark:text-white text-center text-xl font-semibold">
                $ {{ formatAmount(account.balance ?? 0) }}
            </div>
        </div>
        <div class="flex flex-col items-start gap-1 self-stretch">
            <InputLabel
                for="amount"
                :value="$t('public.amount')"
            />
            <div class="relative w-full">
                <InputNumber
                    v-model="form.amount"
                    inputId="currency-us"
                    mode="currency"
                    currency="USD"
                    class="w-full"
                    :min="0"
                    placeholder="$0.00"
                    fluid
                    :invalid="!!form.errors.amount"
                />
                <div
                    class="absolute right-4 top-1/2 transform -translate-y-1/2 cursor-pointer text-sm font-semibold"
                    :class="{
                        'text-primary-500 hover:text-primary-600': !form.amount,
                        'text-error-500 hover:text-error-600': form.amount,
                    }"
                    @click="toggleFullAmount"
                >
                    {{ form.amount ? $t('public.clear') : $t('public.full_amount') }}
                </div>
            </div>
            <InputError :message="form.errors.amount"/>
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
    </form>
</template>
