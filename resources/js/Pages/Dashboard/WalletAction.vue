<script setup>
import Deposit from "@/Pages/Dashboard/Deposit.vue";
import Withdrawal from "@/Pages/Dashboard/Withdrawal.vue";
import InternalTransfer from "@/Pages/Dashboard/InternalTransfer.vue";
import ApplyRebate from "@/Pages/Dashboard/ApplyRebate.vue";

const props = defineProps({
    wallet: Object,
    walletSel: Array,
    paymentAccountSel: Array,
    paymentDetails: Object,
    withdrawalFee: Object,
    countries: Array,
})
</script>

<template>
    <div
        v-if="wallet.type === 'cash_wallet'"
        class="flex flex-col gap-2"
    >
        <div class="flex justify-between w-full gap-2">
            <Deposit
                :walletSel="walletSel"
                :countries="countries"
            />
            <Withdrawal
                :walletSel="walletSel"
                :withdrawalFee="withdrawalFee"
                :paymentAccountSel="paymentAccountSel"
            />
        </div>
        <div class="flex items-center justify-center w-full">
            <InternalTransfer />
        </div>
    </div>
    <div v-else>
        <ApplyRebate />
    </div>
</template>
