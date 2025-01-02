<script setup>
import Button from "primevue/button";
import {ref} from "vue";
import {
    CreditCardAddIcon,
} from "@/Components/Icons/outline.jsx";
import {IconBan} from "@tabler/icons-vue"
import Dialog from "primevue/dialog";
import TopUpPamm from "@/Pages/Pamm/PammListing/TopUpPamm.vue";
import RevokePamm from "@/Pages/Pamm/PammListing/Partials/RevokePamm.vue";

const props = defineProps({
    strategyType: String,
    subscriber: Object,
    walletSel: Array,
})

const visible = ref(false);
const dialogType = ref('');

const openDialog = (type) => {
    visible.value = true;
    dialogType.value = type;
}
</script>

<template>
    <div v-if="subscriber.status === 'Active'" class="flex gap-3 items-center w-full">
        <Button
            v-if="subscriber.master.can_top_up"
            type="button"
            size="small"
            class="w-full gap-2"
            @click="openDialog('top_up')"
        >
            <CreditCardAddIcon class="w-5 h-5" />
            {{ $t('public.top_up') }}
        </Button>
        <Button
            v-if="subscriber.master.can_revoke"
            type="button"
            size="small"
            class="w-full gap-2"
            severity="danger"
            @click="openDialog('terminate')"
        >
            <IconBan size="20" />
            {{ $t('public.terminate') }}
        </Button>

        <Dialog
            v-model:visible="visible"
            modal
            :header="$t(`public.${dialogType}`)"
            class="dialog-xs md:dialog-md"
        >
            <template v-if="dialogType === 'top_up'">
                <TopUpPamm
                    :strategyType="strategyType"
                    :subscriber="subscriber"
                    :walletSel="walletSel"
                    @update:visible="visible = $event"
                />
            </template>

            <template v-if="dialogType === 'terminate'">
                <RevokePamm
                    :strategyType="strategyType"
                    :subscriber="subscriber"
                    @update:visible="visible = $event"
                />
            </template>
        </Dialog>
    </div>
</template>
