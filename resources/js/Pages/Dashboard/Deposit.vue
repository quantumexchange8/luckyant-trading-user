<script setup>
import Button from "@/Components/Button.vue";
import {CurrencyDollarIcon, DuplicateIcon} from "@heroicons/vue/outline";
import {ref, watch} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import InputLabel from "@/Components/Label.vue";
import Skeleton from "primevue/skeleton";
import Select from "primevue/select";
import Image from "primevue/image";
import {useForm} from "@inertiajs/vue3";
import InputNumber from "primevue/inputnumber";
import InputError from "@/Components/InputError.vue";
import QrcodeVue from 'qrcode.vue';
import Tooltip from "@/Components/Tooltip.vue"
import {
    RadioGroup,
    RadioGroupLabel,
    RadioGroupOption,
} from '@headlessui/vue'
import BankImg from "/public/assets/bank.jpg"
import cryptoImg from "/public/assets/cryptocurrency.svg"
import paymentMerchantImg from "/public/assets/payment_merchant.svg"
import FileUpload from "primevue/fileupload";
import {
    IconPhotoPlus,
    IconX,
    IconUpload, IconCircleCheckFilled
} from "@tabler/icons-vue";
import { usePrimeVue } from 'primevue/config';
import PrimeButton from "primevue/button";
import Dialog from "primevue/dialog";

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

