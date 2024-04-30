<script setup>
import {ref, watch} from "vue";
import Modal from "@/Components/Modal.vue";
import {useForm} from "@inertiajs/vue3";
import {RadioGroup, RadioGroupLabel, RadioGroupOption} from "@headlessui/vue";
import Label from "@/Components/Label.vue";
import Input from "@/Components/Input.vue";
import InputError from "@/Components/InputError.vue";
import BaseListbox from "@/Components/BaseListbox.vue";
import Button from "@/Components/Button.vue";

const props = defineProps({
    countries: Array,
    currencies: Array,
})

const addAccountModal = ref(false);

const openAddAccountModal = () => {
    addAccountModal.value = true;
}

const paymentTypes = [
    {
        name: 'bank',
        value: 'Bank',
    },
    {
        name: 'crypto',
        value: 'Crypto',
    },
]

const selected = ref(paymentTypes[0]);
const cryptoWallet = ref('USDT (TRC20)');
const country = ref(132);
const currency = ref('MYR');

const form = useForm({
    payment_method: '',
    payment_account_name: '',
    payment_platform_name: '',
    account_no: '',
    country: 132,
    currency: 'MYR',
    bank_swift_code: '',
    bank_code: '',
    bank_code_type: '',
});

watch((selected), (newSelect) => {
    if (newSelect && newSelect.value === selected.value.value)
    {
        form.payment_method = newSelect.value;
        form.payment_account_name = '';
        form.payment_platform_name = '';
    }
});

watch(country, (newValue) => {
    if (newValue !== 132) {
        currency.value = 'USD';
    }
    else if (newValue == 132) {
        currency.value = 'MYR';
    }

});

const submit = () => {
    form.payment_method = selected.value.value;
    if (form.payment_method === 'Crypto') {
        form.payment_platform_name = cryptoWallet.value
    }

    form.post(route('profile.addPaymentAccount'), {
        onSuccess: () => {
            closeModal();
        },
    })
}

const closeModal = () => {
    addAccountModal.value = false;
}
</script>

