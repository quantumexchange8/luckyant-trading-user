<script setup>
import Button from "primevue/button";
import Dialog from "primevue/dialog";
import Select from "primevue/select";
import InputNumber from "primevue/inputnumber";
import Checkbox from "primevue/checkbox";
import {ref, watch} from "vue";
import InputLabel from "@/Components/Label.vue";
import InputText from "primevue/inputtext";
import InputError from "@/Components/InputError.vue";
import {transactionFormat} from "@/Composables/index.js";
import {useForm} from "@inertiajs/vue3";
import TermsAndCondition from "@/Components/TermsAndCondition.vue";
import {MinusIcon, PlusIcon} from "@heroicons/vue/outline";
import {useLangObserver} from "@/Composables/localeObserver.js";
import {
    IconFileInfo
} from "@tabler/icons-vue"

const props = defineProps({
    master: Object,
    strategy: String,
})

const visible = ref(false);
const accountLoading = ref(false);
const accounts = ref([]);
const terms = ref();
const selectedAccount = ref(null);
const {formatAmount} = transactionFormat();
const {locale} = useLangObserver();
const investmentAmount = ref();
const returnAmount = ref();

const openDialog = () => {
    visible.value = true;
    selectedAccount.value = null;
    getAvailableAccounts();
}

const getAvailableAccounts = async () => {
    accountLoading.value = true;
    try {
        const response = await axios.get(`/${props.strategy}_strategy/getAvailableAccounts?master_login=${props.master.meta_login}&account_type=${props.master.trading_user.account_type}`);
        accounts.value = response.data;

    } catch (error) {
        console.error('Error fetching trading accounts data:', error);
    } finally {
        accountLoading.value = false;
    }
};

const form = useForm({
    master_id: props.master.id,
    meta_login: '',
    investment_amount: null,
    amount_package_id: '',
    package_product: '',
    delivery_address: '',
    terms: false,
});

watch(selectedAccount, () => {
    form.meta_login = selectedAccount.value.meta_login;
    investmentAmount.value = selectedAccount.value.balance - (selectedAccount.value.balance % 100);
    form.type = selectedAccount.value.account_type;
});

watch(investmentAmount, (newAmount) => {
    returnAmount.value = selectedAccount.value.balance - newAmount
})

const submitForm = () => {
    form.investment_amount = investmentAmount.value;
    form.post(route('hofi_strategy.followPammMaster'), {
        onSuccess: () => {
            closeDialog();
        }
    })
}

const closeDialog = () => {
    visible.value = false;
    selectedAccount.value = null;
}

const filterPammMedia = (media) => {
    const targetCollection = `${locale.value}_tnc_pdf`;
    return media.filter((item) => item.collection_name === targetCollection);
};

const filterTreeMedia = (media) => {
    const targetCollection = `${locale.value}_tree_pdf`;
    return media.filter((item) => item.collection_name === targetCollection);
};
</script>

