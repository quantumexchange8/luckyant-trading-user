<script setup>
import Button from "@/Components/Button.vue";
import {onMounted, ref} from "vue";
import Modal from "@/Components/Modal.vue";
import {transactionFormat} from "@/Composables/index.js";
import BaseListbox from "@/Components/BaseListbox.vue";
import InputError from "@/Components/InputError.vue";
import Label from "@/Components/Label.vue";
import {useForm, usePage} from "@inertiajs/vue3";
import Input from "@/Components/Input.vue";

const props = defineProps({
    masterAccount: Object,
    terms: Object,
})

const subscribeAccountModal = ref(false);
const tradingAccountsSel = ref();
const loading = ref('Loading..');
const { formatAmount } = transactionFormat();

const openSubscribeAccountModal = () => {
    subscribeAccountModal.value = true;
};

const closeModal = () => {
    subscribeAccountModal.value = false;
}

const form = useForm({
    master_id: props.masterAccount.id,
    meta_login: ''
})

const getTradingAccounts = async () => {
    try {
        const response = await axios.get('/account_info/getTradingAccounts?type=subscribe&meta_login=' + props.masterAccount.meta_login);
        tradingAccountsSel.value = response.data;
        form.meta_login = tradingAccountsSel.value.length > 0 ? tradingAccountsSel.value[0].value : null;
    } catch (error) {
        console.error('Error refreshing trading accounts data:', error);
    }
};

getTradingAccounts();

const submit = () => {
    form.post(route('trading.subscribeMaster'), {
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    });
}

const currentLocale = ref(usePage().props.locale);
const termsModal = ref(false);

const openTermsModal = () => {
    termsModal.value = true
}

const closeTermsModal = () => {
    termsModal.value = false
}
</script>

<template>
    <Button
        type="button"
        class="w-full flex justify-center"
        @click="openSubscribeAccountModal"
    >
        {{ $t('public.subscribe') }}
    </Button>

    <Modal :show="subscribeAccountModal" :title="$t('public.subscribe_master')" @close="closeModal">
        <div class="p-5 bg-gray-100 dark:bg-gray-600 rounded-lg">
            <div class="flex flex-col items-start gap-3 self-stretch">
                <div>
                    <div v-if="currentLocale === 'en'" class="text-xl">
                        {{ masterAccount.user.username }}
                    </div>
                    <div v-if="currentLocale === 'cn'" class="text-xl">
                        {{ masterAccount.trading_user.company ? masterAccount.trading_user.company : masterAccount.trading_user.name }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('public.account_number') }}
                    </div>
                    <div class="text-base text-gray-800 dark:text-white font-semibold">
                        {{ masterAccount.meta_login }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('public.minimum_equity_to_join') }}
                    </div>
                    <div class="text-base text-gray-800 dark:text-white font-semibold">
                        $ {{ formatAmount(masterAccount.min_join_equity) }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('public.percentage_of_sharing_profit') }}
                    </div>
                    <div class="text-base text-gray-800 dark:text-white font-semibold">
                        {{ formatAmount(masterAccount.sharing_profit, 0) }} %
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('public.estimated_monthly_returns') }}
                    </div>
                    <div class="text-base text-gray-800 dark:text-white font-semibold">
                        {{ masterAccount.estimated_monthly_returns }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('public.estimated_lot_size') }}
                    </div>
                    <div class="text-base text-gray-800 dark:text-white font-semibold">
                        {{ masterAccount.estimated_lot_size }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('public.subscription_fee') }} ({{ masterAccount.roi_period }} {{ $t('public.days') }})
                    </div>
                    <div class="text-base text-gray-800 dark:text-white font-semibold">
                        $ {{ formatAmount(masterAccount.subscription_fee) }}
                    </div>
                </div>
                <div class="flex flex-col gap-2 self-stretch">
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        {{ $t('public.total_fund') }}
                    </div>
                    <div class="mb-1 flex h-2.5 w-full overflow-hidden rounded-full bg-gray-300 dark:bg-gray-400 text-xs">
                        <div
                            :style="{ width: `${masterAccount.totalFundWidth}%` }"
                            class="rounded-full bg-gradient-to-r from-primary-300 to-primary-600 dark:from-primary-500 dark:to-primary-800 transition-all duration-500 ease-out"
                        >
                        </div>
                    </div>
                    <div class="mb-2 flex items-center justify-between text-xs">
                        <div class="dark:text-gray-400">
                            $ 1
                        </div>
                        <div class="dark:text-gray-400">
                            $ {{ formatAmount(masterAccount.total_fund/2) }}
                        </div>
                        <div class="dark:text-gray-400">$ {{ masterAccount.total_fund }}</div>
                    </div>
                </div>
                <div class="flex flex-col gap-2 self-stretch">
                    <div
                        class="text-xs hover:cursor-pointer text-gray-500 hover:text-gray-700 dark:text-gray-400"
                        @click="openTermsModal"
                    >
                        {{ $t('public.terms_and_conditions') }}
                    </div>
                </div>

            </div>
        </div>

        <form class="space-y-2 my-4">
            <div class="space-y-2">
                <Label
                    for="leverage"
                    :value="$t('public.account_number')"
                />
                <div v-if="tradingAccountsSel">
                    <BaseListbox
                        :options="tradingAccountsSel"
                        v-model="form.meta_login"
                        :error="!!form.errors.meta_login"
                        :placeholder="$t('public.placeholder')"
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
                <InputError :message="form.errors.meta_login" />
            </div>

            <div class="pt-5 grid grid-cols-2 gap-4 w-full md:w-1/3 md:float-right">
                <Button variant="transparent" type="button" class="justify-center" @click.prevent="closeModal">
                    {{$t('public.cancel')}}
                </Button>
                <Button class="justify-center" @click="submit" :disabled="form.processing">{{$t('public.confirm')}}</Button>
            </div>
        </form>
    </Modal>

    <Modal :show="termsModal" :title="$t('public.terms_and_conditions')" @close="closeTermsModal">
        <div v-html="terms.contents" class="prose"></div>
    </Modal>
</template>
