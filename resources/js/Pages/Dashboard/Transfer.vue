<script setup>
import Button from "@/Components/Button.vue";
import { CreditCardRefreshIcon } from "@/Components/Icons/outline";
import { ref, computed } from "vue";
import Modal from "@/Components/Modal.vue";
import BaseListbox from "@/Components/BaseListbox.vue";
import { useForm } from "@inertiajs/vue3";
import Label from "@/Components/Label.vue";
import Input from "@/Components/Input.vue";
import InputError from "@/Components/InputError.vue";
import { transactionFormat } from "@/Composables/index.js";
import { trans } from "laravel-vue-i18n";

const props = defineProps({
    eWalletSel: Array,
    wallet: Object,
});
const transferModal = ref(false);
const loading = ref(trans('public.is_loading'));
const { formatAmount } = transactionFormat();

const form = useForm({
    from_wallet: props.wallet.id,
    wallet_address: '',
    amount: '',
});

const openTransferModal = () => {
    transferModal.value = true;
};

const closeModal = () => {
    transferModal.value = false;
};

const submit = () => {
    form.post(route('transaction.transfer'), {
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    });
}
</script>

<template>
    <Button type="button" size="sm" variant="primary" class="w-full flex justify-center gap-1"
        v-slot="{ iconSizeClasses }" @click="openTransferModal">
        <CreditCardRefreshIcon aria-hidden="true" :class="iconSizeClasses" />
        {{ $t('public.transfer') }}
    </Button>

    <Modal :show="transferModal" :title="$t('public.transfer')" @close="closeModal">
        <form class="space-y-2">
            <div class="p-5 bg-gray-100 dark:bg-gray-600 rounded-lg">

                <div class="flex flex-col items-start gap-3 self-stretch">
                    <div class="text-lg font-semibold dark:text-white">
                        {{ $t('public.transfer_information') }}
                    </div>
                    <div class="flex items-center justify-between gap-2 self-stretch">
                        <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('public.transfer_from') }}
                        </div>
                        <div class="text-base text-gray-800 dark:text-white font-semibold">
                            {{ $t('public.' + wallet.type) }}
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-2 self-stretch">
                        <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('public.amount_transfer') }}
                        </div>
                        <div class="text-base text-gray-800 dark:text-white font-semibold">
                            $ {{ formatAmount(parseFloat(form.amount) || 0.00) }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 pt-2">
                <Label class="text-sm dark:text-white w-full md:w-1/4" for="wallet_address" :value="$t('public.wallet_address')" />
                <div class="flex flex-col w-full">
                    <Input
                        id="wallet_address"
                        type="text"
                        min="0"
                        :placeholder="$t('public.wallet_address')"
                        class="block w-full"
                        :class="form.errors.wallet_address ? 'border border-error-500 dark:border-error-500' : 'border border-gray-400 dark:border-gray-600'"
                        v-model="form.wallet_address"
                    />
                    <InputError :message="form.errors.wallet_address" class="mt-2" />
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 pt-2">
                <Label class="text-sm dark:text-white w-full md:w-1/4" for="amount" :value="$t('public.amount') + ' ($)'" />
                <div class="flex flex-col w-full">
                    <Input
                        id="amount"
                        type="number"
                        min="0"
                        :max="props.wallet.balance"
                        :placeholder="$t('public.zero_placeholder')"
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
    </Modal>
</template>
