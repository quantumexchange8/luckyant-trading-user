<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import AffiliateSubscriptionsOverview from "@/Pages/Referral/AffiliateSubscriptions/AffiliateSubscriptionsOverview.vue";
import {h, ref, watch} from "vue";
import TabPanel from "primevue/tabpanel";
import TabPanels from "primevue/tabpanels";
import Tabs from "primevue/tabs";
import Tab from "primevue/tab";
import TabList from "primevue/tablist";
import AffiliateCopyTrade from "@/Pages/Referral/AffiliateSubscriptions/AffiliateCopyTrade.vue";
import AffiliatePamm from "@/Pages/Referral/AffiliateSubscriptions/AffiliatePamm.vue";

const totalAffiliate = ref(null);
const totalDeposit = ref(null);

const handleOverview = (data) => {
    totalAffiliate.value = data.totalAffiliate;
    totalDeposit.value = data.totalDeposit;
}

const tabs = ref([
    {
        title: 'copy_trading',
        value: '0',
        type: 'copy_trading',
        component: h(AffiliateCopyTrade, {
            type: 'CopyTrade',
        })
    },
    {
        title: 'pamm_trading',
        value: '1',
        type: 'pamm_trading',
        component: h(AffiliatePamm, {
            pammType: 'StandardGroup',
        })
    },
    {
        title: 'esg_investment_portfolio',
        value: '2',
        type: 'esg',
        component: h(AffiliatePamm, {
            pammType: 'ESG',
        })
    },
]);

const selectedType = ref('copy_trading');
const activeIndex = ref(tabs.value.find(tab => tab.type === selectedType.value)?.value);

// Watch for changes in selectedType and update the activeIndex accordingly
watch(selectedType, (newType) => {
    const index = tabs.value.findIndex(tab => tab.type === newType);
    if (index >= 0) {
        activeIndex.value = index;
    }
});
</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar.affiliate_subscriptions')">
        <template #header>
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    {{ $t('public.sidebar.affiliate_subscriptions') }}
                </h2>
            </div>
        </template>

        <div class="flex flex-col gap-5 items-center self-stretch">
            <AffiliateSubscriptionsOverview
                :totalAffiliate="totalAffiliate"
                :totalDeposit="totalDeposit"
            />

            <!-- Tabs -->
            <Tabs v-model:value="activeIndex" class="w-full">
                <TabList>
                    <Tab v-for="tab in tabs" :key="tab.title" :value="tab.value">
                        <div class="flex items-center gap-2">
                            <span>{{ $t(`public.${tab.title}`) }}</span>
                        </div>
                    </Tab>
                </TabList>
                <TabPanels>
                    <TabPanel v-for="tab in tabs" :value="tab.value">
                        <component
                            :is="tabs[activeIndex]?.component" @update-totals="handleOverview"
                        />
                    </TabPanel>
                </TabPanels>
            </Tabs>
        </div>
    </AuthenticatedLayout>
</template>
