<script setup>
import PerfectScrollbar from '@/Components/PerfectScrollbar.vue'
import SidebarLink from '@/Components/Sidebar/SidebarLink.vue'
import { DashboardIcon, CoinsHandIcon, ReportIcon, Wallet01Icon, CoinsStacked02Icon } from '@/Components/Icons/outline'
import SidebarCollapsible from '@/Components/Sidebar/SidebarCollapsible.vue'
import SidebarCollapsibleItem from '@/Components/Sidebar/SidebarCollapsibleItem.vue'
import { TemplateIcon, ViewGridIcon, SwitchHorizontalIcon, UserGroupIcon, UserIcon } from '@heroicons/vue/outline'
import {ref} from "vue";
import {usePage} from "@inertiajs/vue3";

const page = usePage();
const hasPammMasters = ref(page.props.hasPammMasters);
</script>

<template>
    <PerfectScrollbar
        tagname="nav"
        aria-label="main"
        class="relative flex flex-col flex-1 max-h-full gap-4 px-3"
    >
        <SidebarLink
            :title="$t('public.sidebar.dashboard')"
            :href="route('dashboard')"
            :active="route().current('dashboard')"
        >
            <template #icon>
                <DashboardIcon
                    class="flex-shrink-0 w-6 h-6"
                    aria-hidden="true"
                />
            </template>
        </SidebarLink>

        <SidebarLink
            :title="$t('public.sidebar.account_info')"
            :href="route('account_info.account_info')"
            :active="route().current('account_info.*')"
        >
            <template #icon>
                <ViewGridIcon
                    class="flex-shrink-0 w-6 h-6"
                    aria-hidden="true"
                />
            </template>
        </SidebarLink>

        <SidebarCollapsible
            :title="$t('public.sidebar.copy_trading')"
            :active="route().current('trading.*')"
        >
            <template #icon>
                <CoinsHandIcon
                    class="flex-shrink-0 w-6 h-6"
                    aria-hidden="true"
                />
            </template>

            <SidebarCollapsibleItem
                :href="route('trading.master_listing')"
                :title="$t('public.sidebar.master_trader_listing')"
                :active="route().current('trading.master_listing')"
            />
            <SidebarCollapsibleItem
                :href="route('trading.subscription_listing')"
                :title="$t('public.subscriptions')"
                :active="route().current('trading.subscription_listing')"
            />
        </SidebarCollapsible>

<!--        <SidebarCollapsible-->
<!--            :title="$t('public.sidebar.transaction')"-->
<!--            :active="route().current('transaction.*')"-->
<!--        >-->
<!--            <template #icon>-->
<!--                <SwitchHorizontalIcon-->
<!--                    class="flex-shrink-0 w-6 h-6"-->
<!--                    aria-hidden="true"-->
<!--                />-->
<!--            </template>-->

<!--            <SidebarCollapsibleItem-->
<!--                :href="route('transaction.transaction_listing')"-->
<!--                :title="$t('public.transaction_history')"-->
<!--                :active="route().current('transaction.transaction_listing')"-->
<!--            />-->

<!--            <SidebarCollapsibleItem-->
<!--                :href="route('transaction.transfer_history')"-->
<!--                :title="$t('public.sidebar.transfer_history')"-->
<!--                :active="route().current('transaction.transfer_history')"-->
<!--            />-->

<!--            <SidebarCollapsibleItem-->
<!--                :href="route('transaction.wallet')"-->
<!--                :title="$t('public.sidebar.wallet')"-->
<!--                :active="route().current('transaction.wallet')"-->
<!--            />-->
<!--            <SidebarCollapsibleItem-->
<!--                :href="route('transaction.trading_account')"-->
<!--                :title="$t('public.sidebar.trading_account')"-->
<!--                :active="route().current('transaction.trading_account')"-->
<!--            />-->
<!--        </SidebarCollapsible>-->

        <SidebarCollapsible
            v-if="hasPammMasters"
            :title="$t('public.pamm_listing')"
            :active="route().current('pamm.*')"
        >
            <template #icon>
                <CoinsStacked02Icon
                    class="flex-shrink-0 w-6 h-6"
                    aria-hidden="true"
                />
            </template>

            <SidebarCollapsibleItem
                :href="route('pamm.pamm_listing')"
                :title="$t('public.pamm_listing')"
                :active="route().current('pamm.pamm_listing')"
            />

