<script setup>
import BaseListbox from "@/Components/BaseListbox.vue";
import {useForm} from "@inertiajs/vue3";
import Label from "@/Components/Label.vue";
import Input from "@/Components/Input.vue";
import InputError from "@/Components/InputError.vue";
import Button from "@/Components/Button.vue";
import {onMounted, ref} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import { trans } from "laravel-vue-i18n";

const props = defineProps({
    account: Object,
})
const emit = defineEmits(['update:accountActionModal']);
const tradingAccountsSel = ref();
const loading = ref(trans('public.is_loading'));
const { formatAmount } = transactionFormat();

const form = useForm({
    amount: '',
    to_meta_login: '',
    to_type: '',
    from_meta_login: props.account.meta_login,
    type: props.account.account_type,
})

const closeModal = () => {
    emit('update:accountActionModal', false);
}

const submit = () => {
    form.post(route('account_info.internalTransferTradingAccount'), {
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    });
}

const getTradingAccounts = async () => {
    try {
        const response = await axios.get('/account_info/getTradingAccounts?type=internal_transfer&meta_login=' + props.account.meta_login);
        tradingAccountsSel.value = response.data;
        form.to_meta_login = tradingAccountsSel.value.length > 0 ? tradingAccountsSel.value[0].value : null;
        form.to_type = tradingAccountsSel.value.length > 0 ? tradingAccountsSel.value[0].account_type : null;
    } catch (error) {
        console.error('Error refreshing trading accounts data:', error);
    }
};

onMounted(() => {
    getTradingAccounts();
});

</script>

<template>
    <form class="space-y-2">
        <div class="flex flex-col sm:flex-row gap-4">
            <Label class="text-sm dark:text-white w-full md:w-1/4 pt-0.5" for="wallet" :value="$t('public.transfer_to')" />
            <div class="flex flex-col w-full">
                <div v-if="tradingAccountsSel">
                    <BaseListbox
                        :options="tradingAccountsSel"
                        v-model="form.to_meta_login"
                        :error="!!form.errors.to_meta_login"
                    />
                </div>
                <div v-else>
                    <Input
                        id="loading"
                        type="text"
                        class="block w-full"
                        v-model="loading"
                        readonly
                    />
                </div>
                <span class="text-sm text-gray-600 dark:text-gray-400 mt-2">{{ $t('public.transfer_from') }}: {{ account.meta_login }} - ${{ account.balance ? formatAmount(account.balance) : '0.00' }}</span>
            </div>
        </div>

        <div class="flex flex-col sm:flex-row gap-4 pt-2">
            <Label class="text-sm dark:text-white w-full md:w-1/4" for="amount" :value="$t('public.amount')  + ' ($)'" />
            <div class="flex flex-col w-full">
                <Input
                    id="amount"
                    type="number"
                    min="0"
                    :placeholder="$t('public.amount_transfer_placeholder')"
                    class="block w-full"
                    v-model="form.amount"
                    :invalid="form.errors.amount"
                />
                <InputError :message="form.errors.amount" class="mt-2" />
            </div>
        </div>

        <div class="pt-5 grid grid-cols-2 gap-4 w-full md:w-1/3 md:float-right">
            <Button variant="transparent" type="button" class="justify-center" @click.prevent="closeModal">
                {{$t('public.cancel')}}
            </Button>
            <Button class="justify-center" @click="submit" :disabled="form.processing">{{$t('public.confirm')}}</Button>
        </div>
    </form>
</template>
