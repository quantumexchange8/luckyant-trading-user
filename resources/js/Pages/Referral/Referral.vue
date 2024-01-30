<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import Input from "@/Components/Input.vue";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import Button from "@/Components/Button.vue";
import ReferralChild from "@/Pages/Referral/ReferralChild.vue";
import {ref, watch} from "vue";
import debounce from "lodash/debounce.js";
import {router} from "@inertiajs/vue3";
import Loading from "@/Components/Loading.vue";
import {SearchIcon} from "@heroicons/vue/outline";

function nodeWasClicked(node) {
    console.log('Last Node');
}

let search = ref(null);
let root = ref({});
const isLoading = ref(false);

watch(search, debounce(function() {
    isLoading.value = true;
    getResults(search.value);
}, 300));

const getResults = async (search = '') => {
    isLoading.value = true;
    try {
        let url = `/referral/getTreeData`;

        if (search) {
            url += `?search=${search}`;
        }

        const response = await axios.get(url);
        root.value = response.data;
    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false;
    }
}

getResults();

function resetField() {
    const url = new URL(window.location.href);
    url.searchParams.delete('search');

    // Navigate to the updated URL without the search parameter
    window.location.href = url.href;
}

function clearField() {
    search.value = '';
}

function handleKeyDown(event) {
    if (event.keyCode === 27) {
        clearField();
    }
}

</script>

<template>
    <AuthenticatedLayout :title="$t('public.Network Tree')">
        <template #header>
            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    {{ $t('public.Member Tree') }}
                </h2>
            </div>
        </template>

        <div class="mb-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="col-span-2 flex justify-between">
                <div class="relative w-full mr-4">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m19 19-4-4m0-7A7 7 0 1 1 1 8a7 7 0 0 1 14 0Z"/>
                        </svg>
                    </div>
                    <InputIconWrapper>
                        <template #icon>
                            <SearchIcon aria-hidden="true" class="w-5 h-5" />
                        </template>
                        <Input
                            withIcon
                            id="search"
                            type="text"
                            class="block border-transparent w-full md:w-1/3"
                            :placeholder="$t('public.Search Name/Email.')"
                            v-model="search"
                        />
                    </InputIconWrapper>
                </div>
            </div>
        </div>

        <div v-if="isLoading" class="w-full flex justify-center mt-8">
            <Loading />
        </div>
        <ReferralChild
            v-else
            :node="root"
            @onClick="nodeWasClicked"
        />


    </AuthenticatedLayout>
</template>
