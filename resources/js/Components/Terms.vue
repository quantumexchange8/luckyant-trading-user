<script setup>
import { ref, defineProps } from 'vue';
import Modal from "@/Components/Modal.vue";
import { usePage } from "@inertiajs/vue3";

const props = defineProps({
    type : String,
});

const type = props.type; 
const termData = ref(null);

const fetchData = async () => {
    try {
        const response = await fetch(`/getTerms?type=${type}`);
        const data = await response.json();
        termData.value = data;
    } catch (error) {
        console.error('Error fetching terms data:', error);
    }
};

fetchData();

const tncModal = ref(false);

const openTncModal = () => {
    tncModal.value = true;
};

const closeModal = () => {
    tncModal.value = false;
};
</script>

<template>
    <!-- <span v-if="props.type == 'standard_learn_more' || props.type == 'staking_learn_more'" class="dark:text-white underline cursor-pointer dark:hover:text-gray-300" @click="openTncModal">
        {{ $t('public.learn_more') }}
    </span> -->
    <span v-if="props.type == 'standard_subscription' || props.type == 'staking_subscription' || props.type == 'buy_coin' || props.type == 'deposit' || props.type == 'swap' || props.type == 'withdrawal' || props.type == 'register'" class="font-semibold hover:text-primary-500 dark:text-white underline cursor-pointer dark:hover:text-gray-300" @click="openTncModal">
        {{ $t('public.terms_and_conditions') }}.
    </span>
    <!-- <span v-if="props.type == 'privacy_notice'" class="dark:text-white underline cursor-pointer dark:hover:text-gray-300" @click="openTncModal">
        {{ $t('public.privacy_notice') }}
    </span> -->

    <Modal v-if="termData" :show="tncModal" :title="termData.title" @close="closeModal" max-width="2xl">
        <div v-html="termData.contents" class="font-medium text-black prose max-w-none text-xs"></div>
    </Modal>
</template>
