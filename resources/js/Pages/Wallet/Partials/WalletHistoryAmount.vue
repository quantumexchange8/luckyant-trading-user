<script setup>
const props = defineProps({
    wallet: Object,
    walletIds: Array
})
</script>

<template>
    <div
        :class="{
            'text-primary-500': walletIds.includes(wallet.to_wallet_id) && wallet.status === 'Success'
        }"
    >
        $ {{ wallet.transaction_amount }}
    </div>
</template>
