<script setup>
import Button from "@/Components/Button.vue";
import {CurrencyDollarIcon} from "@heroicons/vue/outline";
import {ref} from "vue";
import Modal from "@/Components/Modal.vue";

const applyRebateModal = ref(false);

const openApplyRebateModal = () => {
    applyRebateModal.value = true;
}

const closeModal = () => {
    applyRebateModal.value = false;
}
</script>

<template>
    <Button
        type="button"
        size="sm"
        variant="purple"
        class="w-full flex justify-center gap-1"
        v-slot="{ iconSizeClasses }"
        @click="openApplyRebateModal"
    >
        <CurrencyDollarIcon aria-hidden="true" :class="iconSizeClasses" />
        {{ $t('public.apply_rebate') }}
    </Button>

    <Modal :show="applyRebateModal" :title="$t('public.apply_rebate')" @close="closeModal">
        <div class="p-6 flex flex-col items-center justify-center">
            <div class="text-2xl text-gray-400 dark:text-gray-200">
                {{ $t('public.coming_soon') }}
            </div>
            <div class="text-lg text-gray-400 dark:text-gray-600">
                {{ $t('public.working_message') }}
            </div>
        </div>
    </Modal>
</template>
