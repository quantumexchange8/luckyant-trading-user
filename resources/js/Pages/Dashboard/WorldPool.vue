<script setup>
import Card from "primevue/card";
import {transactionFormat} from "@/Composables/index.js";

defineProps({
    world_pool: Object
});

const {formatAmount} = transactionFormat();
</script>

<template>
    <div class="flex flex-col md:flex-row items-center gap-5 w-full">
        <Card
            class="w-full"
            v-for="(amount, rank) in world_pool"
        >
            <template #content>
                <div class="flex flex-col items-center self-stretch">
                    <span class="text-xl font-semibold dark:text-white">${{ formatAmount(amount) }}</span>
                    <span class="text-sm text-gray-500">{{ rank }}</span>
                </div>
            </template>
        </Card>
    </div>
</template>
