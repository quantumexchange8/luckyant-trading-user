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
    eWalletSel: Array,
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
                    :wallet="wallet"
                    :countries="countries"
                />
                <Withdrawal
                    :wallet="wallet"
                    :withdrawalFee="withdrawalFee"
                    :paymentAccountSel="paymentAccountSel"
                />
            </div>
            <div class="flex items-center justify-center w-full">
                <InternalTransfer
                    :eWalletSel="eWalletSel"
                    :wallet="wallet"
                />
            </div>
        </template>
        <template v-else-if="wallet.type === 'bonus_wallet'">
            <InternalTransfer
                :walletSel="walletSel"
                :eWalletSel="eWalletSel"
                :wallet="wallet"
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
