<script setup>
import BaseListbox from "@/Components/BaseListbox.vue";
import {useForm} from "@inertiajs/vue3";
import Label from "@/Components/Label.vue";
import Input from "@/Components/Input.vue";
import InputError from "@/Components/InputError.vue";
import Button from "@/Components/Button.vue";

const props = defineProps({
    account: Object,
    walletSel: Array,
})
const emit = defineEmits(['update:accountActionModal']);

const form = useForm({
    wallet_id: props.walletSel[0].value,
    amount: '',
    to_meta_login: props.account.meta_login,
})

const closeModal = () => {
    emit('update:accountActionModal', false);
}

const submit = () => {
    form.post(route('account_info.depositTradingAccount'), {
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    });
}

</script>

<template>
    <form class="space-y-2">
        <div class="flex flex-col sm:flex-row gap-4">
            <Label class="text-sm dark:text-white w-full md:w-1/4 pt-0.5" for="wallet" :value="$t('public.sidebar.wallet')" />
            <div class="flex flex-col w-full">
                <BaseListbox
                    :options="walletSel"
                    v-model="form.wallet_id"
                    :error="!!form.errors.wallet_id"
                />
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
