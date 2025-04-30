<script setup>
import { ref } from 'vue';
import { useLangObserver } from "@/Composables/localeObserver.js";
import {transactionFormat} from "@/Composables/index.js";
import Divider from 'primevue/divider';
import Tag from "primevue/tag";
import Button from "primevue/button";
import {
    IconLoader,
    IconPlus,
    IconMinus,
} from "@tabler/icons-vue"
import Dialog from "primevue/dialog";
import dayjs from "dayjs";

const props = defineProps({
    node: Object,
    depth: {
        type: Number,
        default: 0
    }
})

const { locale } = useLangObserver();
const {formatAmount} = transactionFormat();

const loadChildren = async (node) => {
    node.loading = true;
    const { data } = await axios.get(`/referral/getTreeData?child_id=${node.id}`);
    node.children = data.data;
    node.expanded = true;
    node.loading = false;
};

const nodeClicked = (node) => {
    if (!node.children) {
        loadChildren(node);
    } else {
        node.expanded = !node.expanded;
    }
};

const visible = ref(false);
const nodeData = ref(null);

const openDialog = (data) => {
    nodeData.value = data;
    visible.value = true;
}
</script>

<template>
    <div class="flex flex-col gap-3 self-stretch">
        <div :style="{'margin-left': `${depth * 50}px`}">
            <div class="flex items-center gap-2">
                <div class="flex-none">
                    <div v-if="node.direct_affiliate > 0">
                        <template v-if="node.expanded">
                            <Button
                                type="button"
                                severity="secondary"
                                rounded
                                class="!w-6 !h-6"
                                @click="nodeClicked(node)"
                            >
                                <template #icon>
                                    <IconMinus size="16" stroke-width="1.5" />
                                </template>
                            </Button>
                        </template>
                        <template v-else>
                            <Button
                                v-if="node.loading"
                                type="button"
                                severity="secondary"
                                rounded
                                class="!w-6 !h-6"
                                disabled
                            >
                                <template #icon>
                                    <div class="animate-spin">
                                        <IconLoader size="16" stroke-width="1.5" />
                                    </div>
                                </template>
                            </Button>

                            <Button
                                v-else
                                type="button"
                                rounded
                                class="!w-6 !h-6"
                                @click="nodeClicked(node)"
                            >
                                <template #icon>
                                    <IconPlus size="16" stroke-width="1.5" />
                                </template>
                            </Button>
                        </template>
                    </div>
                </div>

                <div
                    class="flex shrink-0 items-center gap-3 self-stretch p-2.5 text-gray-800 border border-gray-300 shadow-lg dark:border-gray-700 rounded-lg bg-white dark:bg-gray-900 hover:cursor-pointer hover:border-primary-500 dark:hover:border-primary-600 dark:hover:bg-black/20"
                    @click="openDialog(node)"
                >
                    <div class="flex items-center gap-3 self-stretch">
                        <img class="object-cover w-10 h-10 rounded-full"
                             :src="node.profile_photo || 'https://img.freepik.com/free-icon/user_318-159711.jpg'"
                             alt="userPic" />
                        <div class="flex flex-col items-start max-w-40">
                            <div class="text-sm font-semibold dark:text-white truncate">
                                {{ node.username }}
                            </div>
                            <Tag severity="secondary">
                                <span class="truncate max-w-36">{{ locale !== 'en'
                                    ? JSON.parse(node.rank ?? "{}")[locale] ?? JSON.parse(node.rank ?? "{}")['en']
                                    : JSON.parse(node.rank ?? "{}")['en'] }}</span>
                            </Tag>
                        </div>
                        <div class="flex flex-col items-center py-1.5 px-2 rounded-md bg-primary-100 dark:bg-gray-800">
                            <div class="text-lg font-medium text-primary-700 dark:text-primary-200 leading-none">
                                {{ node.level }}
                            </div>
                            <div class="text-xs dark:text-gray-300 text-nowrap">
                                {{ $t('public.level') }}
                            </div>
                        </div>
                    </div>

                    <Divider layout="vertical" />

                    <div class="flex items-center gap-3 self-stretch">
                        <div class="flex flex-col items-center min-w-14">
                            <div class="font-medium dark:text-white">
                                {{ formatAmount(node.direct_affiliate, 0) }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $t('public.direct_clients') }}
                            </div>
                        </div>
                        <div class="flex flex-col items-center min-w-14">
                            <div class="font-medium dark:text-white">
                                {{ formatAmount(node.total_affiliate, 0) }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $t('public.total_clients') }}
                            </div>
                        </div>
                        <div class="flex flex-col items-center min-w-14">
                            <div class="font-medium dark:text-white">
                                ${{ formatAmount(node.self_deposit) }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $t('public.total_deposit') }}
                            </div>
                        </div>
                        <div class="flex flex-col items-center min-w-14">
                            <div class="font-medium dark:text-white">
                                ${{ formatAmount(node.total_group_deposit) }}
                            </div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $t('public.total_group_deposit') }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <ReferralChild
            v-if="node.expanded"
            v-for="child in node.children"
            :key="child.id"
            :node="child"
            :depth="depth + 1"
        />
    </div>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.view_details')"
        class="dialog-xs md:dialog-md"
    >
        <div class="flex items-center gap-3 self-stretch w-full pb-4 border-b dark:border-gray-600">
            <div class="flex flex-col items-start w-full">
                <div class="flex items-center gap-3 self-stretch">
                    <img class="object-cover w-10 h-10 rounded-full"
                         :src="nodeData.profile_photo || 'https://img.freepik.com/free-icon/user_318-159711.jpg'"
                         alt="userPic" />
                    <div class="flex flex-col items-start">
                        <div class="text-sm font-semibold dark:text-white truncate">
                            {{ nodeData.username }}
                        </div>
                        <Tag severity="secondary">
                                <span class="truncate">{{ locale !== 'en'
                                    ? JSON.parse(nodeData.rank ?? "{}")[locale] ?? JSON.parse(nodeData.rank ?? "{}")['en']
                                    : JSON.parse(nodeData.rank ?? "{}")['en'] }}</span>
                        </Tag>
                    </div>
                </div>
            </div>
            <div class="flex flex-col items-center py-1.5 px-2 rounded-md bg-primary-100 dark:bg-gray-800">
                <div class="text-lg font-medium text-primary-700 dark:text-primary-200 leading-none">
                    {{ nodeData.level }}
                </div>
                <div class="text-xs dark:text-gray-300 text-nowrap">
                    {{ $t('public.level') }}
                </div>
            </div>
        </div>

        <div class="flex flex-col items-center gap-4 divide-y dark:divide-gray-600 self-stretch">
            <div class="flex flex-col gap-3 items-start w-full pt-4">
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.direct_clients') }}
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        {{ formatAmount(nodeData.direct_affiliate, 0) }}
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.total_clients') }}
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        {{ formatAmount(nodeData.total_affiliate, 0) }}
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.total_deposit') }}
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        ${{ formatAmount(nodeData.self_deposit) }}
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.total_group_deposit') }}
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        ${{ formatAmount(nodeData.total_group_deposit) }}
                    </div>
                </div>
            </div>
        </div>
    </Dialog>
</template>
