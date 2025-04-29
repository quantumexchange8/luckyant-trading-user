<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import Tree from 'primevue/tree';
import { onMounted, ref } from "vue";
import { IconLoader } from "@tabler/icons-vue";

const nodes = ref([]);
const loading = ref(false);

// Initial nodes with loading indicator
const getInitialNodes = () => ([
    { key: '0', label: 'Node 0', leaf: false, loading: true },
    { key: '1', label: 'Node 1', leaf: false, loading: true },
    { key: '2', label: 'Node 2', leaf: false, loading: true },
]);

onMounted(() => {
    loading.value = true;
    nodes.value = getInitialNodes();

    // Simulate async data load
    setTimeout(() => {
        nodes.value.forEach(node => node.loading = false);
        loading.value = false;
    }, 2000);
});

// Lazy load child nodes on expand
const onNodeExpand = (node) => {
    if (!node.children) {
        node.loading = true;

        setTimeout(() => {
            node.children = Array.from({ length: 3 }, (_, i) => ({
                key: `${node.key}-${i}`,
                label: `Lazy ${node.label}-${i}`
            }));
            node.loading = false;
        }, 500);
    }
};
</script>

<template>
    <AuthenticatedLayout :title="$t('public.referral_tree')">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    {{ $t('public.referral_tree') }}
                </h2>
            </div>
        </template>

        <div class="flex flex-col gap-5 items-center self-stretch">
            <Tree
                :value="nodes"
                @node-expand="onNodeExpand"
                loadingMode="icon"
                class="w-full md:w-[30rem]"
            >
                <template #loadingicon>
                    <div class="animate-spin">
                        <IconLoader stroke-width="1.5" />
                    </div>
                </template>
            </Tree>
        </div>
    </AuthenticatedLayout>
</template>