<template>
    <div
        class="card text-gray-100 w-full hover:brightness-90 transition-all cursor-pointer group bg-gradient-to-tl from-primary-900 to-primary-950 hover:from-primary-800 hover:to-primary-950 border-r-2 border-t-2 border-primary-900 rounded-lg overflow-hidden relative"
        @click="openAddAccountModal"
    >
        <div class="px-8 py-5">
            <div class="bg-blue-500 w-10 h-10 rounded-full rounded-tl-none group-hover:-translate-y-1 group-hover:shadow-xl group-hover:shadow-blue-900 transition-all"></div>

            <div class="uppercase font-bold text-xl text-center my-8">
                {{ $t('public.click_add_account') }}
            </div>

            <div class="text-gray-400">
                <p class="font-bold">{{ $t('public.currency') }}</p>
                <p>{{ $t('public.account_no') }}</p>
            </div>
        </div>


        <div class="h-2 w-full bg-gradient-to-l via-sky-500 group-hover:blur-xl blur-2xl m-auto rounded transition-all absolute bottom-0"></div>
        <div class="h-0.5 group-hover:w-full bg-gradient-to-l via-sky-600 dark:via-sky-950 group-hover:via-sky-500 w-[70%] m-auto rounded transition-all"></div>
    </div>

    <Modal :show="addAccountModal" :title="$t('public.add_account')" @close="closeModal">
        <form class="space-y-4">
            <div class="space-y-2">
                <Label
                    for="leverage"
                    :value="$t('public.payment_methods')"
                />
                <RadioGroup v-model="selected">
                    <RadioGroupLabel class="sr-only">{{ $t('public.signal_status') }}</RadioGroupLabel>
                    <div class="flex gap-3 items-center self-stretch w-full">
                        <RadioGroupOption
                            as="template"
                            v-for="(plan, index) in paymentTypes"
                            :key="index"
                            :value="plan"
                            v-slot="{ active, checked }"
                        >
                            <div
                                :class="[
                                        active
                                            ? 'ring-0 ring-white ring-offset-0'
                                            : '',
                                        checked ? 'border-primary-600 dark:border-white bg-primary-500 dark:bg-gray-600 text-white' : 'border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 dark:text-white',
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
                                                {{ $t('public.' + plan.name) }}
                                            </div>
                                        </RadioGroupLabel>
                                    </div>
                                </div>
                            </div>
                        </RadioGroupOption>
                    </div>
                </RadioGroup>
                <InputError :message="form.errors.payment_method" />
            </div>

            <div v-if="selected.name === 'bank'" class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                <!-- <BankSetting/> -->

                <div class="space-y-2">
                    <Label
                        for="bank_name"
                        :value="$t('public.bank_name')"
                    />
                    <Input
                        id="bank_name"
                        type="text"
                        class="block w-full"
                        v-model="form.payment_platform_name"
                        :invalid="form.errors.payment_platform_name"
                    />
                    <InputError :message="form.errors.payment_platform_name" />
                </div>
                <div class="space-y-2">
                    <Label
                        for="bank_account_name"
                        :value="$t('public.bank_account_name')"
                    />
                    <Input
                        id="bank_account_name"
                        type="text"
                        class="block w-full"
                        v-model="form.payment_account_name"
                        :invalid="form.errors.payment_account_name"
                    />
                    <InputError :message="form.errors.payment_account_name" />
                </div>
                <div class="space-y-2">
                    <Label
                        for="account_number"
                        :value="$t('public.account_number')"
                    />
                    <Input
                        id="account_number"
                        type="text"
                        class="block w-full"
                        v-model="form.account_no"
                        :invalid="form.errors.account_no"
                    />
                    <InputError :message="form.errors.account_no" />
                </div>
                <div class="space-y-2">
                    <Label
                        for="bank_swift"
                        :value="$t('public.bank_swift_code')"
                    />
                    <Input
                        id="bank_swift"
                        type="text"
                        class="block w-full"
                        v-model="form.bank_swift_code"
                        :invalid="form.errors.bank_swift_code"
                    />
                    <InputError :message="form.errors.bank_swift_code" />
                </div>
                <div class="space-y-2">
                    <Label
                        for="bank_code"
                        :value="$t('public.bank_code')"
                    />
                    <Input
                        id="bank_code"
                        type="text"
                        class="block w-full"
                        :placeholder="$t('public.optional')"
                        v-model="form.bank_code"
                        :invalid="form.errors.bank_code"
                    />
                    <InputError :message="form.errors.bank_code" />
                </div>
                <div class="space-y-2">
                    <Label
                        for="bank_code_type"
                        :value="$t('public.bank_code_type')"
                    />
                    <Input
                        id="bank_code_type"
                        type="text"
                        class="block w-full"
                        :placeholder="$t('public.optional')"
                        v-model="form.bank_code_type"
                        :invalid="form.errors.bank_code_type"
                    />
                </div>
                <div class="space-y-2">
                    <Label
                        for="country"
                        :value="$t('public.country')"
                    />
                    <BaseListbox
                        :options="countries"
                        v-model="country"
                        :error="!!form.errors.country"
                    />
                </div>
                <div class="space-y-2">
                    <Label
                        for="currency"
                        :value="$t('public.currency')"
                    />
                    <BaseListbox
                        :options="currencies"
                        v-model="currency"
                        :error="!!form.errors.currency"
                    />
                </div>
            </div>

            <div v-else-if="selected.name === 'crypto'" class="space-y-2">
                <!-- <CryptoSetting/> -->
                <div class="space-y-2">
                    <Label
                        for="crypto_name"
                        :value="$t('public.payment_service')"
                    />
                    <Input
                        id="crypto_name"
                        type="text"
                        class="block w-full"
                        v-model="cryptoWallet"
                        readonly
                        :invalid="form.errors.payment_platform_name"
                    />
                    <InputError :message="form.errors.payment_platform_name" />
                </div>
                <div class="space-y-2">
                    <Label
                        for="crypto_account_name"
                        :value="$t('public.crypto_wallet_name')"
                    />
                    <Input
                        id="crypto_account_name"
                        type="text"
                        class="block w-full"
                        v-model="form.payment_account_name"
                        :invalid="form.errors.payment_account_name"
                    />
                    <InputError :message="form.errors.payment_account_name" />
                </div>
                <div class="space-y-2">
                    <Label
                        for="account_number"
                        :value="$t('public.wallet_address')"
                    />
                    <Input
                        id="account_number"
                        type="text"
                        min="0"
                        class="block w-full"
                        v-model="form.account_no"
                        :invalid="form.errors.account_no"
                    />
                    <InputError :message="form.errors.account_no" />
                </div>
            </div>

            <div class="pt-5 flex justify-end">
                <Button
                    class="flex justify-center"
                    @click="submit"
                    :disabled="form.processing"
                >
                    {{ $t('public.save') }}
                </Button>
            </div>
        </form>
    </Modal>
</template>
