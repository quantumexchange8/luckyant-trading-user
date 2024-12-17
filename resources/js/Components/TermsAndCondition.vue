<script setup>
import Dialog from "primevue/dialog";
import {onMounted, ref, watch, onUnmounted} from "vue";
import {transactionFormat} from "@/Composables/index.js";

const props = defineProps({
    termsLabel: String,
    terms: Object,
    managementFee: Object,
})

const visible = ref(false);

// Create a ref to hold the current lang attribute value
const currentLang = ref(document.documentElement.lang)
const currentTerms = ref({})
const {formatAmount} = transactionFormat();

// Function to handle changes to the lang attribute
const handleLangChange = () => {
    currentLang.value = document.documentElement.lang
    // Handle diff locale terms
    if (props.terms) {
        currentTerms.value = props.terms[currentLang.value] || props.terms['en']
    }
}

watch(() => props.terms, (newTerms) => {
    if (newTerms) {
        handleLangChange() // Call the function once terms is loaded
    }
}, { immediate: true })

// Watch for changes to the lang attribute on the <html> element
onMounted(() => {
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.type === 'attributes' && mutation.attributeName === 'lang') {
                handleLangChange()
            }
        })
    })

    // Start observing the <html> element for attribute changes
    observer.observe(document.documentElement, { attributes: true })

    // Clean up the observer when the component is unmounted
    onUnmounted(() => {
        observer.disconnect()
    })
})
</script>

<template>
    <div
        v-if="termsLabel && terms"
        class="text-primary-500 font-medium no-underline hover:text-primary-600 select-none cursor-pointer capitalize"
        @click="visible = true"
    >
        {{ termsLabel }}
    </div>
    <div v-else class="font-medium select-none capitalize">
        {{ $t('public.terms_and_conditions') }}
    </div>
    <slot></slot>

    <Dialog
        v-model:visible="visible"
        modal
        :header="currentTerms.title"
        class="dialog-xs md:dialog-lg"
    >
        <div class="prose dark:text-white w-full" v-html="currentTerms.contents"></div>
        <div
            v-if="managementFee"
        >
            <div class="flex flex-col items-center gap-3 self-stretch mt-5">
                <!-- Table Header -->
                <div
                    class="flex justify-between items-center py-2 self-stretch border-b border-gray-200 bg-gray-100 dark:border-gray-700 dark:bg-gray-800">
                    <div class="flex items-center px-2 w-full text-gray-950 dark:text-white text-xs font-semibold uppercase">
                        {{ $t('public.days') }}
                    </div>
                    <div class="flex items-center px-2 w-full text-gray-950 dark:text-white text-xs font-semibold uppercase">
                        {{ $t('public.management_fee') }} (%)
                    </div>
                </div>
                <div class="flex flex-col items-center self-stretch max-h-[200px] overflow-y-auto">
                    <div
                        v-for="fee in managementFee"
                        class="flex justify-between gap-3 my-1 items-center self-stretch"
                    >
                        <!-- Days Input -->
                        <div class="flex flex-col items-start gap-1 w-full px-2">
                            {{ fee.penalty_days }}
                        </div>

                        <!-- Percentage Input -->
                        <div class="flex flex-col items-start gap-1 w-full">
                            {{ formatAmount(fee.penalty_percentage, 0) }}%
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Dialog>
</template>
