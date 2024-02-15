<script setup>
import Button from "@/Components/Button.vue";
import {CurrencyDollarIcon, MailIcon} from "@heroicons/vue/outline";
import {ref} from "vue";
import Modal from "@/Components/Modal.vue";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import Input from "@/Components/Input.vue";
import Label from "@/Components/Label.vue";
import {useForm} from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import BaseListbox from "@/Components/BaseListbox.vue";

const props = defineProps({
    walletSel: Array,
})

const depositModal = ref(false);

const openDepositModal = () => {
    depositModal.value = true;
}

const closeModal = () => {
    depositModal.value = false;
}

const form = useForm({
    wallet_id: props.walletSel[0].value,
    amount: '',
})

const submit = () => {
    form.post(route('transaction.deposit'), {
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    });
}
</script>

<template>
    <Button
        type="button"
        size="sm"
        variant="success"
        class="w-full flex justify-center gap-1"
        v-slot="{ iconSizeClasses }"
        @click="openDepositModal"
    >
        <CurrencyDollarIcon aria-hidden="true" :class="iconSizeClasses" />
        Deposit
    </Button>

    <Modal :show="depositModal" title="Deposit" @close="closeModal">
        <form class="space-y-2">
            <div class="flex flex-col sm:flex-row gap-4">
                <Label class="text-sm dark:text-white w-full md:w-1/4 pt-0.5" for="wallet" value="Wallet" />
                <div class="flex flex-col w-full">
                    <BaseListbox
                        :options="walletSel"
                        v-model="form.wallet_id"
                        :error="!!form.errors.wallet_id"
                    />
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 pt-2">
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
                </div>
            </div>

            <div class="py-5 grid grid-cols-2 gap-4 w-full md:w-1/3 md:float-right">
                <Button variant="transparent" type="button" class="justify-center" @click.prevent="closeModal">
                    {{$t('public.Cancel')}}
                </Button>
                <Button class="justify-center" @click="submit" :disabled="form.processing">{{$t('public.Confirm')}}</Button>
            </div>
        </form>
    </Modal>
</template>
