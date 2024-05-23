<script setup>
import {ref} from "vue";
import Tooltip from "@/Components/Tooltip.vue";
import {MemberDetailIcon} from "@/Components/Icons/outline.jsx";
import Button from "@/Components/Button.vue";
import Modal from "@/Components/Modal.vue";
import SubscriptionBatchDetail from "@/Pages/Trading/SubscriptionListing/Partials/SubscriptionBatchDetail.vue";

const props = defineProps({
    subscription: Object,
    terms: Object,
})

const subscriptionModal = ref(false);
const modalComponent = ref('');

const openSubscriptionModal = (componentType) => {
    subscriptionModal.value = true;
    if (componentType === 'view') {
        modalComponent.value = 'view_details';
    } else if (componentType === 'swap_master') {
        modalComponent.value = 'swap_master';
    }  else if (componentType === 'terminate') {
        modalComponent.value = 'terminate';
    }
}

const closeModal = () => {
    subscriptionModal.value = false
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
            @click="openSubscriptionModal('view')"
        >
            <MemberDetailIcon aria-hidden="true" class="w-6 h-6 absolute" />
        </Button>
    </Tooltip>

    <Modal :show="subscriptionModal" :title="$t('public.' + modalComponent)" @close="closeModal">
        <template v-if="modalComponent === 'view_details'">
            <SubscriptionBatchDetail
                :subscription="subscription"
                :terms="terms"
                @update:subscriptionModal="subscriptionModal = $event"
            />
        </template>
    </Modal>
</template>
