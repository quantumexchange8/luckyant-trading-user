<script setup>
import Button from "@/Components/Button.vue";
import {ref, watchEffect} from "vue";
import Modal from "@/Components/Modal.vue";
import {useForm, usePage} from "@inertiajs/vue3";
import InputError from "@/Components/InputError.vue";
import BaseListbox from "@/Components/BaseListbox.vue";
import Input from "@/Components/Input.vue";
import Label from "@/Components/Label.vue";
import {transactionFormat} from "@/Composables/index.js";
import Checkbox from "@/Components/Checkbox.vue";

const props = defineProps({
    masterAccount: Object,
    terms: Object,
})

const joinPammModal = ref(false);
const tradingAccountsSel = ref(null);
const { formatAmount } = transactionFormat();
const currentLocale = ref(usePage().props.locale);

const openJoinPammModal = () => {
    joinPammModal.value = true;
}

const closeModal = () => {
    joinPammModal.value = false
}

const form = useForm({
    master_id: props.masterAccount.id,
    meta_login: '',
    terms: '',
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

const termsModal = ref(false);

const openTermsModal = () => {
    termsModal.value = true
}

const closeTermsModal = () => {
    termsModal.value = false
}

watchEffect(() => {
    if (usePage().props.title !== null) {
        getTradingAccounts();
    }
});
</script>

<template>
    <Button
        type="button"
        class="w-full flex justify-center"
        @click="openJoinPammModal"
    >
        {{ $t('public.join_pamm') }}
    </Button>

    <Modal :show="joinPammModal" :title="$t('public.join_pamm')" @close="closeModal">
        <form class="flex flex-col w-full gap-2">
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
                        :value="$t('public.loading')"
                        readonly
                    />
                </div>
                <InputError :message="form.errors.meta_login" />
            </div>
            <div class="p-5 bg-gray-100 dark:bg-gray-800 rounded-lg">
                <div class="flex flex-col items-start gap-3 self-stretch">
                    <div>
                        <div v-if="currentLocale === 'en'" class="text-xl dark:text-white">
                            {{ masterAccount.trading_user.name }}
                        </div>
                        <div v-if="currentLocale === 'cn'" class="text-xl dark:text-white">
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
                            {{ $t('public.roi_period') }}
                        </div>
                        <div class="text-base text-gray-800 dark:text-white font-semibold">
                            {{ masterAccount.roi_period }} {{ $t('public.days') }}
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-2 self-stretch">
                        <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                            {{ $t('public.max_drawdown') }}
                        </div>
                        <div class="text-base text-gray-800 dark:text-white font-semibold">
                            {{ masterAccount.max_drawdown }}
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
                </div>
            </div>

            <div class="flex items-center">
                <div class="flex items-center h-5">
                    <Checkbox id="terms" v-model="form.terms"/>
                </div>
                <div class="ml-3">
                    <label for="terms" class="flex gap-1 text-gray-500 dark:text-gray-400 text-xs">
                        {{ $t('public.agreement') }}
                        <div
                            class="text-xs underline hover:cursor-pointer text-primary-500 hover:text-gray-700 dark:text-primary-600 dark:hover:text-primary-400"
                            @click="openTermsModal"
                        >
                            {{ $t('public.terms_and_conditions') }}
                        </div>
                    </label>
                </div>
            </div>
            <InputError :message="form.errors.terms" />
        </form>
        <div class="pt-5 grid grid-cols-2 gap-4 w-full md:w-1/3 md:float-right">
            <Button variant="transparent" type="button" class="justify-center" @click.prevent="closeModal">
                {{$t('public.cancel')}}
            </Button>
            <Button type="button" class="justify-center" @click="submit" :disabled="form.processing">{{$t('public.confirm')}}</Button>
        </div>
    </Modal>

    <Modal :show="termsModal" :title="$t('public.terms_and_conditions')" @close="closeTermsModal">
        <div v-html="terms.contents" class="prose dark:text-white"></div>
        <div class="pt-4">
            <div class="text-gray-600 dark:text-gray-400">
                {{ $t('public.management_fee') }}
            </div>
            <div
                v-for="management_fee in masterAccount.master_management_fee"
                class="text-sm font-semibold dark:text-white"
            >
                {{ management_fee.penalty_days }} {{ $t('public.days') }} - {{ formatAmount(management_fee.penalty_percentage, 0) }} %
            </div>
        </div>
    </Modal>
</template>
