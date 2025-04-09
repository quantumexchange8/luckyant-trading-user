<script setup>
import {DotsHorizontalIcon} from "@heroicons/vue/outline";
import Button from "@/Components/Button.vue";
import TieredMenu from "primevue/tieredmenu";
import {computed, h, ref} from "vue";
import {
    CreditCardDownIcon,
    ReportIcon,
    LeverageIcon,
    SwitchHorizontalRightIcon,
    PasscodeLockIcon
} from "@/Components/Icons/outline.jsx"
import WithdrawBalance from "@/Pages/AccountInfo/TradingAccount/WithdrawBalance.vue";
import EditLeverage from "@/Pages/AccountInfo/TradingAccount/EditLeverage.vue";
import ChangePassword from "@/Pages/AccountInfo/TradingAccount/ChangePassword.vue";
import InternalTransferBalance from "@/Pages/AccountInfo/TradingAccount/InternalTransferBalance.vue";
import AccountReport from "@/Pages/Account/Partials/AccountReport.vue";
import {usePage} from "@inertiajs/vue3";
import Dialog from "primevue/dialog";

const props = defineProps({
    activeAccountCounts: Number,
    account: Object,
    walletSel: Array,
    leverageSel: Array,
})

const menu = ref();
const visible = ref(false);
const dialogType = ref('');

const toggle = (event) => {
    menu.value.toggle(event);
};

const items = ref([
    {
        label: 'balance_out',
        icon: h(CreditCardDownIcon),
        command: () => {
            visible.value = true;
            dialogType.value = 'balance_out';
        },
    },
    {
        label: 'internal_transfer',
        icon: h(SwitchHorizontalRightIcon),
        command: () => {
            visible.value = true;
            dialogType.value = 'internal_transfer';
        },
    },
    {
        label: 'edit_leverage',
        icon: h(LeverageIcon),
        command: () => {
            visible.value = true;
            dialogType.value = 'edit_leverage';
        },
    },
    {
        label: 'change_password',
        icon: h(PasscodeLockIcon),
        command: () => {
            visible.value = true;
            dialogType.value = 'change_password';
        },
    },
    {
        label: 'transaction_history',
        icon: h(ReportIcon),
        command: () => {
            visible.value = true;
            dialogType.value = 'transaction_history';
        },
    },
]);

const formattedItems = computed(() => {
    return items.value.filter(item => {
        if (item.label === 'balance_out') {
            return props.account.balance_out && usePage().props.auth.user.role !== 'special_demo';
        }

        if (item.label === 'internal_transfer') {
            return props.activeAccountCounts > 1 && props.account.balance_out;
        }
        return true;
    });
});

const closeModal = () => {
    visible.value = false;
}
</script>

<template>
    <Button
        variant="gray-outline"
        size="sm"
        type="button"
        iconOnly
        pill
        @click="toggle"
        aria-haspopup="true"
        aria-controls="overlay_tmenu"
        v-slot="{ iconSizeClasses }"
    >
        <DotsHorizontalIcon :class="iconSizeClasses" />
    </Button>

    <!-- Menu -->
    <TieredMenu ref="menu" id="overlay_tmenu" :model="formattedItems" popup>
        <template #item="{ item, props }">
            <div
                class="flex items-center gap-3 self-stretch"
                v-bind="props.action"
                :class="{ 'hidden': item.disabled }"
            >
                <component :is="item.icon" class="w-5" />
                <span class="font-medium" :class="{'text-error-500': item.label === 'delete_account'}">{{ $t(`public.${item.label}`) }}</span>
            </div>
        </template>
    </TieredMenu>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t(`public.${dialogType}`)"
        class="dialog-xs md:dialog-md"
    >
        <template v-if="dialogType === 'balance_out'">
            <WithdrawBalance
                :account="account"
                :walletSel="walletSel"
                @update:accountActionModal="visible = $event"
            />
        </template>

        <template v-if="dialogType === 'internal_transfer'">
            <InternalTransferBalance
                :account="account"
                @update:accountActionModal="visible = $event"
            />
        </template>

        <template v-if="dialogType === 'edit_leverage'">
            <EditLeverage
                :account="account"
                :leverageSel="leverageSel"
                @update:accountActionModal="visible = $event"
            />
        </template>

        <template v-if="dialogType === 'change_password'">
            <ChangePassword
                :account="account"
                @update:accountActionModal="visible = $event"
            />
        </template>

        <template v-if="dialogType === 'transaction_history'">
            <AccountReport
                :account="account"
                @update:accountActionModal="visible = $event"
            />
        </template>
    </Dialog>
</template>
