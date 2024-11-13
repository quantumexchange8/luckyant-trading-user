<script setup>
import Button from "@/Components/Button.vue";
import {CurrencyDollarIcon, DuplicateIcon, XIcon} from "@heroicons/vue/outline";
import {ref, watch} from "vue";
import Modal from "@/Components/Modal.vue";
import {transactionFormat} from "@/Composables/index.js";
import InputLabel from "@/Components/Label.vue";
import RadioButton from "primevue/radiobutton";
import Skeleton from "primevue/skeleton";
import Dropdown from "primevue/dropdown";
import Image from "primevue/image";
import {useForm} from "@inertiajs/vue3";
import InputNumber from "primevue/inputnumber";
import InputError from "@/Components/InputError.vue";
import QrcodeVue from 'qrcode.vue';
import Tooltip from "@/Components/Tooltip.vue"

const props = defineProps({
    wallet: Object
})

const visible = ref(false);
const depositOptions = ref();
const loadingOptions = ref(false);
const paymentDetails = ref();
const loadingPayment = ref(false);
const {formatAmount} = transactionFormat();
const selectedOption = ref();

const openModal = () => {
    visible.value = true;
    getDepositOptions();
}

const getDepositOptions = async () => {
    loadingOptions.value = true;
    try {
        const response = await axios.get('/getDepositOptions');

        depositOptions.value = response.data.depositOptions;

    } catch (error) {
        console.error(error);
    } finally {
        loadingOptions.value = false;
    }
}

const getPaymentDetails = async () => {
    loadingPayment.value = true;
    try {
        const response = await axios.get(`/getPaymentDetails?type=${selectedOption.value}`);

        if (selectedOption.value === 'payment_service') {
            payment.value = response.data.paymentDetails;
            selectedOptionDetail.value = payment.value;
        } else {
            paymentDetails.value = response.data.paymentDetails;
        }

    } catch (error) {
        console.error(error);
    } finally {
        loadingPayment.value = false;
    }
}

watch(selectedOption, (value) => {
    selectedOptionDetail.value = null;
    getPaymentDetails()
})

const selectedOptionDetail = ref();
const payment = ref();
const amount = ref(0);

watch(selectedOptionDetail, (value) => {
    payment.value = value;
})

const form = useForm({
    setting_payment_id: '',
    wallet_id: props.wallet.id,
    payment_method: '',
    payment_detail: '',
    amount: 0,
    receipt: '',
})

const selectedReceipt = ref(null);
const selectedReceiptName = ref(null);

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

const submitForm = () => {
    form.setting_payment_id = selectedOptionDetail.value.id;
    form.payment_method = selectedOption.value;
    form.payment_detail = selectedOptionDetail.value;
    form.account_no = paymentDetails.value.account_no;
    form.amount = amount.value;

    form.post(route('transaction.deposit'), {
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    });
}

const closeModal = () => {
    visible.value = false;
}

const tooltipContent = ref('');

const copyWalletAddress = () => {
    let walletAddressCopy = document.querySelector('#cryptoWalletAddress');
    walletAddressCopy.setAttribute('type', 'text');
    walletAddressCopy.select();

    try {
        var successful = document.execCommand('copy');
        if (successful) {
            tooltipContent.value = 'copied';
            setTimeout(() => {
                tooltipContent.value = 'copy'; // Reset tooltip content to 'Copy' after 2 seconds
            }, 1000);
        } else {
            tooltipContent.value = 'try_again_later';
        }

    } catch (err) {
        alert('copy_error');
    }

    /* unselect the range */
    walletAddressCopy.setAttribute('type', 'hidden')
    window.getSelection().removeAllRanges()
}
</script>

