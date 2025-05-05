<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import PerformanceOverview from "@/Pages/Report/PerformanceIncentive/PerformanceOverview.vue";
import PerformanceTable from "@/Pages/Report/PerformanceIncentive/PerformanceTable.vue";
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import {ref, watch} from "vue";
import WalletHistoryOverview from "@/Pages/Wallet/WalletHistoryOverview.vue";

const totalPerformanceIncentive = ref(null);
const totalAffiliateAmount = ref(null);
const totalPersonalAmount = ref(null);

const tabs = ref([
    {
        type: 'all',
        value: '0',
    },
    {
        type: 'affiliate',
        value: '1',
    },
    {
        type: 'personal',
        value: '2',
    },
]);

const selectedType = ref('all_trades');
const activeIndex = ref('0');

// Watch for changes in selectedType and update the activeIndex accordingly
watch(activeIndex, (newIndex) => {
    const activeTab = tabs.value.find(tab => tab.value === newIndex);
    if (activeTab) {
        selectedType.value = activeTab.type;
    }
});

const handleUpdateTotals = (data) => {
    totalPerformanceIncentive.value = data.totalPerformanceIncentive;
    totalAffiliateAmount.value = data.totalAffiliateAmount;
    totalPersonalAmount.value = data.totalPersonalAmount;
};
</script>

<template>
    <AuthenticatedLayout :title="$t('public.performance_incentive')">
        <template #header>
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    {{ $t('public.performance_incentive') }}
                </h2>
            </div>
        </template>

        <div class="flex flex-col gap-5 items-center self-stretch">
            <PerformanceOverview
                :totalPerformanceIncentive="totalPerformanceIncentive"
                :totalAffiliateAmount="totalAffiliateAmount"
                :totalPersonalAmount="totalPersonalAmount"
            />

            <Tabs v-model:value="activeIndex" class="w-full">
                <TabList>
                    <Tab
                        v-for="tab in tabs"
                        :key="tab.type"
                        :value="tab.value"
                    >
                        {{ $t(`public.${tab.type}`) }}
                    </Tab>
                </TabList>
            </Tabs>

            <PerformanceTable
                :selectedType="selectedType"
                @update-totals="handleUpdateTotals"
            />
        </div>
    </AuthenticatedLayout>
</template>
