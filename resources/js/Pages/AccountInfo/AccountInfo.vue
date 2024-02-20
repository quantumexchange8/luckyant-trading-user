<script setup>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import Button from "@/Components/Button.vue";
import {PlusCircleIcon} from "@heroicons/vue/solid";
import {useForm, usePage} from "@inertiajs/vue3";
import TradingAccount from "@/Pages/AccountInfo/TradingAccount/TradingAccount.vue";
import Modal from "@/Components/Modal.vue";
import {ref} from "vue";
import Label from "@/Components/Label.vue";
import Input from "@/Components/Input.vue";
import InputError from "@/Components/InputError.vue";
import BaseListbox from "@/Components/BaseListbox.vue";
import Checkbox from "@/Components/Checkbox.vue";
import { TabGroup, TabList, Tab, TabPanels, TabPanel } from '@headlessui/vue'
import MasterAccount from "@/Pages/AccountInfo/MasterAccount/MasterAccount.vue";

const props = defineProps({
    walletSel: Array,
    accountCounts: Number,
    masterAccountLogin: Array,
})
const user = usePage().props.auth.user;
const addingTradingAccount = ref(false)

const addTradingAccount = () => {
    addingTradingAccount.value = true
}

const form = useForm({
    leverage: 500,
    terms: '',
})

const submit = () => {
    form.post(route('account_info.add_trading_account'), {
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    })
}

const closeModal = () => {
    addingTradingAccount.value = false
}

const leverages = [
    { label: '1:1', value: 1 },
    { label: '1:10', value: 10 },
    { label: '1:20', value: 20 },
    { label: '1:50', value: 50 },
    { label: '1:100', value: 100 },
    { label: '1:200', value: 200 },
    { label: '1:300', value: 300 },
    { label: '1:400', value: 400 },
    { label: '1:500', value: 500 },
]

</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar.Account Info')">
        <template #header>
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    {{ $t('public.sidebar.Account Info') }}
                </h2>

                <Button
                    v-if="user.kyc_approval === 'Verified'"
                    type="button"
                    variant="primary"
                    class="items-center gap-2 max-w-xs"
                    v-slot="{ iconSizeClasses }"
                    @click="addTradingAccount"
                >
                    <PlusCircleIcon aria-hidden="true" :class="iconSizeClasses" />
                    <span>{{ $t('public.Add Trading Account') }}</span>
                </Button>
            </div>
        </template>

        <div v-if="accountCounts > 0">
            <div class="w-full">
                <TabGroup>
                    <TabList class="flex space-x-1 rounded-xl bg-blue-900/20 dark:bg-gray-800 p-1 max-w-md">
                        <Tab
                            as="template"
                            v-slot="{ selected }"
                        >
                            <button
                                :class="[
                                    'w-full rounded-lg py-2.5 text-sm font-medium leading-5',
                                     'ring-white/60 ring-offset-2 ring-offset-primary-200 focus:outline-none focus:ring-2',
                                     selected
                                     ? 'bg-white text-primary-800 shadow'
                                     : 'text-blue-25 hover:bg-white/[0.12] hover:text-white',
                                ]"
                            >
                                Trading Accounts
                            </button>
                        </Tab>

                        <Tab
                            as="template"
                            v-slot="{ selected }"
                        >
                            <button
                                :class="[
                                    'w-full rounded-lg py-2.5 text-sm font-medium leading-5',
                                     'ring-white/60 ring-offset-2 ring-offset-primary-200 focus:outline-none focus:ring-2',
                                     selected
                                     ? 'bg-white text-primary-800 shadow'
                                     : 'text-blue-25 hover:bg-white/[0.12] hover:text-white',
                                ]"
                            >
                                Master Accounts
                            </button>
                        </Tab>
                    </TabList>

                    <TabPanels class="mt-2">
                        <TabPanel
                            class="py-3"
                        >
                            <TradingAccount
                                :walletSel="walletSel"
                                :accountCounts="accountCounts"
                                :masterAccountLogin="masterAccountLogin"
                            />
                        </TabPanel>

                        <TabPanel
                            class="py-3"
                        >
                            <MasterAccount
                                :walletSel="walletSel"
                                :accountCounts="accountCounts"
                                :masterAccountLogin="masterAccountLogin"
                            />
                        </TabPanel>
                    </TabPanels>
                </TabGroup>
            </div>
        </div>
        <div
            v-else
            class="flex flex-col items-center w-full"
        >
            <div class="text-2xl text-gray-400 dark:text-gray-200">
                {{ $t('public.No Account') }}
            </div>
            <div class="text-lg text-gray-400 dark:text-gray-600">
                We will notify you once your KYC verification is completed, enabling you to add an account.
            </div>
        </div>

        <Modal :show="addingTradingAccount" :title="$t('public.Add Trading Account')" @close="closeModal">
            <form class="space-y-4">
                <div class="space-y-2">
                    <Label
                        for="leverage"
                        :value="$t('public.Leverage')"
                    />
                    <BaseListbox
                        :options="leverages"
                        v-model="form.leverage"
                    />
                    <InputError :message="form.errors.leverage" />
                </div>
                <div class="mt-6 space-y-4">
                    <h3 class="text-gray-400 dark:text-gray-300 font-bold text-sm">{{ $t('public.Terms & Conditions') }}</h3>
                    <ol class="text-gray-500 dark:text-gray-400 text-xs list-decimal text-justify pl-6 mt-2">
                        <li>{{ $t('public.Terms 1') }}</li>
                        <li>{{ $t('public.Terms 2') }}</li>
                        <li>{{ $t('public.Terms 3') }}</li>
                        <li>{{ $t('public.Terms 4') }}</li>
                        <li>{{ $t('public.Terms 5') }}</li>
                        <li>{{ $t('public.Terms 6') }}</li>
                        <li>{{ $t('public.Terms 7') }}</li>
                        <li>{{ $t('public.Terms 8') }}</li>
                    </ol>

                    <div class="flex items-center">
                        <div class="flex items-center h-5">
                            <Checkbox v-model="form.terms"/>
                        </div>
                        <div class="ml-3">
                            <label for="terms" class="text-gray-500 dark:text-gray-400 text-xs">{{ $t('public.I acknowledge that I have read, and do hereby accept the terms and conditions stated as above.') }}</label>
                        </div>
                    </div>
                    <InputError :message="form.errors.terms"/>

                    <div class="mt-6 flex justify-end">
                        <Button
                            type="button"
                            variant="transparent"
                            @click="closeModal">
                            {{ $t('public.Cancel') }}
                        </Button>

                        <Button
                            variant="primary"
                            class="ml-3"
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                            @click="submit"
                        >
                            {{ $t('public.Process') }}
                        </Button>
                    </div>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
