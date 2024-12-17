<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import Tabs from 'primevue/tabs';
import TabList from 'primevue/tablist';
import Tab from 'primevue/tab';
import TabPanels from 'primevue/tabpanels';
import TabPanel from 'primevue/tabpanel';
import Badge from 'primevue/badge';
import {h, ref} from "vue";
import CopyTradeSubscriptions from "@/Pages/CopyTrading/CopyTradeSubscriptions.vue";
import PammSubscriptions from "@/Pages/Pamm/PammSubscriptions.vue";

const props = defineProps({
    strategyType: String,
    copyTradesCount: Number,
    pammsCount: Number,
    esgsCount: Number,
    walletSel: Array,
});

const tabs = ref([
    {
        title: 'copy_trading',
        value: '0',
        count: props.copyTradesCount,
        component: h(CopyTradeSubscriptions, {
            strategyType: props.strategyType,
            copyTradesCount: props.copyTradesCount
        })
    },
    {
        title: 'pamm_trading',
        value: '1',
        count: props.pammsCount,
        component: h(PammSubscriptions, {
            strategyType: props.strategyType,
            pammType: 'StandardGroup',
            pammsCount: props.pammsCount,
            walletSel: props.walletSel,
        })
    },
    ...(props.strategyType !== 'alpha'
        ? [
            {
                title: 'esg_investment_portfolio',
                value: '2',
                count: props.esgsCount,
                component: h(PammSubscriptions, {
                    strategyType: props.strategyType,
                    pammType: 'ESG',
                    pammsCount: props.pammsCount,
                    walletSel: props.walletSel,
                })
            }
        ]
        : []),
]);
</script>

<template>
    <AuthenticatedLayout :title="$t(`public.${strategyType}_subscriptions`)">
        <template #header>
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    {{ $t(`public.${strategyType}_subscriptions`) }}
                </h2>
            </div>
        </template>

        <!-- Tabs -->
        <Tabs value="0">
            <TabList>
                <Tab v-for="tab in tabs" :key="tab.title" :value="tab.value">
                    <div class="flex items-center gap-2">
                        <span>{{ $t(`public.${tab.title}`) }}</span>
                        <Badge :value="tab.count" />
                    </div>
                </Tab>
            </TabList>
            <TabPanels>
                <TabPanel v-for="tab in tabs" :value="tab.value">
                    <component :is="tab.component" />
                </TabPanel>
            </TabPanels>
        </Tabs>
    </AuthenticatedLayout>
</template>
