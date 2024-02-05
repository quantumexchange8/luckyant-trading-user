<script setup>
import Button from "@/Components/Button.vue";
import {CurrencyDollarIcon} from "@heroicons/vue/outline";
import {ref} from "vue";
import Modal from "@/Components/Modal.vue";

const depositModal = ref(false);

const openDepositModal = () => {
    depositModal.value = true;
}

const closeModal = () => {
    depositModal.value = false;
}
</script>

<template>
    <Button
        type="button"
        size="sm"
        variant="success"
        class="w-full flex justify-center gap-1"
        v-slot="{ iconSizeClasses }"
        @click="openDepositModal"
    >
        <CurrencyDollarIcon aria-hidden="true" :class="iconSizeClasses" />
        Deposit
    </Button>

    <Modal :show="depositModal" title="Deposit" @close="closeModal">
        <div class="p-6 flex flex-col items-center justify-center">
            <div class="text-2xl text-gray-400 dark:text-gray-200">
                Coming SOON
            </div>
            <div class="text-lg text-gray-400 dark:text-gray-600">
                We're working hard to improve the user experience. Stay tuned!
            </div>
        </div>
    </Modal>
</template>
