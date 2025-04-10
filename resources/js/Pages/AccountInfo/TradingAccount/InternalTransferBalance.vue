<script setup>
import {useForm} from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import Button from "primevue/button";
import {ref} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import Select from "primevue/select";
import InputNumber from "primevue/inputnumber";
import InputLabel from "@/Components/Label.vue";

const props = defineProps({
    account: Object,
})
const emit = defineEmits(['update:accountActionModal']);
const tradingAccountsSel = ref();
const { formatAmount } = transactionFormat();

const form = useForm({
    amount: null,
    to_meta_login: '',
    from_meta_login: props.account.meta_login,
})

const closeDialog = () => {
    emit('update:accountActionModal', false);
}

const submitForm = () => {
    form.post(route('account_info.internalTransferTradingAccount'), {
        onSuccess: () => {
            closeDialog();
            form.reset();
        },
        onError: (errors) => {
            console.error(errors)
        }
    });
}

const isLoading = ref(false);

const getTradingAccounts = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get('/account_info/getTradingAccounts?type=internal_transfer&meta_login=' + props.account.meta_login);
        tradingAccountsSel.value = response.data;
        form.to_meta_login = tradingAccountsSel.value.length > 0 ? tradingAccountsSel.value[0] : null;
    } catch (error) {
        console.error('Error refreshing trading accounts data:', error);
    } finally {
        isLoading.value = false;
    }
};

getTradingAccounts();
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
                for="to_meta_login"
                :value="$t('public.transfer_to')"
            />
            <Select
                input-id="to_meta_login"
                v-model="form.to_meta_login"
                :options="tradingAccountsSel"
                :placeholder="$t('public.select_account')"
                class="w-full"
                :invalid="!!form.errors.to_meta_login"
                :loading="isLoading"
            >
                <template #value="slotProps">
                    <div v-if="slotProps.value" class="flex items-center">
                        {{ slotProps.value.label }}
                    </div>
                    <span v-else class="text-gray-500">{{ slotProps.placeholder }}</span>
                </template>
                <template #option="slotProps">
                    {{ slotProps.option.label }}
                </template>
            </Select>
            <InputError :message="form.errors.to_meta_login"/>
        </div>
        <div class="flex flex-col items-start gap-1 self-stretch">
            <InputLabel
                for="amount"
                :value="$t('public.amount')"
            />
            <InputNumber
                v-model="form.amount"
                inputId="currency-us"
                mode="currency"
                currency="USD"
                class="w-full"
                :min="0"
                :step="100"
                placeholder="$0.00"
                fluid
                :invalid="!!form.errors.amount"
            />
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
