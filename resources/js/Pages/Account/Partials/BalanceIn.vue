<script setup>
import {CreditCardAddIcon} from "@/Components/Icons/outline.jsx";
import Button from "@/Components/Button.vue";
import {ref} from "vue";
import Modal from "@/Components/Modal.vue";
import DepositBalance from "@/Pages/AccountInfo/TradingAccount/DepositBalance.vue";

const props = defineProps({
    account: Object,
    walletSel: Array,
})

const visible = ref(false);

const closeModal = () => {
    visible.value = false;
}
</script>

<template>
    <Button
        type="button"
        size="sm"
        variant="primary"
        class="flex justify-center gap-2 w-full md:max-w-48"
        v-slot="{ iconSizeClasses }"
        @click="visible = true"
        v-if="account.balance_in"
    >
        <CreditCardAddIcon :class="iconSizeClasses" />
        {{ $t('public.balance_in') }}
    </Button>

    <Modal
        :show="visible"
        :title="$t('public.balance_in')"
        @close="closeModal"
    >
        <DepositBalance
            :account="account"
            :walletSel="walletSel"
            @update:accountActionModal="visible = $event"
        />
    </Modal>
</template>
