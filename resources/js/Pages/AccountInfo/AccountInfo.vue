<script setup>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import Button from "@/Components/Button.vue";
import {PlusCircleIcon} from "@heroicons/vue/solid";
import {useForm} from "@inertiajs/vue3";
import TradingAccount from "@/Pages/AccountInfo/TradingAccount/TradingAccount.vue";
import InactiveAccount from "@/Pages/AccountInfo/TradingAccount/InactiveAccount.vue";
import Modal from "@/Components/Modal.vue";
import {ref} from "vue";
import Label from "@/Components/Label.vue";
import InputError from "@/Components/InputError.vue";
import BaseListbox from "@/Components/BaseListbox.vue";
import Checkbox from "@/Components/Checkbox.vue";
import {TabGroup, TabList, Tab, TabPanels, TabPanel} from '@headlessui/vue'
import {transactionFormat} from "@/Composables/index.js";
import MasterAccount from "@/Pages/AccountInfo/MasterAccount/MasterAccount.vue";
import {MemberDetailIcon} from "@/Components/Icons/outline.jsx";

const props = defineProps({
    walletSel: Array,
    leverageSel: Array,
    accountCounts: Number,
    masterAccountLogin: Array,
    liveAccountQuota: Object,
    totalEquity: Number,
    totalBalance: Number,
    virtualStatus: Boolean,
})
const {formatAmount} = transactionFormat();
const addingTradingAccount = ref(false)
const totalEquity = ref(null);
const totalBalance = ref(null);
const activeTab = ref('trading_account')

const addTradingAccount = () => {
    addingTradingAccount.value = true
}

const form = useForm({
    leverage: 500,
    terms: '',
    type: 1
})

const handleTabChange = (tab) => {
    activeTab.value = tab
    form.type = tab === 'trading_account' ? 1 : 2
}

const submit = () => {
    console.log('Form data:', form.data())

    form.post(route('account_info.add_trading_account'), {
        onSuccess: () => {
            closeModal()
            form.reset()
        },
        onError: (errors) => {
            console.error('Submission errors:', errors)
        }
    })
}

const closeModal = () => {
    addingTradingAccount.value = false
}

const refreshData = async () => {
    const response = await axios.get('/account_info/refreshTradingAccountsData');
    totalEquity.value = response.data.totalEquity;
    totalBalance.value = response.data.totalBalance;
};

refreshData();

setInterval(refreshData, 10000);

