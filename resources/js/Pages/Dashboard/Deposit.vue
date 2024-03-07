<script setup>
import Button from "@/Components/Button.vue";
import {CurrencyDollarIcon, MailIcon} from "@heroicons/vue/outline";
import { XIcon } from "@/Components/Icons/outline.jsx"
import {ref, computed} from "vue";
import Modal from "@/Components/Modal.vue";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import Input from "@/Components/Input.vue";
import Label from "@/Components/Label.vue";
import {useForm} from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import BaseListbox from "@/Components/BaseListbox.vue";
import PaymentDetails from "@/Pages/Dashboard/PaymentDetails.vue"
import {
  RadioGroup,
  RadioGroupLabel,
  RadioGroupDescription,
  RadioGroupOption,
} from '@headlessui/vue'


const props = defineProps({
    walletSel: Array,
    paymentDetails: Array,
    countries: Array,
})

const paymentType = [
  {
    name: 'Bank',
  },
  {
    name: 'Crypto',
  }
]

const depositModal = ref(false);
const selected = ref(null);

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
    payment_method: props.paymentDetails.payment_method,
    account_no: props.paymentDetails.account_no,
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

        <!-- select payment method first -->
        <div class="p-5 bg-gray-100 dark:bg-gray-600 rounded-lg">
            <div class="flex flex-col items-start gap-3 self-stretch">
                <div class="text-lg font-semibold">
                    Payment Methods
                </div>
            </div>

            <div class="w-full px-4 py-4">
                <div class="mx-auto w-full">
                    <RadioGroup v-model="selected">
                        <RadioGroupLabel class="sr-only">Payment Method</RadioGroupLabel>
                        <div class="flex gap-3 items-center self-stretch w-full">
                            <RadioGroupOption
                                as="template"
                                v-for="(type, index) in paymentType"
                                :key="index"
                                :value="type"
                                v-slot="{ active, checked }"
                            >
                                <div
                                    :class="[
                                active
                                    ? 'ring-0 ring-white ring-offset-0'
                                    : '',
                                checked ? 'border-primary-600 dark:border-white bg-primary-500 dark:bg-gray-600 text-white' : 'border-gray-300 bg-white dark:bg-gray-700',
                            ]"
                                    class="relative flex cursor-pointer rounded-xl border p-3 focus:outline-none w-full"
                                >
                                    <div class="flex items-center w-full">
                                        <div class="text-sm flex flex-col gap-3 w-full">
                                            <RadioGroupLabel
                                                as="div"
                                                class="font-medium"
                                            >
                                                <div class="flex justify-center items-center gap-3">
                                                    {{ type.name }}
                                                </div>
                                            </RadioGroupLabel>
                                        </div>
                                    </div>
                                </div>
                            </RadioGroupOption>
                        </div>
                        <InputError :message="form.errors.gender" class="mt-2" />
                    </RadioGroup>
                </div>
            </div>
        </div>

        <!-- show country -->
        <div v-if="selected != null ? selected.name == 'Bank' : '' " class="p-5 mt-3 bg-gray-100 dark:bg-gray-600 rounded-lg">
            <PaymentDetails :countries="countries"/>
        </div>
        <!-- <div v-if="selected.name == 'Crypto'" class="p-5 mt-3 bg-gray-100 dark:bg-gray-600 rounded-lg">
            
        </div> -->

        <!-- show payment information -->
        <div v-if="selected !== null" class="p-5 mt-3 bg-gray-100 dark:bg-gray-600 rounded-lg">
            <div class="flex flex-col items-start gap-3 self-stretch">
                <div class="text-lg font-semibold">
                    Payment Information
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        Payment Method
                    </div>
                    <div class="text-base text-gray-800 dark:text-white font-semibold">
                        {{ paymentDetails.payment_method }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        {{ paymentDetails.payment_method === 'Bank' ? 'Bank Name' : 'Tether' }}
                    </div>
                    <div class="text-base text-gray-800 dark:text-white font-semibold">
                        {{ paymentDetails.payment_platform_name }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        {{ paymentDetails.payment_method === 'Bank' ? 'Account No' : 'Wallet Address' }}
                    </div>
                    <div class="text-base text-gray-800 dark:text-white font-semibold">
                        {{ paymentDetails.account_no }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        {{ paymentDetails.payment_method === 'Bank' ? 'Account Name' : 'Wallet Name' }}
                    </div>
                    <div class="text-base text-gray-800 dark:text-white font-semibold">
                        {{ paymentDetails.payment_account_name }}
                    </div>
                </div>
            </div>
        </div>

        <form class="space-y-2 mt-5">
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
                <div v-if="selectedReceipt == null" class="flex items-center gap-3 w-full">
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
                        class="justify-center gap-2 w-full sm:max-w-24"
                    >
                        <span>Browse</span>
                    </Button>
                    <InputError :message="form.errors.receipt"/>
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

            <div class="pt-5 grid grid-cols-2 gap-4 w-full md:w-1/3 md:float-right">
                <Button variant="transparent" type="button" class="justify-center" @click.prevent="closeModal">
                    {{$t('public.Cancel')}}
                </Button>
                <Button class="justify-center" @click="submit" :disabled="form.processing">{{$t('public.Confirm')}}</Button>
            </div>
        </form>
    </Modal>
</template>
