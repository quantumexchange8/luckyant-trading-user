<script setup>
import Button from "@/Components/Button.vue";
import {CurrencyDollarIcon} from "@heroicons/vue/outline";
import {ref} from "vue";
import Modal from "@/Components/Modal.vue";

const withdrawalModal = ref(false);

const openWithdrawalModal = () => {
    withdrawalModal.value = true;
}

const closeModal = () => {
    withdrawalModal.value = false;
}
</script>

<template>
    <Button
        type="button"
        size="sm"
        variant="danger"
        class="w-full flex justify-center gap-1"
        v-slot="{ iconSizeClasses }"
        @click="openWithdrawalModal"
    >
        <CurrencyDollarIcon aria-hidden="true" :class="iconSizeClasses" />
        Withdrawal
    </Button>

    <Modal :show="withdrawalModal" title="Withdrawal" @close="closeModal">
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