<template>
    <Button
        type="button"
        size="sm"
        variant="success"
        class="w-full flex justify-center gap-1"
        v-slot="{ iconSizeClasses }"
        @click="openModal"
    >
        <CurrencyDollarIcon aria-hidden="true" :class="iconSizeClasses" />
        {{ $t('public.deposit') }}
    </Button>

    <Modal
        :show="visible"
        :title="$t('public.deposit')"
        @close="closeModal"
    >
        <div class="flex flex-col gap-5 self-start">
            <div class="p-6 flex flex-col items-center gap-1 bg-gray-200 dark:bg-gray-800">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $t('public.balance')}}
                </div>
                <div class="text-2xl font-bold text-gray-950 dark:text-white">
                    ${{ formatAmount(wallet.balance) }}
                </div>
            </div>

            <div class="flex flex-col gap-1 items-start self-stretch">
                <InputLabel
                    :value="$t('public.type')"
                />
                <div v-if="depositOptions" class="flex flex-wrap gap-4">
                    <div v-for="option in depositOptions" class="flex items-center">
                        <RadioButton
                            v-model="selectedOption"
                            :inputId="option"
                            :value="option"
                        />
                        <InputLabel :for="option" class="ml-2">{{ $t(`public.${option}`) }}</InputLabel>
                    </div>
                </div>
                <Skeleton v-else width="8rem" class="my-1"></Skeleton>
            </div>

            <!-- Bank -->
            <template v-if="selectedOption === 'bank'">
                <Dropdown
                    id="agent"
                    v-model="selectedOptionDetail"
                    :options="paymentDetails"
                    filter
                    :filterFields="['payment_platform_name']"
                    optionLabel="payment_platform_name"
                    :placeholder="$t('public.bank_placeholder')"
                    class="w-full"
                    scroll-height="236px"
                    :invalid="!!form.errors.payment_method"
                >
                    <template #value="slotProps">
                        <div v-if="slotProps.value" class="flex items-center gap-3">
                            <div class="flex items-center gap-2">
                                <div class="w-5 h-5 rounded-full overflow-hidden">
                                    <template v-if="slotProps.value.media">
                                        <img :src="slotProps.value.media[0].original_url" alt="profile_picture" />
                                    </template>
                                </div>
                                <div>{{ slotProps.value.payment_platform_name }}</div>
                            </div>
                        </div>
                        <span v-else class="text-gray-400">{{ slotProps.placeholder }}</span>
                    </template>
                </Dropdown>

                <div
                    v-if="selectedOptionDetail"
                    class="flex flex-col gap-2 self-stretch"
                >
                    <div class="flex flex-col justify-center items-start gap-1 self-stretch sm:flex-row sm:justify-normal sm:items-center">
                        <span class="w-[140px] text-gray-500 text-sm font-medium">{{ $t('public.bank_name') }}</span>
                        <span class="self-stretch sm:text-right text-gray-950 text-sm font-medium sm:self-auto sm:flex-grow break-words">{{ payment.payment_platform_name }}</span>
                    </div>
                    <div class="flex flex-col justify-center items-start gap-1 self-stretch sm:flex-row sm:justify-normal sm:items-center">
                        <span class="w-[140px] text-gray-500 text-sm font-medium">{{ $t('public.account_no') }}</span>
                        <span class="self-stretch sm:text-right text-gray-950 text-sm font-medium sm:self-auto sm:flex-grow break-words">{{ payment.account_no }}</span>
                    </div>
                    <div class="flex flex-col justify-center items-start gap-1 self-stretch sm:flex-row sm:justify-normal sm:items-center">
                        <span class="w-[140px] text-gray-500 text-sm font-medium">{{ $t('public.full_name') }}</span>
                        <span class="self-stretch sm:text-right text-gray-950 text-sm font-medium sm:self-auto sm:flex-grow break-words">{{ payment.payment_account_name }}</span>
                    </div>
                    <div class="flex flex-col justify-center items-start gap-1 self-stretch sm:flex-row sm:justify-normal sm:items-center">
                        <span class="w-[140px] text-gray-500 text-sm font-medium">{{ $t('public.country') }}</span>
                        <span class="self-stretch sm:text-right text-gray-950 text-sm font-medium sm:self-auto sm:flex-grow break-words">{{ payment.country.name }}</span>
                    </div>
                    <div class="flex flex-col justify-center items-start gap-1 self-stretch sm:flex-row sm:justify-normal sm:items-center">
                        <span class="w-[140px] text-gray-500 text-sm font-medium">{{ $t('public.amount_transfer') }}</span>
                        <span class="self-stretch sm:text-right text-gray-950 text-sm font-medium sm:self-auto sm:flex-grow break-words">{{ payment.currency }} {{ formatAmount(payment.currency_rate * amount) }}</span>
                    </div>
                </div>
            </template>

            <!-- Crypto -->
            <template v-if="selectedOption === 'payment_service'">
                <div v-if="payment" class="flex flex-col items-center justify-center gap-2">
                    <qrcode-vue
                        :class="['border-4 border-white']"
                        :value="payment.account_no"
                        :size="200"
                    ></qrcode-vue>
                    <input
                        type="hidden"
                        id="cryptoWalletAddress"
                        :value="payment.account_no"
                    />
                    <div class="flex items-center gap-1">
                        <span class="text-base text-gray-800 dark:text-white font-semibold">{{ payment.account_no }}</span>
                        <Tooltip :content="$t('public.' + tooltipContent)" placement="top">
                            <DuplicateIcon class="w-5 h-5 mt-1 text-gray-600 dark:text-white hover:cursor-pointer" @click="copyWalletAddress" />
                        </Tooltip>
                    </div>
                </div>
            </template>

            <!-- Payment Gateway -->
            <template v-if="selectedOption === 'payment_merchant'">
                <div class="flex flex-col gap-1 items-start self-stretch">
                    <InputLabel
                        :value="$t('public.platform')"
                    />
                    <div v-if="!loadingPayment" class="flex flex-wrap gap-4">
                        <div v-for="option in paymentDetails" class="flex items-center">
                            <RadioButton
                                v-model="selectedOptionDetail"
                                :inputId="option.platform"
                                :value="option"
                            />
                            <InputLabel :for="option.platform" class="ml-2 text-sm uppercase">{{ option.platform }}</InputLabel>
                        </div>
                    </div>
                    <Skeleton v-else width="8rem" class="my-1"></Skeleton>
                </div>
            </template>

            <!-- Cryptocurrency service provider -->
            <template v-if="selectedOption === 'cryptocurrency_service_provider'">
                <div class="text-base text-gray-800 dark:text-white font-semibold">
                    <a
                        href="https://transak.com/"
                        target="_blank"
                        class="text-blue-500 hover:underline"
                    >
                        {{ 'Transak' }}
                    </a>
                </div>
            </template>

            <form>
                <div
                    v-if="selectedOptionDetail && payment.platform !== 'ttpay'"
                    class="flex flex-col gap-5 items-center self-stretch border-t border-gray-200 dark:border-gray-800"
                >
                    <div
                        class="flex flex-col gap-1 items-start self-stretch mt-5"
                    >
                        <InputLabel
                            for="amount"
                            :value="$t('public.amount')"
                        />
                        <InputNumber
                            v-model="amount"
                            class="w-full"
                            inputId="horizontal-buttons"
                            buttonLayout="horizontal"
                            :min="0"
                            :step="100"
                            mode="currency"
                            currency="USD"
                            fluid
                            :invalid="!!form.errors.amount"
                        />
                        <InputError :message="form.errors.amount"/>
                    </div>

                    <div
                        v-if="selectedOption !== 'payment_merchant'"
                        class="flex flex-col gap-1 items-start self-stretch"
                    >
                        <InputLabel
                            for="receipt"
                            :value="$t('public.payment_slip')"
                        />
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
                                <span>{{ $t('public.browse') }}</span>
                            </Button>
                            <InputError :message="form.errors.receipt"/>
                        </div>
                        <div
                            v-if="selectedReceipt"
                            class="relative w-full py-2 pl-2 flex justify-between rounded-lg border focus:ring-1 focus:outline-none"

                        >
                            <div class="inline-flex items-center gap-3">
                                <Image
                                    :src="selectedReceipt"
                                    preview
                                    alt="Selected Image"
                                    image-class="w-10 h-8 object-contain rounded" />
                                <div class="text-gray-light-900 dark:text-white">
                                    {{ selectedReceiptName }}
                                </div>
                            </div>
                            <Button
                                type="button"
                                variant="transparent"
                                pill
                                @click="removeReceipt"
                                size="sm"
                                v-slot="{ iconSizeClasses }"
                            >
                                <XIcon :class="iconSizeClasses" />
                            </Button>
                        </div>
                    </div>
                </div>
            </form>

            <div v-if="selectedOption && selectedOption !== 'cryptocurrency_service_provider'" class="flex justify-end gap-5 items-center">
                <Button
                    variant="transparent"
                    type="button"
                    class="justify-center w-full md:w-auto"
                    @click.prevent="closeModal"
                >
                    {{$t('public.cancel')}}
                </Button>
                <Button
                    class="justify-center w-full md:w-auto"
                    @click="submitForm"
                    :disabled="form.processing"
                >
                    {{$t('public.confirm')}}
                </Button>
            </div>
        </div>
    </Modal>
</template>
