<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import {ref, watch} from "vue";
import {
    IconCircleXFilled,
    IconSearch,
} from "@tabler/icons-vue";
import ReferralChild from "@/Pages/Referral/ReferralChild.vue";
import InputText from "primevue/inputtext";
import debounce from "lodash/debounce.js";

const props = defineProps({
    root: Object,
});

const search = ref('');
const rootNode = ref(props.root);

const clearSearch = () => {
    search.value = '';
    rootNode.value = props.root;
}

const searchUser = async (keyword) => {
    try {
        if (keyword) {
            const response = await axios.get(`/referral/getTreeData?search=${keyword}`);
            if (response.data.success) {
                rootNode.value = response.data.data;
            } else {
                rootNode.value = props.root;
            }
        }
    } catch (e) {
        console.error('Search failed:', e);
        rootNode.value = props.root;
    }
};

watch(search, debounce(function() {
    searchUser(search.value);
}, 300));
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
            <div class="flex self-stretch w-full">
                <div class="relative w-full md:w-60">
                    <div class="absolute top-2/4 -mt-[9px] left-4 text-gray-400">
                        <IconSearch size="20" stroke-width="1.5"/>
                    </div>
                    <InputText
                        v-model="search"
                        :placeholder="$t('public.search')"
                        class="font-normal pl-12 w-full md:w-60"
                    />
                    <div
                        v-if="search"
                        class="absolute top-2/4 -mt-2 right-4 text-gray-300 hover:text-gray-400 select-none cursor-pointer"
                        @click="clearSearch"
                    >
                        <IconCircleXFilled size="16"/>
                    </div>
                </div>

            </div>

            <ReferralChild
                :node="rootNode"
            />
        </div>
    </AuthenticatedLayout>
</template>
