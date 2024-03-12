<script setup>
import Button from "@/Components/Button.vue";
import {onMounted, ref} from "vue";
import Modal from "@/Components/Modal.vue";
import {transactionFormat} from "@/Composables/index.js";
import BaseListbox from "@/Components/BaseListbox.vue";
import InputError from "@/Components/InputError.vue";
import Label from "@/Components/Label.vue";
import {useForm} from "@inertiajs/vue3";
import Input from "@/Components/Input.vue";

const props = defineProps({
    masterAccount: Object,
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
</script>

<template>
    <Button
        type="button"
        class="w-full flex justify-center"
        @click="openSubscribeAccountModal"
    >
        Subscribe
    </Button>

    <Modal :show="subscribeAccountModal" title="Subscribe Master" @close="closeModal">
        <div class="p-5 bg-gray-100 dark:bg-gray-600 rounded-lg">
            <div class="flex flex-col items-start gap-3 self-stretch">
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        Account Number
                    </div>
                    <div class="text-base text-gray-800 dark:text-white font-semibold">
                        {{ masterAccount.meta_login }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        Minimum Equity to join
                    </div>
                    <div class="text-base text-gray-800 dark:text-white font-semibold">
                        $ {{ formatAmount(masterAccount.min_join_equity) }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        Percentage of Sharing Profit
                    </div>
                    <div class="text-base text-gray-800 dark:text-white font-semibold">
                        {{ formatAmount(masterAccount.sharing_profit, 0) }} %
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        Estimated Monthly Returns
                    </div>
                    <div class="text-base text-gray-800 dark:text-white font-semibold">
                        {{ formatAmount(masterAccount.estimated_monthly_returns) }} %
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        Estimated Lot Size
                    </div>
                    <div class="text-base text-gray-800 dark:text-white font-semibold">
                        {{ formatAmount(masterAccount.estimated_lot_size) }}
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 self-stretch">
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        Subscription Fee ({{ masterAccount.roi_period }} Days)
                    </div>
                    <div class="text-base text-gray-800 dark:text-white font-semibold">
                        $ {{ formatAmount(masterAccount.subscription_fee) }}
                    </div>
                </div>
                <div class="flex flex-col gap-2 self-stretch">
                    <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                        Total Fund
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

        <form class="space-y-2 my-4">
            <div class="space-y-2">
                <Label
                    for="leverage"
                    :value="$t('public.Account Number')"
                />
                <div v-if="tradingAccountsSel">
                    <BaseListbox
                        :options="tradingAccountsSel"
                        v-model="form.meta_login"
                        :error="!!form.errors.meta_login"
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
                    {{$t('public.Cancel')}}
                </Button>
                <Button class="justify-center" @click="submit" :disabled="form.processing">{{$t('public.Confirm')}}</Button>
            </div>
        </form>
    </Modal>
</template>