</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar.account_info')">
        <template #header>
            <div class="flex flex-col gap-2 md:flex-row md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    {{ $t('public.sidebar.account_info') }}
                </h2>
                <Button
                    type="button"
                    variant="gray"
                    v-slot="{ iconSizeClasses }"
                    class="flex gap-2 items-center justify-center"
                    size="sm"
                    external
                    :href="route('account_info.transaction_listing')"
                >
                    <MemberDetailIcon aria-hidden="true" :class="iconSizeClasses"/>
                    <span>{{ $t('public.transaction_history') }}</span>
                </Button>
            </div>
        </template>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">

            <fieldset
                class="border-2 border-primary-500 p-4 rounded-lg shadow-md text-center bg-gradient-to-b from-transparent to-primary-300"
            >
                <legend class="text-lg px-4 uppercase font-semibold">{{ $t('public.total_equity') }}</legend>
                <div class="text-xl font-medium sm:text-3xl">
                        <span v-if="totalEquity !== null">
                            $ {{ formatAmount(totalEquity) }}
                        </span>
                    <span v-else>
                            {{ $t('public.loading') }}
                        </span>
                </div>
            </fieldset>

            <fieldset
                class="border-2 border-purple-500 p-4 rounded-lg shadow-md text-center bg-gradient-to-b from-transparent to-purple-300"
            >
                <legend class="text-lg px-4 uppercase font-semibold">{{ $t('public.total_balance') }}</legend>
                <div class="text-xl font-medium sm:text-3xl">
                        <span v-if="totalBalance !== null">
                            $ {{ formatAmount(totalBalance) }}
                        </span>
                    <span v-else>
                            {{ $t('public.loading') }}
                        </span>
                </div>
            </fieldset>

        </div>

        <div class="w-full">
            <TabGroup>
                <div class="w-full flex flex-col sm:flex-row gap-4 sm:justify-between items-center">

                    <TabList v-if="accountCounts > 0"
                             class="flex space-x-1 rounded-xl bg-blue-900/20 dark:bg-gray-800 p-1 w-full max-w-md">
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
                                {{ $t('public.trading_accounts') }}
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
                                {{ $t('public.master_accounts') }}
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
                                {{ $t('public.inactive_accounts') }}
                            </button>
                        </Tab>
                    </TabList>
                    <Button
                        type="button"
                        variant="primary"
                        size="sm"
                        class="flex justify-center items-center gap-2 w-full sm:w-auto"
                        v-slot="{ iconSizeClasses }"
                        @click="addTradingAccount"
                        v-if="props.accountCounts < props.liveAccountQuota.value || props.virtualStatus"
                    >
                        <PlusCircleIcon aria-hidden="true" :class="iconSizeClasses"/>
                        <span>{{ $t('public.add_account') }}</span>
                    </Button>
                </div>
                <TabPanels v-if="accountCounts > 0" class="mt-2">
                    <TabPanel
                        class="py-3"
                    >
                        <TradingAccount
                            :walletSel="walletSel"
                            :leverageSel="leverageSel"
                            :accountCounts="accountCounts"
                            :masterAccountLogin="masterAccountLogin"
                        />
                    </TabPanel>

                    <TabPanel
                        class="py-3"
                    >
                        <MasterAccount
                            :walletSel="walletSel"
                            :leverageSel="leverageSel"
                            :accountCounts="accountCounts"
                            :masterAccountLogin="masterAccountLogin"
                        />
                    </TabPanel>

                    <TabPanel
                        class="py-3"
                    >
                        <InactiveAccount
                            :walletSel="walletSel"
                            :leverageSel="leverageSel"
                            :accountCounts="accountCounts"
                            :masterAccountLogin="masterAccountLogin"
                        />
                    </TabPanel>
                </TabPanels>

                <div
                    v-else
                    class="flex flex-col items-center w-full"
                >
                    <div class="text-2xl text-gray-400 dark:text-gray-200">
                        {{ $t('public.no_account') }}
                    </div>
                    <div class="text-lg text-gray-400 dark:text-gray-600">
                        {{ $t('public.no_account_message') }}
                    </div>
                </div>
            </TabGroup>
        </div>

        <Modal :show="addingTradingAccount" :title="$t('public.add_account')" @close="closeModal">
            <form class="space-y-4">
                <TabGroup>
                    <TabList class="flex space-x-1 rounded-xl bg-blue-900/20 dark:bg-gray-800 p-1 w-full">
                        <Tab
                            as="template"
                            v-slot="{ selected }"
                            v-if="props.accountCounts < props.liveAccountQuota.value"
                        >
                            <button
                                :class="[
                                    'w-full rounded-lg py-2.5 text-sm font-medium leading-5',
                                    'ring-white/60 ring-offset-2 ring-offset-primary-200 focus:outline-none focus:ring-2',
                                    selected
                                    ? 'bg-white text-primary-800 shadow'
                                    : 'text-blue-25 hover:bg-white/[0.12] hover:text-white',
                                ]"
                                @click="handleTabChange('trading_account')"
                            >
                                {{ $t('public.trading_account') }}
                            </button>
                        </Tab>

                        <Tab
                            as="template"
                            v-slot="{ selected }"
                            v-if="props.virtualStatus"
                        >
                            <button
                                :class="[
                                    'w-full rounded-lg py-2.5 text-sm font-medium leading-5',
                                    'ring-white/60 ring-offset-2 ring-offset-primary-200 focus:outline-none focus:ring-2',
                                    selected
                                    ? 'bg-white text-primary-800 shadow'
                                    : 'text-blue-25 hover:bg-white/[0.12] hover:text-white',
                                ]"
                                @click="handleTabChange('virtual_account')"
                            >
                                {{ $t('public.virtual_account') }}
                            </button>
                        </Tab>
                    </TabList>
                    <TabPanels>
                        <TabPanel class="py-3" v-if="props.accountCounts < props.liveAccountQuota.value">
                            <div class="space-y-2">
                                <Label
                                    for="leverage"
                                    :value="$t('public.leverage')"
                                />
                                <BaseListbox
                                    :options="leverageSel"
                                    v-model="form.leverage"
                                />
                                <InputError :message="form.errors.leverage"/>
                            </div>
                            <div class="mt-6 space-y-4">
                                <h3 class="text-gray-400 dark:text-gray-300 font-bold text-sm">{{
                                        $t('public.terms_and_conditions')
                                    }}</h3>
                                <ol class="text-gray-500 dark:text-gray-400 text-xs list-decimal text-justify pl-6 mt-2">
                                    <li>{{ $t('public.terms_1') }}</li>
                                    <li>{{ $t('public.terms_2') }}</li>
                                    <li>{{ $t('public.terms_3') }}</li>
                                    <li>{{ $t('public.terms_4') }}</li>
                                    <li>{{ $t('public.terms_5') }}</li>
                                    <li>{{ $t('public.terms_6') }}</li>
                                    <li>{{ $t('public.terms_7') }}</li>
                                    <li>{{ $t('public.terms_8') }}</li>
                                    <li>{{ $t('public.terms_9') }}</li>
                                </ol>

                                <div class="flex items-center">
                                    <div class="flex items-center h-5">
                                        <Checkbox id="terms" v-model="form.terms"/>
                                    </div>
                                    <div class="ml-3">
                                        <label for="terms" class="text-gray-500 dark:text-gray-400 text-xs">{{
                                                $t('public.accept_terms')
                                            }}</label>
                                    </div>
                                </div>
                                <InputError :message="form.errors.terms"/>

                            </div>
                        </TabPanel>
                        
                        <TabPanel class="py-3" v-if="props.virtualStatus">
                            <div class="space-y-2">
                                <Label
                                    for="leverage"
                                    :value="$t('public.leverage')"
                                />
                                <BaseListbox
                                    :options="leverageSel"
                                    v-model="form.leverage"
                                />
                                <InputError :message="form.errors.leverage"/>
                            </div>
                            <div class="mt-6 space-y-4">
                                <h3 class="text-gray-400 dark:text-gray-300 font-bold text-sm">{{
                                        $t('public.terms_and_conditions')
                                    }}</h3>
                                <ol class="text-gray-500 dark:text-gray-400 text-xs list-decimal text-justify pl-6 mt-2">
                                    <li>{{ $t('public.terms_1') }}</li>
                                    <li>{{ $t('public.terms_2') }}</li>
                                    <li>{{ $t('public.terms_3') }}</li>
                                    <li>{{ $t('public.terms_4') }}</li>
                                    <li>{{ $t('public.terms_5') }}</li>
                                    <li>{{ $t('public.terms_6') }}</li>
                                    <li>{{ $t('public.terms_7') }}</li>
                                    <li>{{ $t('public.terms_9') }}</li>
                                </ol>

                                <div class="flex items-center">
                                    <div class="flex items-center h-5">
                                        <Checkbox id="terms" v-model="form.terms"/>
                                    </div>
                                    <div class="ml-3">
                                        <label for="terms" class="text-gray-500 dark:text-gray-400 text-xs">{{
                                                $t('public.accept_terms')
                                            }}</label>
                                    </div>
                                </div>
                                <InputError :message="form.errors.terms"/>

                            </div>
                        </TabPanel>
                    </TabPanels>
                </TabGroup>
                <div class="mt-6 flex justify-end">
                    <Button
                        type="button"
                        variant="primary-transparent"
                        @click="closeModal">
                        {{ $t('public.cancel') }}
                    </Button>

                    <Button
                        variant="primary"
                        class="ml-3"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="submit"
                    >
                        {{ $t('public.process') }}
                    </Button>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
