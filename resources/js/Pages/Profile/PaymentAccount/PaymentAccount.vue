<script setup>
import AddPaymentAccount from "@/Pages/Profile/PaymentAccount/AddPaymentAccount.vue";
import Badge from "@/Components/Badge.vue";

const props = defineProps({
    paymentAccounts: Object,
    countries: Array,
    currencies: Array,
})

const statusVariant = (transactionStatus) => {
    if (transactionStatus === 'Active') return 'success';
    if (transactionStatus === 'Inactive') return 'danger';
}
</script>

<template>
    <div
        class="grid grid-cols-1 sm:grid-cols-3 gap-5"
    >
        <div
            v-for="paymentAccount in paymentAccounts"
            class="card text-gray-300 w-full hover:brightness-90 transition-all cursor-pointer group bg-gradient-to-tl from-gray-600 to-gray-800 hover:from-gray-800 hover:to-gray-700 dark:from-gray-900 dark:to-gray-950 dark:hover:from-gray-800 dark:hover:to-gray-950 border-r-2 border-t-2 border-gray-600 dark:border-gray-900 rounded-lg overflow-hidden relative"
        >
            <div class="px-8 py-5">
                <div class="flex justify-between items-center mb-4">
                    <div class="bg-orange-500 w-10 h-10 rounded-full rounded-tl-none group-hover:-translate-y-1 group-hover:shadow-xl group-hover:shadow-red-900 transition-all"></div>
                    <Badge :variant="statusVariant(paymentAccount.status)">{{ paymentAccount.status }}</Badge>
                </div>

                <div class="uppercase font-bold text-xl">
                    {{ paymentAccount.payment_platform_name }}
                </div>
                <div class="text-gray-300 uppercase tracking-widest">
                    {{ paymentAccount.payment_account_name }}
                </div>
                <div class="text-gray-400 mt-6">
                    <p class="font-bold">{{ paymentAccount.currency }}</p>
                    <p>{{ paymentAccount.account_no }}</p>
                </div>
            </div>


            <div class="h-2 w-full bg-gradient-to-l via-yellow-500 group-hover:blur-xl blur-2xl m-auto rounded transition-all absolute bottom-0"></div>
            <div class="h-0.5 group-hover:w-full bg-gradient-to-l via-yellow-700 dark:via-yellow-950 group-hover:via-yellow-500 w-[70%] m-auto rounded transition-all"></div>
        </div>
        <AddPaymentAccount
            :countries="countries"
            :currencies="currencies"
        />
    </div>
</template>
