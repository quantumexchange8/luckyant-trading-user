<script setup>
import Button from "@/Components/Button.vue";
import {CurrencyDollarIcon, MailIcon} from "@heroicons/vue/outline";
import { XIcon } from "@/Components/Icons/outline.jsx"
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
    PaymentDetails: Object,
})

const depositModal = ref(false);

const openDepositModal = () => {
    depositModal.value = true;
}

const closeModal = () => {
    depositModal.value = false;
}

const selectedReceipt = ref(null);
const selectedReceiptName = ref(null);

const form = useForm({
    wallet_id: props.walletSel[0].value,
    amount: '',
    receipt: null,
    txn_hash: '',
})

const onReceiptChanges = (event) => {
    const receiptInput = event.target;
    const file = receiptInput.files[0];

    if (file) {
        const reader = new FileReader();
        reader.onload = () => {
            selectedReceipt.value = reader.result
        };
        reader.readAsDataURL(file);
        selectedReceiptName.value = file.name;
        form.receipt = event.target.files[0];
    } else {
        selectedReceipt.value = null;
    }
}

const removeReceipt = () => {
    selectedReceipt.value = null;
}

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
        <div class="flex flex-col p-2.5 mb-3 text-sm text-gray-800 rounded-lg bg-gray-100 dark:bg-gray-700 dark:text-white">
            <div class="text-lg font-semibold">
                Payment Information
            </div>
            <div v-if="props.PaymentDetails.payment_method == 'Bank'" class="py-2">
                <div class="flex items-center gap-3 pb-1">
                    <div>Payment Method:</div>
                    <div>
                        {{ props.PaymentDetails.payment_method }}
                    </div>
                </div>
                <div class="flex items-center gap-3 pb-1">
                    <div>
                        Bank:
                    </div>
                    <div>
                        {{ props.PaymentDetails.payment_platform_name }}
                    </div>
                </div>
                <div class="flex items-center gap-3 pb-1">
                    <div>
                        Account No:
                    </div>
                    <div>
                        {{ props.PaymentDetails.account_no }}
                    </div>
                </div>
                <div class="flex items-center gap-3 pb-1">
                    <div>
                        Account Name:
                    </div>
                    <div>
                        {{ props.PaymentDetails.payment_account_name }}
                    </div>
                </div>
            </div>
            <div v-else-if="props.PaymentDetails.payment_method == 'Crypto'" class="py-2">
                <div class="flex items-center gap-3 pb-1">
                    <div>Payment Method:</div>
                    <div>
                        {{ props.PaymentDetails.payment_method }}
                    </div>
                </div>
                <div class="flex items-center gap-3 pb-1">
                    <div>
                        Tether:
                    </div>
                    <div>
                        {{ props.PaymentDetails.payment_platform_name }}
                    </div>
                </div>
                <div class="flex items-center gap-3 pb-1">
                    <div>
                        Wallet Address:
                    </div>
                    <div>
                        {{ props.PaymentDetails.account_no }}
                    </div>
                </div>
                <div class="flex items-center gap-3 pb-1">
                    <div>
                        Wallet Name:
                    </div>
                    <div>
                        {{ props.PaymentDetails.payment_account_name }}
                    </div>
                </div>
            </div>
            
        </div>
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

            <!-- <div class="flex flex-col sm:flex-row gap-4 pt-2">
                <Label class="text-sm dark:text-white w-full md:w-1/4 pt-0.5" for="txn_hash" value="Txn Hash" />
                <div class="flex flex-col w-full">
                    <Input
                        id="txn_hash"
                        type="text"
                        min="0"
                        placeholder="txn hash"
                        class="block w-full"
                        v-model="form.txn_hash"
                        :invalid="form.errors.txn_hash"
                    />
                    <InputError :message="form.errors.txn_hash" class="mt-2" />
                </div>
            </div> -->

            <div class="flex flex-col sm:flex-row gap-4 pt-2">
                <Label for="receipt" class="text-sm dark:text-white md:w-1/4" value="Payment Slip"/>
                <div v-if="selectedReceipt == null" class="flex gap-3 w-full">
                    <input
                        ref="receiptInput"
                        id="receipt"
                        type="file"
                        class="hidden"
                        accept="image/*"
                        @change="onReceiptChanges"
                    />
                    <Button
                        type="button"
                        variant="primary"
                        @click="$refs.receiptInput.click()"
                        class="justify-center gap-2"
                    >
                        <span>Browse</span>
                    </Button>
                </div>
                <div
                    v-if="selectedReceipt"
                    class="relative w-full py-2 pl-4 flex justify-between rounded-lg border focus:ring-1 focus:outline-none"
                    
                >
                    <div class="inline-flex items-center gap-3">
                        <img :src="selectedReceipt" alt="Selected Image" class="max-w-full h-9 object-contain rounded" />
                        <div class="text-gray-light-900 dark:text-white">
                            {{ selectedReceiptName }}
                        </div>
                    </div>
                    <Button
                        type="button"
                        variant="transparent"
                        pill
                        @click="removeReceipt"
                    >
                        <XIcon/>
                    </Button>
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