<!--            <SidebarCollapsibleItem-->
<!--                :href="route('referral.affiliateListing')"-->
<!--                :title="$t('public.sidebar.affiliate_listing')"-->
<!--                :active="route().current('referral.affiliateListing')"-->
<!--            />-->

        </SidebarCollapsible>

        <SidebarCollapsible
            :title="$t('public.sidebar.affiliate_program')"
            :active="route().current('referral.*')"
        >
            <template #icon>
                <UserGroupIcon
                    class="flex-shrink-0 w-6 h-6"
                    aria-hidden="true"
                />
            </template>

            <SidebarCollapsibleItem
                :href="route('referral.index')"
                :title="$t('public.sidebar.affiliate_tree')"
                :active="route().current('referral.index')"
            />

            <SidebarCollapsibleItem
                :href="route('referral.affiliateListing')"
                :title="$t('public.sidebar.affiliate_listing')"
                :active="route().current('referral.affiliateListing')"
            />

            <SidebarCollapsibleItem
                :href="route('referral.affiliateSubscription')"
                :title="$t('public.sidebar.affiliate_subscriptions')"
                :active="route().current('referral.affiliateSubscription')"
            />

        </SidebarCollapsible>

        <SidebarCollapsible
            :title="$t('public.sidebar.report')"
            :active="route().current('report.*')"
        >
            <template #icon>
                <ReportIcon
                    class="flex-shrink-0 w-6 h-6"
                    aria-hidden="true"
                />
            </template>

            <SidebarCollapsibleItem
                :href="route('report.trade_rebate_history')"
                :title="$t('public.sidebar.trade_rebate')"
                :active="route().current('report.trade_rebate_history')"
            />

            <SidebarCollapsibleItem
                :href="route('report.wallet_history')"
                :title="$t('public.sidebar.wallet_history')"
                :active="route().current('report.wallet_history')"
            />

            <SidebarCollapsibleItem
                :href="route('report.trade_history')"
                :title="$t('public.sidebar.trade_history')"
                :active="route().current('report.trade_history')"
            />

            <SidebarCollapsibleItem
                :href="route('report.performance_incentive')"
                :title="$t('public.performance_incentive')"
                :active="route().current('report.performance_incentive')"
            />

        </SidebarCollapsible>

        <SidebarLink
            :title="$t('public.sidebar.wallet')"
            :href="route('wallet.wallet_history')"
            :active="route().current('wallet.wallet_history')"
        >
            <template #icon>
                <Wallet01Icon
                    class="flex-shrink-0 w-6 h-6"
                    aria-hidden="true"
                />
            </template>
        </SidebarLink>

        <SidebarLink
            :title="$t('public.sidebar.profile')"
            :href="route('profile.edit')"
            :active="route().current('profile.edit')"
        >
            <template #icon>
                <UserIcon
                    class="flex-shrink-0 w-6 h-6"
                    aria-hidden="true"
                />
            </template>
        </SidebarLink>

<!--        <SidebarCollapsible-->
<!--            title="Components"-->
<!--            :active="route().current('components.*')"-->
<!--        >-->
<!--            <template #icon>-->
<!--                <TemplateIcon-->
<!--                    class="flex-shrink-0 w-6 h-6"-->
<!--                    aria-hidden="true"-->
<!--                />-->
<!--            </template>-->

<!--            <SidebarCollapsibleItem-->
<!--                :href="route('components.buttons')"-->
<!--                title="Buttons"-->
<!--                :active="route().current('components.buttons')"-->
<!--            />-->
<!--        </SidebarCollapsible>-->

        <!-- Examples -->
        <!--
        => External link example
        <SidebarLink
            title="Github"
            href="https://github.com/kamona-wd/kui-laravel-breeze"
            external
            target="_blank"
        >
        </SidebarLink>

        => Collapsible examples
        <SidebarCollapsible title="Users" :active="$page.url.startsWith('/users')">
            <SidebarCollapsibleItem :href="route('users.index')" title="List" :active="$page.url === '/users/index'" />
            <SidebarCollapsibleItem :href="route('users.create')" title="Create" :active="$page.url === '/users/create'" />
        </SidebarCollapsible>

        <SidebarCollapsible title="Users" :active="usePage().url.value.startsWith('/users')">
            <template #icon>
                <UserIcon
                    class="flex-shrink-0 w-6 h-6"
                    aria-hidden="true"
                />
            </template>

            <SidebarCollapsibleItem :href="route('users.index')" title="List" :active="route().current('users.index')" />
            <SidebarCollapsibleItem :href="route('users.create')" title="Create" :active="route().current('users.create')" />
        </SidebarCollapsible>-->
    </PerfectScrollbar>
</template>
