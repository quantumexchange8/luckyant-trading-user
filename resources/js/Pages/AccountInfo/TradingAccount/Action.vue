<script setup>
import Button from "@/Components/Button.vue";
import {CreditCardAddIcon, CreditCardDownIcon, SwitchHorizontalRightIcon} from "@/Components/Icons/outline.jsx";
import {ref} from "vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import Dropdown from "@/Components/Dropdown.vue";
import Modal from "@/Components/Modal.vue";
import DepositBalance from "@/Pages/AccountInfo/TradingAccount/DepositBalance.vue";
import WithdrawBalance from "@/Pages/AccountInfo/TradingAccount/WithdrawBalance.vue";
import InternalTransferBalance from "@/Pages/AccountInfo/TradingAccount/InternalTransferBalance.vue";

const props = defineProps({
    account: Object,
    walletSel: Array,
    tradingAccounts: Object,
})

const accountActionModal = ref(false);
const modalComponent = ref('');

const openAccountActionModal = (action) => {
    accountActionModal.value = true;
    if (action === 'deposit') {
        modalComponent.value = 'Deposit';
    }
    else if (action === 'withdrawal') {
        modalComponent.value = 'Withdrawal';
    }
    else if (action === 'internal_transfer') {
        modalComponent.value = 'Internal Transfer';
    }
}

const closeModal = () => {
    accountActionModal.value = false
    modalComponent.value = '';
}
</script>

<template>
    <Button
        type="button"
        variant="primary"
        class="flex justify-center gap-2"
        v-slot="{ iconSizeClasses }"
        @click="openAccountActionModal('deposit')"
    >
        <CreditCardAddIcon />
        Deposit
    </Button>
    <Dropdown align="right" width="48">
        <template #trigger>
                    <span class="inline-flex rounded-md">
                        <Button
                            type="button"
                            variant="transparent"
                            class="flex justify-center"
                        >
                            More
                        </Button>
                    </span>
        </template>

        <template #content>
            <DropdownLink
                @click="openAccountActionModal('withdrawal')"
            >
                <div class="flex items-center gap-2">
                    <CreditCardDownIcon class="w-5 h-5" />
                    <div>
                        Withdrawal
                    </div>
                </div>
            </DropdownLink>
            <DropdownLink
                v-if="tradingAccounts.length > 1"
                @click="openAccountActionModal('internal_transfer')"
            >
                <div class="flex items-center gap-2">
                    <SwitchHorizontalRightIcon class="w-5 h-5" />
                    <div>
                        Internal Transfer
                    </div>
                </div>
            </DropdownLink>
        </template>
    </Dropdown>

    <Modal :show="accountActionModal" :title="modalComponent" @close="closeModal">
        <template v-if="modalComponent === 'Deposit'">
            <DepositBalance
                :account="account"
                :walletSel="walletSel"
                @update:accountActionModal="accountActionModal = $event"
            />
        </template>

        <template v-if="modalComponent === 'Withdrawal'">
            <WithdrawBalance
                :account="account"
                :walletSel="walletSel"
                @update:accountActionModal="accountActionModal = $event"
            />
        </template>

        <template v-if="modalComponent === 'Internal Transfer'">
            <InternalTransferBalance
                :account="account"
                @update:accountActionModal="accountActionModal = $event"
            />
        </template>
    </Modal>

</template>
