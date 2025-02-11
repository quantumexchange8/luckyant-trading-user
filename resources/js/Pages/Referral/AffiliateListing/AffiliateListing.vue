<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import AffiliateListingOverview from "@/Pages/Referral/AffiliateListing/AffiliateListingOverview.vue";
import AffiliateListingTable from "@/Pages/Referral/AffiliateListing/AffiliateListingTable.vue";
import {ref} from "vue";

const totalAffiliate = ref(null);
const totalDeposit = ref(null);

const handleOverview = (data) => {
    totalAffiliate.value = data.totalAffiliate;
    totalDeposit.value = data.totalDeposit;
}
</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar.affiliate_listing')">
        <template #header>
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    {{ $t('public.sidebar.affiliate_listing') }}
                </h2>
            </div>
        </template>

        <div class="flex flex-col gap-5 items-center self-stretch">
            <AffiliateListingOverview
                :totalAffiliate="totalAffiliate"
                :totalDeposit="totalDeposit"
            />

            <AffiliateListingTable
                @update-totals="handleOverview"
            />
        </div>
    </AuthenticatedLayout>
</template>
