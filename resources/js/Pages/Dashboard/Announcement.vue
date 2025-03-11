<script setup>
import Dialog from "primevue/dialog";
import {onMounted, ref, watchEffect} from "vue";
import Carousel from 'primevue/carousel';
import Image from 'primevue/image';
import {usePage} from "@inertiajs/vue3";

const props = defineProps({
    announcements: Object,
});

const isFirstLogin = ref(usePage().props.firstTimeLogin);
const visible = ref(false);

const setValueInSession = async (value) => {
    try {
        await axios.post('/update_session', { firstTimeLogin: value });
        isFirstLogin.value = value;
        console.log('Session value updated:', isFirstLogin.value);
    } catch (error) {
        console.error('Error updating session value:', error);
    }
};

onMounted(() => {
    if (isFirstLogin.value === 1 && props.announcements) {
        visible.value = true;
        setValueInSession(0);
    }
});

const handleRedirect = (value) => {
    if (value) {
        window.location.href = value;
    }
}
</script>

<template>
    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.announcement')"
        class="dialog-xs md:dialog-lg"
    >
        <Carousel
            :value="announcements"
            circular
            :autoplayInterval="3000"
        >
            <template #item="slotProps">
                <div class="flex flex-col gap-5 items-center self-stretch w-full">
                    <div
                        v-if="slotProps.data.media.length > 0"
                        :class="[
                            'w-full',
                            {
                                'select-none cursor-pointer': slotProps.data.url,
                            }
                        ]"
                        @click="handleRedirect(slotProps.data.url)"
                    >
                        <Image
                            :src="slotProps.data.media[0].original_url"
                            alt="Image"
                            imageClass="w-full object-contain rounded"
                        />
                    </div>

                    <!-- Subject -->
                    <div class="text-base md:text-xl font-semibold w-full text-gray-950 dark:text-white">
                        {{ slotProps.data.subject }}
                    </div>

                    <!-- Content -->
                    <div
                        class="prose dark:prose-invert w-full text-left"
                        v-html="slotProps.data.details"
                    ></div>

                    <!-- Url -->
                    <div
                        v-if="slotProps.data.url"
                        class="text-xs text-gray-500 w-full hover:text-primary-500 select-none cursor-pointer"
                    >
                        {{ $t('public.to') }}: {{ slotProps.data.url }}
                    </div>
                </div>
            </template>
        </Carousel>
    </Dialog>
</template>
