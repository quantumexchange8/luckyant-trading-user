<script setup>
import Button from "primevue/button";
import {
    IconDots
} from "@tabler/icons-vue"
import {h, ref} from "vue";
import TieredMenu from "primevue/tieredmenu";
import {
    CreditCardXIcon,
} from "@/Components/Icons/outline.jsx";
import {IconBan} from "@tabler/icons-vue"
import Dialog from "primevue/dialog";
import SwitchMaster from "@/Pages/Trading/SubscriptionListing/Partials/SwitchMaster.vue";
import StopRenewSubscription from "@/Pages/Trading/MasterListing/StopRenewSubscription.vue";
import TerminateSubscription from "@/Pages/Trading/MasterListing/TerminateSubscription.vue";

const props = defineProps({
    strategyType: String,
    subscriber: Object,
})

const menu = ref();
const visible = ref(false);
const dialogType = ref('');

const toggle = (event) => {
    menu.value.toggle(event);
};

const items = ref([
    {
        label: 'stop_renewal',
        icon: h(CreditCardXIcon),
        command: () => {
            visible.value = true;
            dialogType.value = 'stop_renewal';
        },
    },
    {
        label: 'terminate',
        icon: h(IconBan),
        command: () => {
            visible.value = true;
            dialogType.value = 'terminate';
        },
    },
]);

const openDialog = (type) => {
    visible.value = true;
    dialogType.value = type;
}
</script>

<template>
    <div v-if="subscriber.status === 'Subscribing'" class="flex gap-3 items-center w-full">
        <Button
            type="button"
            size="small"
            class="w-full"
            @click="openDialog('switch_master')"
        >
            {{ $t('public.switch_master') }}
        </Button>

        <Button
            severity="secondary"
            rounded
            outlined
            size="small"
            class="py-3"
            @click="toggle"
        >
            <IconDots size="12"/>
        </Button>

        <!-- Menu -->
        <TieredMenu ref="menu" id="overlay_tmenu" :model="items" popup>
            <template #item="{ item, props }">
                <div
                    class="flex items-center gap-3 self-stretch"
                    v-bind="props.action"
                    :class="{ 'hidden': item.disabled }"
                >
                    <component :is="item.icon" class="w-5" />
                    <span class="font-medium">{{ $t(`public.${item.label}`) }}</span>
                </div>
            </template>
        </TieredMenu>

        <Dialog
            v-model:visible="visible"
            modal
            :header="$t(`public.${dialogType}`)"
            class="dialog-xs"
            :class="{
                'md:dialog-lg': dialogType === 'switch_master',
                'md:dialog-md': dialogType !== 'switch_master',
            }"
        >
            <template v-if="dialogType === 'switch_master'">
                <SwitchMaster
                    :strategyType="strategyType"
                    :subscriber="subscriber"
                    @update:visible="visible = $event"
                />
            </template>

            <template v-if="dialogType === 'stop_renewal'">
                <StopRenewSubscription
                    :strategyType="strategyType"
                    :subscriber="subscriber"
                    :subscription="subscriber.subscription"
                    @update:visible="visible = $event"
                />
            </template>

            <template v-if="dialogType === 'terminate'">
                <TerminateSubscription
                    :strategyType="strategyType"
                    :subscriber="subscriber"
                    :subscription="subscriber.subscription"
                    @update:visible="visible = $event"
                />
            </template>
        </Dialog>
    </div>
</template>
