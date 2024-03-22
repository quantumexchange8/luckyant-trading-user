<script setup>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue'
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue'
import DeleteUserForm from './Partials/DeleteUserForm.vue'
import {Tab, TabGroup, TabList, TabPanel, TabPanels} from "@headlessui/vue";
import Button from "@/Components/Button.vue";
import PaymentAccount from "@/Pages/Profile/PaymentAccount/PaymentAccount.vue";
import {onMounted, ref} from "vue";

defineProps({
    mustVerifyEmail: Boolean,
    status: String,
    frontIdentityImg: String,
    backIdentityImg: String,
    profileImg: String,
    nationalities: Array,
    paymentAccounts: Object,
    countries: Array,
    currencies: Array,
})

const selectedTab = ref(0);
function changeTab(index) {
    selectedTab.value = index;
}

onMounted(() => {
    const params = new Proxy(new URLSearchParams(window.location.search), {
        get: (searchParams, prop) => searchParams.get(prop),
    });
    if (params.status === 'paymentAccount'){
        selectedTab.value = 1;
    }
});
</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar.profile')">
        <template #header>
            <h2
                class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight"
            >
                {{ $t('public.sidebar.profile') }}
            </h2>
        </template>

        <div class="w-full">
            <TabGroup :selectedIndex="selectedTab" @change="changeTab">
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
                            {{ $t('public.profile_information') }}
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
                            {{ $t('public.payment_accounts') }}
                        </button>
                    </Tab>
                </TabList>

                <TabPanels class="mt-2">
                    <TabPanel
                        class="py-3"
                    >
                        <div class="space-y-6">
                            <div
                                class="p-4 sm:p-8 bg-white dark:bg-gray-900 shadow sm:rounded-lg"
                            >
                                <UpdateProfileInformationForm
                                    :must-verify-email="mustVerifyEmail"
                                    :status="status"
                                    :frontIdentityImg="frontIdentityImg"
                                    :backIdentityImg="backIdentityImg"
                                    :profileImg="profileImg"
                                    :nationalities="nationalities"
                                    :countries="countries"
                                />
                            </div>

                            <div
                                class="p-4 sm:p-8 bg-white dark:bg-gray-900 shadow sm:rounded-lg"
                            >
                                <UpdatePasswordForm class="max-w-xl" />
                            </div>
                        </div>
                    </TabPanel>

                    <TabPanel
                        class="py-3"
                    >
                        <PaymentAccount
                            :paymentAccounts="paymentAccounts"
                            :countries="countries"
                            :currencies="currencies"
                        />
                    </TabPanel>
                </TabPanels>
            </TabGroup>
        </div>
    </AuthenticatedLayout>
</template>
