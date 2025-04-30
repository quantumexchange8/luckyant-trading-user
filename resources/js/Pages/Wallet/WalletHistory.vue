<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import WalletHistoryOverview from "@/Pages/Wallet/WalletHistoryOverview.vue";
import WalletHistoryTable from "@/Pages/Wallet/WalletHistoryTable.vue";
import {ref} from "vue";

defineProps({
    transactionTypeSel: Array
})

const cashWalletAmount = ref(null);
const bonusWalletAmount = ref(null);
const eWalletAmount = ref(null);

const handleUpdateTotals = (data) => {
    cashWalletAmount.value = data.cashWalletAmount;
    bonusWalletAmount.value = data.bonusWalletAmount;
    eWalletAmount.value = data.eWalletAmount;
};
</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar.wallet_history')">
        <template #header>
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    {{ $t('public.sidebar.wallet_history') }}
                </h2>
            </div>
        </template>

        <div class="flex flex-col gap-5 items-center self-stretch">
            <WalletHistoryOverview
                :cashWalletAmount="cashWalletAmount"
                :bonusWalletAmount="bonusWalletAmount"
                :eWalletAmount="eWalletAmount"
            />

            <WalletHistoryTable
                :transactionTypeSel="transactionTypeSel"
                @update-totals="handleUpdateTotals"
            />
        </div>
    </AuthenticatedLayout>
</template>
