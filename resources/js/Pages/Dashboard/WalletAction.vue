<script setup>
import Deposit from "@/Pages/Dashboard/Deposit.vue";
import Withdrawal from "@/Pages/Dashboard/Withdrawal.vue";
import InternalTransfer from "@/Pages/Dashboard/InternalTransfer.vue";
import ApplyRebate from "@/Pages/Dashboard/ApplyRebate.vue";
import DepositToMeta from "@/Pages/Dashboard/DepositToMeta.vue";
import DepositBalance from "@/Pages/AccountInfo/TradingAccount/DepositBalance.vue";

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
    <div class="flex flex-col gap-2">
        <template v-if="wallet.type === 'cash_wallet'">
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
                <InternalTransfer
                    :walletSel="walletSel"
                    :defaultWallet="walletSel[0]"
                />
            </div>
        </template>
        <template v-else-if="wallet.type === 'bonus_wallet'">
            <ApplyRebate />
            <InternalTransfer 
                :walletSel="walletSel"
                :defaultWallet="walletSel[1]"
            />
        </template>
        <template v-else-if="wallet.type === 'e_wallet'">
            <DepositToMeta
                :walletSel="walletSel"
                :wallet="wallet"
            />
        </template>
    </div>
</template>
