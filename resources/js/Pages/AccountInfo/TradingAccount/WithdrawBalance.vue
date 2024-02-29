<script setup>
import {useForm} from "@inertiajs/vue3";
import Label from "@/Components/Label.vue";
import Input from "@/Components/Input.vue";
import InputError from "@/Components/InputError.vue";
import Button from "@/Components/Button.vue";
import {transactionFormat} from "@/Composables/index.js";
import {BanIcon} from "@heroicons/vue/outline"

const props = defineProps({
    account: Object,
    walletSel: Array,
})
const emit = defineEmits(['update:accountActionModal']);
const { formatAmount } = transactionFormat();
const form = useForm({
    to_wallet_id: props.walletSel[0].value,
    amount: '',
    from_meta_login: props.account.meta_login,
})

const closeModal = () => {
    emit('update:accountActionModal', false);
}

const submit = () => {
    form.post(route('account_info.withdrawTradingAccount'), {
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    });
}

</script>

<template>
    <form v-if="$page.props.auth.user.kyc_approval === 'Verified'" class="space-y-2">
        <div class="flex flex-col sm:flex-row gap-4">
            <Label class="text-sm dark:text-white w-full md:w-1/4" for="amount" :value="$t('public.Amount')  + ' ($)'" />
            <div class="flex flex-col w-full">
                <Input
                    id="amount"
                    type="number"
                    min="0"
                    placeholder="$ 30.00"
                    class="block w-full"
                    v-model="form.amount"
                    :invalid="form.errors.amount"
                />
                <InputError :message="form.errors.amount" class="mt-2" />
                <span class="text-sm text-gray-600 dark:text-gray-400 mt-2">{{ account.meta_login }}: ${{ account.balance ? formatAmount(account.balance) : '0.00' }}</span>
            </div>
        </div>

        <div class="pt-5 grid grid-cols-2 gap-4 w-full md:w-1/3 md:float-right">
            <Button variant="transparent" type="button" class="justify-center" @click.prevent="closeModal">
                {{$t('public.Cancel')}}
            </Button>
            <Button class="justify-center" @click="submit" :disabled="form.processing">{{$t('public.Confirm')}}</Button>
        </div>
    </form>

    <div
        v-else
        class="flex flex-col gap-4 items-center justify-center w-full"
    >
        <div class="bg-error-400 rounded-full w-20 h-20">
            <BanIcon class="text-white" />
        </div>
        <div class="flex flex-col items-center">
            <div class="text-xl text-gray-800 font-semibold">
                Account verification required
            </div>
            <div class="text-gray-500">
                No balance withdrawal permitted until verified.
            </div>
        </div>
        <div class="flex justify-center w-full">
            <Button
                external
                type="button"
                variant="primary"
                class="items-center gap-2 max-w-xs"
                :href="route('profile.edit')"
            >
                Verify Account
            </Button>
        </div>
    </div>
</template>