const selectPaymentGateway = (paymentGateway) => {
    selectedOptionDetail.value = paymentGateway;
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
        paymentDetails.value = response.data.paymentDetails;

        if (selectedOption.value === 'payment_service') {
            selectedOptionDetail.value = paymentDetails.value;
        } else if (selectedOption.value === 'payment_merchant') {
            selectedOptionDetail.value = paymentDetails.value[0];
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
const amount = ref(null);

watch(selectedOptionDetail, (value) => {
    payment.value = value;
})

const form = useForm({
    setting_payment_id: '',
    wallet_id: props.wallet.id,
    payment_method: '',
    payment_detail: '',
    amount: null,
    images: null,
})

const files = ref([]);
const $primevue = usePrimeVue();

const onRemoveTemplatingFile = (removeFileCallback, index) => {
    removeFileCallback(index);
};

const onSelectedFiles = (event) => {
    files.value = event.files;
};

const formatSize = (bytes) => {
    const k = 1024;
    const dm = 3;
    const sizes = $primevue.config.locale.fileSizeTypes;

    if (bytes === 0) {
        return `0 ${sizes[0]}`;
    }

    const i = Math.floor(Math.log(bytes) / Math.log(k));
    const formattedSize = parseFloat((bytes / Math.pow(k, i)).toFixed(dm));

    return `${formattedSize} ${sizes[i]}`;
};

const submitForm = () => {
    if (selectedOptionDetail.value) {
        form.setting_payment_id = selectedOptionDetail.value.id;
        form.payment_detail = selectedOptionDetail.value;
    }
    form.payment_method = selectedOption.value;
    form.account_no = paymentDetails.value.account_no;
    form.amount = amount.value;
    form.images = files.value;

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

const tooltipContent = ref('copy');

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

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.deposit')"
        class="dialog-xs md:dialog-md"
    >
        <div class="flex flex-col gap-5 self-start">
            <div class="p-6 flex flex-col items-center gap-1 bg-gray-200 dark:bg-gray-800">
                <div class="text-sm text-gray-600 dark:text-gray-400">
                    {{ $t('public.balance')}}
                </div>
                <div class="text-xl font-bold text-gray-950 dark:text-white">
                    ${{ formatAmount(wallet.balance) }}
                </div>
            </div>

            <div class="flex flex-col gap-1 items-start self-stretch">
                <InputLabel
                    :value="$t('public.type')"
                />
                <div v-if="depositOptions" class="w-full">
                    <div class="mx-auto w-full">
                        <RadioGroup v-model="selectedOption">
                            <RadioGroupLabel class="sr-only">{{ $t('public.payment_methods') }}</RadioGroupLabel>
                            <div class="flex sm:flex-row flex-col gap-3 items-center self-stretch w-full">
                                <RadioGroupOption
                                    as="template"
                                    v-for="(option, index) in depositOptions"
                                    :key="index"
                                    :value="option"
                                    v-slot="{ active, checked }"
                                >
                                    <div
                                        :class="[
                                active
                                    ? 'ring-0 ring-white ring-offset-0'
                                    : '',
                                checked ? 'border-primary-600 dark:border-primary-800 bg-primary-500 dark:bg-primary-800 text-white' : 'border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800',
                            ]"
                                        class="relative flex cursor-pointer rounded-xl border p-3 focus:outline-none w-full"
                                    >
                                        <div class="flex items-center w-full">
                                            <div class="text-sm flex flex-col gap-3 w-full">
                                                <RadioGroupLabel
                                                    as="div"
                                                    class="font-medium dark:text-white"
                                                >
                                                    <div class="flex flex-col justify-center items-center gap-1">
                                                        <img v-if="option === 'bank'" class="rounded-full w-10 h-10" :src="BankImg" alt="payment-image">
                                                        <img v-if="option === 'payment_service'" class="rounded-full w-10 h-10" :src="cryptoImg" alt="payment-image">
                                                        <img v-if="option === 'payment_merchant' || option === 'cryptocurrency_service_provider'" class="rounded-full w-10 h-10" :src="paymentMerchantImg" alt="payment-image">
                                                        {{ $t('public.' + option) }}
                                                    </div>
                                                </RadioGroupLabel>
                                            </div>
                                        </div>
                                    </div>
                                </RadioGroupOption>
                            </div>
                            <InputError :message="form.errors.payment_method" class="mt-2" />
                        </RadioGroup>
                    </div>
                </div>
                <Skeleton v-else width="8rem" class="my-1"></Skeleton>
            </div>

            <!-- Bank -->
            <template v-if="selectedOption === 'bank'">
                <Select
                    id="agent"
                    v-model="selectedOptionDetail"
                    :options="paymentDetails"
                    filter
                    :filterFields="['payment_platform_name']"
                    optionLabel="payment_platform_name"
                    :placeholder="$t('public.bank_placeholder')"
                    class="w-full"
                    scroll-height="236px"
                    :loading="loadingPayment"
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
                </Select>
                <InputError :message="form.errors.payment_detail"/>

                <div
                    v-if="selectedOptionDetail"
                    class="flex flex-col gap-2 self-stretch"
                >
                    <div class="flex flex-col justify-center items-start gap-1 self-stretch sm:flex-row sm:justify-normal sm:items-center">
                        <span class="w-[140px] text-gray-500 text-sm font-medium">{{ $t('public.bank_name') }}</span>
                        <span class="self-stretch sm:text-right text-gray-950 dark:text-white text-sm font-medium sm:self-auto sm:flex-grow break-words">{{ payment.payment_platform_name }}</span>
                    </div>
                    <div class="flex flex-col justify-center items-start gap-1 self-stretch sm:flex-row sm:justify-normal sm:items-center">
                        <span class="w-[140px] text-gray-500 text-sm font-medium">{{ $t('public.account_no') }}</span>
                        <span class="self-stretch sm:text-right text-gray-950 dark:text-white text-sm font-medium sm:self-auto sm:flex-grow break-words">{{ payment.account_no }}</span>
                    </div>
                    <div class="flex flex-col justify-center items-start gap-1 self-stretch sm:flex-row sm:justify-normal sm:items-center">
                        <span class="w-[140px] text-gray-500 text-sm font-medium">{{ $t('public.full_name') }}</span>
                        <span class="self-stretch sm:text-right text-gray-950 dark:text-white text-sm font-medium sm:self-auto sm:flex-grow break-words">{{ payment.payment_account_name }}</span>
                    </div>
                    <div class="flex flex-col justify-center items-start gap-1 self-stretch sm:flex-row sm:justify-normal sm:items-center">
                        <span class="w-[140px] text-gray-500 text-sm font-medium">{{ $t('public.country') }}</span>
                        <span class="self-stretch sm:text-right text-gray-950 dark:text-white text-sm font-medium sm:self-auto sm:flex-grow break-words">{{ payment.country.name }}</span>
                    </div>
                    <div class="flex flex-col justify-center items-start gap-1 self-stretch sm:flex-row sm:justify-normal sm:items-center">
                        <span class="w-[140px] text-gray-500 text-sm font-medium">{{ $t('public.amount_transfer') }}</span>
                        <span class="self-stretch sm:text-right text-gray-950 dark:text-white text-sm font-medium sm:self-auto sm:flex-grow break-words">{{ payment.currency }} {{ formatAmount(payment.currency_rate * amount) }}</span>
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
                <div class="flex flex-col gap-1 items-start self-stretch pb-2">
                    <InputLabel
                        :value="$t('public.platform')"
                    />
                    <div
                        v-if="loadingPayment"
                        class="grid grid-cols-1 md:grid-cols-2 items-start gap-3 self-stretch"
                    >
                        <div
                            v-for="account in 2"
                            class="group flex flex-col items-start py-3 px-4 gap-1 self-stretch rounded-lg border shadow-input transition-colors duration-300 select-none cursor-pointer w-full bg-primary-50 dark:bg-gray-800 border-primary-500"
                        >
                                <span
                                    class="flex-grow text-sm font-semibold text-gray-950 dark:text-white"
                                >
                                    {{ $t('public.loading') }}
                                </span>
                        </div>
                    </div>

                    <div
                        v-else
                        class="flex items-start gap-3 self-stretch w-full overflow-x-auto"
                    >
                        <div
                            v-for="paymentGateway in paymentDetails"
                            @click="selectPaymentGateway(paymentGateway)"
                            class="group flex flex-col items-start py-3 px-4 gap-1 self-stretch rounded-lg border shadow-input transition-colors duration-300 select-none cursor-pointer min-w-40"
                            :class="{
                                    'bg-primary-50 dark:bg-gray-800 border-primary-500': selectedOptionDetail.payment_app_name === paymentGateway.payment_app_name,
                                    'bg-white dark:bg-gray-950 border-gray-300 dark:border-gray-700 hover:bg-primary-50 hover:border-primary-500': selectedOptionDetail.payment_app_name !== paymentGateway.payment_app_name,
                                }"
                        >
                            <div class="flex items-center gap-3 self-stretch">
                                <span
                                    class="flex-grow text-sm font-semibold transition-colors duration-300 group-hover:text-primary-700 dark:group-hover:text-primary-500 uppercase"
                                    :class="{
                                        'text-primary-700 dark:text-primary-300': selectedOptionDetail.payment_app_name === paymentGateway.payment_app_name,
                                        'text-gray-950 dark:text-white': selectedOptionDetail.payment_app_name !== paymentGateway.payment_app_name
                                    }"
                                >
                                    {{ paymentGateway.name }}
                                </span>
                                <IconCircleCheckFilled v-if="selectedOptionDetail.payment_app_name === paymentGateway.payment_app_name" size="20" stroke-width="1.25" color="#2970FF" />
                            </div>
                        </div>
                    </div>
                    <InputError :message="form.errors.payment_detail"/>
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
                    v-if="selectedOptionDetail"
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
                            placeholder="$0.00"
                            :invalid="!!form.errors.amount"
                        />
                        <InputError :message="form.errors.amount"/>
                    </div>

                    <div
                        v-if="selectedOption !== 'payment_merchant'"
                        class="flex flex-col gap-1 items-start self-stretch w-full"
                    >
                        <InputLabel
                            for="receipt"
                            :value="$t('public.payment_slip')"
                        />
                        <div
                            class="flex flex-col gap-3 md:gap-5 w-full"
                        >
                            <FileUpload
                                name="demo[]"
                                :multiple="true"
                                accept="image/*"
                                @select="onSelectedFiles"
                            >
                                <template #header="{ chooseCallback, clearCallback, files }">
                                    <div class="flex flex-wrap justify-between items-center flex-1 gap-4">
                                        <div class="flex gap-2">
                                            <PrimeButton
                                                type="button"
                                                severity="secondary"
                                                size="small"
                                                @click="chooseCallback()"
                                                rounded
                                                outlined
                                                class="!p-2"
                                            >
                                                <IconPhotoPlus size="16" stroke-width="1.5" />
                                            </PrimeButton>
                                            <PrimeButton
                                                type="button"
                                                severity="danger"
                                                size="small"
                                                @click="clearCallback()"
                                                rounded
                                                outlined
                                                class="!p-2"
                                                :disabled="!files || files.length === 0"
                                            >
                                                <IconX size="16" stroke-width="1.5" />
                                            </PrimeButton>
                                        </div>
                                    </div>
                                </template>
                                <template #content="{ files, removeFileCallback }">
                                    <div class="flex flex-col gap-3">
                                        <div v-if="files.length > 0">
                                            <div class="flex overflow-x-scroll gap-4">
                                                <div
                                                    v-for="(file, index) of files" :key="file.name + file.type + file.size"
                                                    class="p-5 rounded-border w-full max-w-64 flex flex-col border border-gray-300 dark:border-gray-600 rounded items-center gap-4 relative"
                                                >
                                                    <div class="absolute top-2 right-2">
                                                        <PrimeButton
                                                            type="button"
                                                            severity="danger"
                                                            size="small"
                                                            @click="onRemoveTemplatingFile(removeFileCallback, index)"
                                                            rounded
                                                            text
                                                            class="!px-2"
                                                            :disabled="!files || files.length === 0"
                                                        >
                                                            <IconX size="16" stroke-width="1.5" />
                                                        </PrimeButton>
                                                    </div>
                                                    <div class="max-h-20 mt-5">
                                                        <Image role="presentation" :alt="file.name" :src="file.objectURL" preview imageClass="w-48 object-contain h-20" />
                                                    </div>
                                                    <div class="flex flex-col gap-1 items-center self-stretch w-52">
                                                        <span class="font-semibold text-center text-xs truncate w-full max-w-52">{{ file.name }}</span>
                                                        <div class="text-xxs">{{ formatSize(file.size) }}</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                <template #empty>
                                    <div class="flex items-center justify-center flex-col gap-3 mt-3">
                                        <div class="flex items-center justify-center p-3 text-gray-400 dark:text-gray-600 rounded-full border border-gray-400 dark:border-gray-600">
                                            <IconUpload size="24" stroke-width="1.5" />
                                        </div>
                                        <p class="text-sm">{{ $t('public.drag_and_drop_file') }}</p>
                                        <InputError :message="form.errors.images" />
                                    </div>
                                </template>
                            </FileUpload>
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
                    @click.prevent="submitForm"
                    :disabled="form.processing"
                >
                    {{$t('public.confirm')}}
                </Button>
            </div>
        </div>
    </Dialog>
</template>
