<script setup>
import Button from "@/Components/Button.vue";
import {CreditCardRefreshIcon} from "@/Components/Icons/outline";
import {ref} from "vue";
import Modal from "@/Components/Modal.vue";

const internalTransferModal = ref(false);

const openInternalTransferModal = () => {
    internalTransferModal.value = true;
}

const closeModal = () => {
    internalTransferModal.value = false;
}
</script>

<template>
    <Button
        type="button"
        size="sm"
        variant="primary"
        class="w-full flex justify-center gap-1"
        v-slot="{ iconSizeClasses }"
        @click="openInternalTransferModal"
    >
        <CreditCardRefreshIcon aria-hidden="true" :class="iconSizeClasses" />
        Internal Transfer
    </Button>

    <Modal :show="internalTransferModal" title="Internal Transfer" @close="closeModal">
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
