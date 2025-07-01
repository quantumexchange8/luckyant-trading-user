<script setup>
import Deposit from "@/Pages/Dashboard/Deposit.vue";
import InternalTransfer from "@/Pages/Dashboard/InternalTransfer.vue";
import DepositToMeta from "@/Pages/Dashboard/DepositToMeta.vue";
import Transfer from "@/Pages/Dashboard/Transfer.vue"
import Withdrawal from "@/Pages/Dashboard/Withdrawal.vue";

const props = defineProps({
    wallet: Object,
    eWalletSel: Array,
    withdrawalFee: Object,
    withdrawalFeePercentage: Object,
})
</script>

<template>
    <div class="flex flex-col gap-2">
        <template v-if="wallet.type === 'cash_wallet'">
            <div class="flex justify-between w-full gap-2">
                <Deposit
                    :wallet="wallet"
                />
                <Withdrawal
                    :withdrawalFee="withdrawalFee"
                    :withdrawalFeePercentage="withdrawalFeePercentage"
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
                :eWalletSel="eWalletSel"
                :wallet="wallet"
            />
        </template>
        <template v-else-if="wallet.type === 'e_wallet'">
            <Transfer
                :eWalletSel="eWalletSel"
                :wallet="wallet"
            />
        </template>
    </div>
</template>
