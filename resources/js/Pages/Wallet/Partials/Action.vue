<script setup>
import {ref} from "vue";
import Tooltip from "@/Components/Tooltip.vue";
import {MemberDetailIcon} from "@/Components/Icons/outline.jsx";
import Button from "@/Components/Button.vue";
import Modal from "@/Components/Modal.vue";
import WalletHistoryDetail from "@/Pages/Wallet/Partials/WalletHistoryDetail.vue";

const props = defineProps({
    walletHistory: Object,
})

const walletHistoryModal = ref(false);
const modalComponent = ref('');

const openWalletHistoryModal = (componentType) => {
    walletHistoryModal.value = true;
    if (componentType === 'view') {
        modalComponent.value = 'view_details';
    }
}

const closeModal = () => {
    walletHistoryModal.value = false
    modalComponent.value = null;
}
</script>

<template>
    <Tooltip
        :content="$t('public.view_details')"
        placement="bottom"
    >
        <Button
            type="button"
            pill
            class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"
            variant="gray"
            @click="openWalletHistoryModal('view')"
        >
            <MemberDetailIcon aria-hidden="true" class="w-6 h-6 absolute" />
        </Button>
    </Tooltip>

    <Modal :show="walletHistoryModal" :title="$t('public.' + modalComponent)" @close="closeModal">
        <template v-if="modalComponent === 'view_details'">
            <WalletHistoryDetail
                :walletHistory="walletHistory"
                @update:walletHistoryModal="walletHistoryModal = $event"
            />
        </template>
    </Modal>
</template>
