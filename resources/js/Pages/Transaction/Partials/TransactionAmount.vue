<script setup>
const props = defineProps({
    transaction: Object,
})
</script>

<template>
    <div
        :class="{
            'text-success-500': transaction.transaction_type === 'BalanceIn' && transaction.status === 'Success',
            'text-error-500': transaction.transaction_type === 'BalanceOut' && transaction.status === 'Success'
        }"
    >
        $ {{ transaction.transaction_amount }}
    </div>
</template>
