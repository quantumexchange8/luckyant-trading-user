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
    walletSel: Array,
    eWalletSel: Array,
    wallet: Object,
});
const internalTransferModal = ref(false);
const loading = ref(trans('public.is_loading'));
const { formatAmount } = transactionFormat();

const form = useForm({
    from_wallet: props.wallet.id,
    to_wallet: props.eWalletSel[0].value,
    amount: '',
});

const openInternalTransferModal = () => {
    internalTransferModal.value = true;
};

const closeModal = () => {
    internalTransferModal.value = false;
};

const submit = () => {
    form.post(route('transaction.internalTransferWallet'), {
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    });
}

</script>

<template>
    <Button type="button" size="sm" variant="primary" class="w-full flex justify-center gap-1"
        v-slot="{ iconSizeClasses }" @click="openInternalTransferModal">
        <CreditCardRefreshIcon aria-hidden="true" :class="iconSizeClasses" />
        {{ $t('public.internal_transfer') }}
    </Button>

    <Modal :show="internalTransferModal" :title="$t('public.internal_transfer')" @close="closeModal">
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
                <Label class="text-sm dark:text-white w-full md:w-1/4 pt-0.5" for="wallet" :value="$t('public.transfer_to')" />
                <div class="flex flex-col w-full">
                    <div v-if="eWalletSel">
                        <BaseListbox
                            :options="wallet.type === 'cash_wallet' ? eWalletSel : walletSel"
                            :placeholder="$t('public.placeholder')"
                            v-model="form.to_wallet"
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
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-4 pt-2">
                <Label class="text-sm dark:text-white w-full md:w-1/4" for="amount" :value="$t('public.amount') + ' ($)'" />
                <div class="flex flex-col w-full">
                    <Input
                        id="amount"
                        type="number"
                        min="0"
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
