<script setup>
import {ref} from "vue";
import Tooltip from "@/Components/Tooltip.vue";
import {RefreshCw03Icon, XCircleIcon, InfoCircleIcon} from "@/Components/Icons/outline.jsx";
import {BanIcon} from "@heroicons/vue/outline";
import Button from "@/Components/Button.vue";
import Modal from "@/Components/Modal.vue";
import {transactionFormat} from "@/Composables/index.js";
import {useForm} from "@inertiajs/vue3";
import StopRenewSubscription from "@/Pages/Trading/MasterListing/StopRenewSubscription.vue";
import TerminateSubscription from "@/Pages/Trading/MasterListing/TerminateSubscription.vue";
import SwapMaster from "@/Pages/Trading/SubscriptionListing/Partials/SwapMaster.vue";

const props = defineProps({
    subscription: Object,
    terms: Object,
    swapMasterSel: Array
})

const subscriptionModal = ref(false);
const modalComponent = ref('');

const openSubscriptionModal = (componentType) => {
    subscriptionModal.value = true;
    if (componentType === 'stop_renewal') {
        modalComponent.value = 'stop_renewal';
    } else if (componentType === 'swap_master') {
        modalComponent.value = 'swap_master';
    }  else if (componentType === 'terminate') {
        modalComponent.value = 'terminate';
    } else if (componentType === 'view') {
        modalComponent.value = 'view_details';
    } else if (componentType === 'request_renewal') {
        modalComponent.value = 'request_renewal';
    }
}

const closeModal = () => {
    subscriptionModal.value = false
    modalComponent.value = null;
}
</script>

<template>
    <Tooltip
        :content="$t('public.swap_master')"
        placement="bottom"
    >
        <Button
            type="button"
            pill
            class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"
            variant="primary"
            @click="openSubscriptionModal('swap_master')"
        >
            <RefreshCw03Icon aria-hidden="true" class="w-5 h-5 absolute" />
        </Button>
    </Tooltip>

    <Tooltip
        v-if="subscription.auto_renewal"
        :content="$t('public.stop_renewal')"
        placement="bottom"
    >
        <Button
            type="button"
            pill
            class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"
            variant="gray"
            @click="openSubscriptionModal('stop_renewal')"
        >
            <BanIcon aria-hidden="true" class="w-6 h-6 absolute" />
        </Button>
    </Tooltip>

    <Tooltip
        v-if="subscription.status !== 'Terminated' && !subscription.auto_renewal"
        :content="$t('public.request_renewal')"
        placement="bottom"
    >
        <Button
            type="button"
            pill
            class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"
            variant="info"
            @click="openSubscriptionModal('request_renewal')"
        >
            <InfoCircleIcon aria-hidden="true" class="w-6 h-6 absolute" />
        </Button>
    </Tooltip>

    <Tooltip
        :content="$t('public.terminate')"
        placement="bottom"
    >
        <Button
            type="button"
            pill
            class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"
            variant="danger"
            @click="openSubscriptionModal('terminate')"
        >
            <XCircleIcon aria-hidden="true" class="w-6 h-6 absolute" />
        </Button>
    </Tooltip>

    <Modal :show="subscriptionModal" :title="$t('public.' + modalComponent)" @close="closeModal">
        <template v-if="modalComponent === 'swap_master'">
            <SwapMaster
                :subscription="subscription"
                :swapMasterSel="swapMasterSel"
                @update:subscriptionModal="subscriptionModal = $event"
            />
        </template>

        <template v-if="modalComponent === 'stop_renewal' || modalComponent === 'request_renewal'">
            <StopRenewSubscription
                :subscription="subscription"
                :terms="terms"
                @update:subscriptionModal="subscriptionModal = $event"
            />
        </template>

        <template v-if="modalComponent === 'terminate'">
            <TerminateSubscription
                :subscription="subscription"
                :terms="terms"
                @update:subscriptionModal="subscriptionModal = $event"
            />
        </template>
    </Modal>
</template>
