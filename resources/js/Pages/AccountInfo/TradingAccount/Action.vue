<script setup>
import Button from "@/Components/Button.vue";
import {CreditCardAddIcon, CreditCardDownIcon, SwitchHorizontalRightIcon, UserUp01Icon, UserSquareIcon, PasscodeLockIcon, Edit05Icon} from "@/Components/Icons/outline.jsx";
import {ref} from "vue";
import DropdownLink from "@/Components/DropdownLink.vue";
import Dropdown from "@/Components/Dropdown.vue";
import Modal from "@/Components/Modal.vue";
import DepositBalance from "@/Pages/AccountInfo/TradingAccount/DepositBalance.vue";
import WithdrawBalance from "@/Pages/AccountInfo/TradingAccount/WithdrawBalance.vue";
import InternalTransferBalance from "@/Pages/AccountInfo/TradingAccount/InternalTransferBalance.vue";
import BecomeMaster from "@/Pages/AccountInfo/TradingAccount/BecomeMaster.vue";
import EditLeverage from "@/Pages/AccountInfo/TradingAccount/EditLeverage.vue";
import ChangePassword from "@/Pages/AccountInfo/TradingAccount/ChangePassword.vue";
import Tooltip from "@/Components/Tooltip.vue";

const props = defineProps({
    account: Object,
    walletSel: Array,
    leverageSel: Array,
    accountCounts: Number,
    masterAccountLogin: Array,
    type: String,
})

const accountActionModal = ref(false);
const modalComponent = ref('');
const actionType = ref(null);

const openAccountActionModal = (action) => {
    accountActionModal.value = true;
    if (action === 'deposit') {
        actionType.value = 'balance_in';
        modalComponent.value = 'Balance In';
    }
    else if (action === 'withdrawal') {
        actionType.value = 'balance_out';
        modalComponent.value = 'Balance Out';
    }
    else if (action === 'internal_transfer') {
        actionType.value = 'internal_transfer';
        modalComponent.value = 'Internal Transfer';
    }
    else if (action === 'become_master') {
        actionType.value = 'become_master';
        modalComponent.value = 'Become Master';
    }
    else if (action === 'edit_leverage') {
        actionType.value = 'edit_leverage';
        modalComponent.value = 'Edit Leverage';
    }
    else if (action === 'change_password') {
        actionType.value = 'change_password';
        modalComponent.value = 'Change Password';
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
        class="flex justify-center gap-2 w-full"
        v-slot="{ iconSizeClasses }"
        @click="openAccountActionModal('deposit')"
        v-if="!props.type"
    >
        <CreditCardAddIcon />
        {{ $t('public.balance_in') }}
    </Button>
    <Dropdown v-if="(!account.subscriber || account.subscriber.status === 'Unsubscribed') && !props.type" align="right" width="48">
        <template #trigger>
            <span class="inline-flex rounded-md">
                <Button
                    type="button"
                    variant="transparent"
                    class="flex justify-center"
                >
                    {{ $t('public.more') }}
                </Button>
            </span>
        </template>

        <template #content>
            <DropdownLink
                v-if="!account.subscriber || account.balance_out"
                @click="openAccountActionModal('withdrawal')"
            >
                <div class="flex items-center gap-2">
                    <CreditCardDownIcon class="w-5 h-5" />
                    <div>
                        {{ $t('public.balance_out') }}
                    </div>
                </div>
            </DropdownLink>
            <DropdownLink
                v-if="accountCounts > 1 && (!account.subscriber || account.balance_out)"
                @click="openAccountActionModal('internal_transfer')"
            >
                <div class="flex items-center gap-2">
                    <SwitchHorizontalRightIcon class="w-5 h-5" />
                    <div>
                        {{ $t('public.internal_transfer') }}
                    </div>
                </div>
            </DropdownLink>
            <DropdownLink
                v-if="!masterAccountLogin.includes(account.meta_login) && (!account.master_request || account.master_request.status !== 'Pending')"
                @click="openAccountActionModal('become_master')"
            >
                <div class="flex items-center gap-2">
                    <UserUp01Icon class="w-5 h-5" />
                    <div>
                        {{ $t('public.become_master') }}
                    </div>
                </div>
            </DropdownLink>
            <DropdownLink
                v-if="masterAccountLogin.includes(account.meta_login)"
                :href="'/account_info/master_profile/' + account.meta_login"
            >
                <div class="flex items-center gap-2">
                    <UserSquareIcon class="w-5 h-5" />
                    <div>
                        {{ $t('public.master_profile') }}
                    </div>
                </div>
            </DropdownLink>
        </template>
    </Dropdown>

    <Tooltip :content="$t('public.edit_leverage')" placement="bottom" v-if="props.type">
        <Button
            type="button"
            class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"
            variant="primary"
            pill
            @click="openAccountActionModal('edit_leverage')" 
        >
            <Edit05Icon aria-hidden="true" class="w-5 h-5 absolute" />
        </Button>
    </Tooltip>

    <Tooltip :content="$t('public.change_password')" placement="bottom" v-if="props.type">
        <Button
            type="button"
            class="justify-center px-4 pt-2 mx-1 w-8 h-8 focus:outline-none"
            variant="primary"
            pill
            @click="openAccountActionModal('change_password')" 
        >
            <PasscodeLockIcon aria-hidden="true" class="w-5 h-5 absolute" />
        </Button>
    </Tooltip>

    <Modal :show="accountActionModal" :title="$t('public.' + actionType)" @close="closeModal">
        <template v-if="modalComponent === 'Balance In'">
            <DepositBalance
                :account="account"
                :walletSel="walletSel"
                @update:accountActionModal="accountActionModal = $event"
            />
        </template>

        <template v-if="modalComponent === 'Balance Out'">
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

        <template v-if="modalComponent === 'Become Master'">
            <BecomeMaster
                :account="account"
                @update:accountActionModal="accountActionModal = $event"
            />
        </template>

        <template v-if="modalComponent === 'Edit Leverage'">
            <EditLeverage
                :account="account"
                :leverageSel="leverageSel"
                @update:accountActionModal="accountActionModal = $event"
            />
        </template>

        <template v-if="modalComponent === 'Change Password'">
            <ChangePassword
                :account="account"
                @update:accountActionModal="accountActionModal = $event"
            />
        </template>


    </Modal>

</template>