<template>
    <Button
        class="w-full"
        @click="openDialog"
    >
        {{ $t('public.subscribe') }}
    </Button>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.subscribe_master')"
        class="dialog-xs md:dialog-md"
    >
        <div class="flex flex-col items-center self-stretch gap-5 md:gap-8">
            <div class="py-5 px-6 flex flex-col items-center gap-4 bg-gray-50 dark:bg-gray-950 divide-y dark:divide-gray-700 self-stretch">
                <div class="w-full flex items-center gap-4">
                    <div class="w-[42px] h-[42px] shrink-0 grow-0 rounded-full overflow-hidden">
                        <img
                            class="object-cover w-10 h-10 rounded-full"
                            :src="master.profile_photo ? master.profile_photo : 'https://img.freepik.com/free-icon/user_318-159711.jpg'"
                            alt="masterPic"
                        />
                    </div>
                    <div class="flex flex-col items-start self-stretch">
                        <div class="self-stretch truncate w-[190px] md:w-64 text-gray-950 dark:text-white font-bold">
                            <div v-if="locale === 'cn'">
                                {{ master.trading_user.company ? master.trading_user.company : master.trading_user.name }}
                            </div>
                            <div v-else>
                                {{ master.trading_user.name }}
                            </div>
                        </div>
                        <div class="self-stretch truncate w-24 text-gray-500 text-sm">
                            {{ master.meta_login }}
                        </div>
                    </div>
                </div>
                <div class="flex flex-col items-start gap-3 w-full pt-4 self-stretch">
                    <div class="flex flex-col gap-1 items-center self-stretch">
                        <div class="flex py-1 gap-3 items-start self-stretch">
                            <span class="w-full text-gray-500 font-medium text-xs">{{ $t('public.minimum_investment') }}</span>
                            <span class="w-full text-gray-950 dark:text-white font-medium text-sm">$ {{ formatAmount(master.min_join_equity) }}</span>
                        </div>
                        <div class="flex py-1 gap-3 items-start self-stretch">
                            <span class="w-full text-gray-500 font-medium text-xs">{{ $t('public.percentage_of_sharing_profit') }}</span>
                            <span class="w-full text-gray-950 dark:text-white font-medium text-sm">{{ formatAmount(master.sharing_profit, 0) }}%</span>
                        </div>
                        <div class="flex py-1 gap-3 items-start self-stretch">
                            <span class="w-full text-gray-500 font-medium text-xs">{{ $t('public.estimated_monthly_returns') }}</span>
                            <span class="w-full text-gray-950 dark:text-white font-medium text-sm">{{ master.estimated_monthly_returns }}</span>
                        </div>
                        <div class="flex py-1 gap-3 items-start self-stretch">
                            <span class="w-full text-gray-500 font-medium text-xs">{{ $t('public.estimated_lot_size') }}</span>
                            <span class="w-full text-gray-950 dark:text-white font-medium text-sm">{{ master.estimated_lot_size }} {{ $t('public.lot') }}</span>
                        </div>
                        <div class="flex py-1 gap-3 items-start self-stretch">
                            <span class="w-full text-gray-500 font-medium text-xs">{{ $t('public.roi_period') }}</span>
                            <span class="w-full text-gray-950 dark:text-white font-medium text-sm lowercase">{{ master.roi_period }} {{ $t('public.days') }}</span>
                        </div>
                        <div class="flex py-1 gap-3 items-start self-stretch">
                            <span class="w-full text-gray-500 font-medium text-xs">{{ $t('public.max_drawdown') }}</span>
                            <span class="w-full text-gray-950 dark:text-white font-medium text-sm lowercase">{{ master.max_drawdown }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3 md:gap-5 items-center self-stretch">
                <div class="flex flex-col items-start gap-1 self-stretch w-full">
                    <InputLabel for="meta_login" :value="$t('public.account_no')" />
                    <Select
                        v-model="selectedAccount"
                        :options="accounts"
                        optionLabel="meta_login"
                        class="w-full"
                        :loading="accountLoading"
                        :invalid="!!form.errors.meta_login"
                        :placeholder="$t('public.placeholder')"
                        :disabled="!accounts.length"
                    >
                        <template #value="slotProps">
                            <div v-if="slotProps.value" class="flex items-center">
                                <div>{{ slotProps.value.meta_login }} (${{ formatAmount(slotProps.value.balance ?? 0) }})</div>
                            </div>
                            <span v-else>{{ slotProps.placeholder }}</span>
                        </template>
                        <template #option="slotProps">
                            <div class="flex items-center gap-1 max-w-[220px] truncate">
                                <span>{{ slotProps.option.meta_login }} (${{ formatAmount(slotProps.option.balance ?? 0) }})</span>
                            </div>
                        </template>
                    </Select>
                    <InputError :message="form.errors.meta_login" />
                </div>
                <div class="flex flex-col items-start gap-1 self-stretch w-full">
                    <InputLabel for="investment_amount" :value="$t('public.investment_amount') + ' ($)'" />
                    <InputNumber
                        v-model="investmentAmount"
                        inputId="investment_amount"
                        class="w-full"
                        :min="Number(master.min_join_equity)"
                        :step="master.type === 'ESG' ? 1000 : 100"
                        fluid
                        showButtons
                        buttonLayout="horizontal"
                        mode="currency"
                        currency="USD"
                        locale="en-US"
                        :placeholder="'$ ' + formatAmount(master.min_join_equity)"
                        :invalid="!!form.errors.investment_amount"
                        :disabled="!selectedAccount"
                    >
                        <template #incrementbuttonicon>
                            <PlusIcon class="w-4 h-4"/>
                        </template>
                        <template #decrementbuttonicon>
                            <MinusIcon class="w-4 h-4"/>
                        </template>
                    </InputNumber>
                    <small v-if="selectedAccount && returnAmount > 0" class="text-error-500">{{ $t('public.amount_return_cash_wallet') }}: <strong>${{ formatAmount(returnAmount) }}</strong></small>
                    <InputError :message="form.errors.investment_amount" />
                </div>

                <!-- Delivery Address -->
                <div
                    v-if="master.delivery_address"
                    class="flex flex-col items-start gap-1 self-stretch w-full"
                >
                    <InputLabel for="delivery_address" :value="$t('public.delivery_address')" />
                    <InputText
                        id="delivery_address"
                        type="text"
                        class="block w-full"
                        v-model="form.delivery_address"
                        :placeholder="$t('public.delivery_address')"
                        :invalid="!!form.errors.delivery_address"
                    />
                    <InputError :message="form.errors.delivery_address" />
                </div>
            </div>

            <!-- ESG Tree notice -->
            <div v-if="master.media.length > 0" class="flex flex-col items-start gap-3 self-stretch">
                <span class="text-gray-950 dark:text-white text-sm font-bold">{{ $t('public.notice') }}</span>
                <div v-if="master.type === 'ESG'" class="text-sm text-gray-500">
                    {{ $t('public.purchase_product_desc') }}
                    <span class="font-semibold text-primary-500">{{ formatAmount((investmentAmount > 0 ? investmentAmount : 0) / 1000, 0) }}</span>
                </div>
                <div class="flex flex-col items-start gap-1 self-stretch">
                    <div
                        v-for="mediaItem in filterPammMedia(master.media)"
                        :key="mediaItem.id"
                        class="flex gap-1 items-center text-xs text-gray-500"
                    >
                        <IconFileInfo size="16" />
                        {{ $t('public.pamm_agreement') }} -
                        <a :href="mediaItem.original_url" target="_blank" class="text-xs underline hover:cursor-pointer text-primary-500 hover:text-primary-700 dark:text-primary-600 dark:hover:text-primary-400">{{ $t('public.view_details') }}</a>
                    </div>
                    <div
                        v-for="mediaItem in filterTreeMedia(master.media)"
                        :key="mediaItem.id"
                        class="flex gap-1 items-center text-xs text-gray-500"
                    >
                        <IconFileInfo size="16" />
                        {{ $t('public.planting_agreement') }} -
                        <a :href="mediaItem.original_url" target="_blank" class="text-xs underline hover:cursor-pointer text-primary-500 hover:text-primary-700 dark:text-primary-600 dark:hover:text-primary-400">{{ $t('public.view_details') }}</a>
                    </div>
                </div>
            </div>

            <!-- t&c -->
            <div class="flex flex-col gap-1 items-start self-stretch">
                <div class="flex items-start gap-2 self-stretch w-full">
                    <Checkbox
                        v-model="form.terms"
                        inputId="terms"
                        binary
                        :invalid="!!form.errors.terms"
                    />
                    <label for="terms" class="text-gray-600 dark:text-gray-400 text-xs">
                        {{ $t('public.agreement') }}
                        <TermsAndCondition
                            :termsLabel="$t('public.terms_and_conditions')"
                            :terms="master.master_term"
                            :managementFee="master.master_management_fee"
                        />
                    </label>
                </div>
                <InputError :message="form.errors.terms" />
            </div>

            <div class="flex w-full justify-end gap-3">
                <Button
                    type="button"
                    severity="secondary"
                    text
                    class="justify-center w-full md:w-auto px-6"
                    @click="closeDialog"
                    :disabled="form.processing"
                >
                    {{ $t('public.cancel') }}
                </Button>
                <Button
                    type="submit"
                    class="justify-center w-full md:w-auto px-6"
                    @click.prevent="submitForm"
                    :disabled="form.processing"
                >
                    {{ $t('public.confirm') }}
                </Button>
            </div>
        </div>
    </Dialog>
</template>
