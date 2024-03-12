<script setup>
import Input from "@/Components/Input.vue";
import Label from "@/Components/Label.vue";
import InputError from "@/Components/InputError.vue";
import BaseListbox from "@/Components/BaseListbox.vue";
import { ref, watch } from "vue";
import debounce from "lodash/debounce.js";
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue'

const props = defineProps({
    countries: Array,
})

const selectedCountry = ref(null)
const countries = ref({data: []});

watch(
    [selectedCountry],
    debounce(([newValue]) => {
        if(newValue) {
            const selectedCountryObject = props.countries.find(country => country.value === newValue); // Find the country object based on the selected value
            if(selectedCountryObject) {
                const selectedCountryId = selectedCountryObject.id; // Extracting the id from the selected country object
                getResults(1, selectedCountryId);
            }
        }
    }, 300)
);

const getResults = async (page = 1, countryId = '') => {
    // historyLoading.value = true
    try {
        let url = `/transaction/getPaymentDetails?page=${page}`;
        
        if (countryId) {
            url += `&countryId=${countryId}`;
        }

        const response = await axios.get(url);
        countries.value = response.data;
    } catch (error) {
        console.error(error.response.data);
    } finally {
        historyLoading.value = false

    }
}

getResults()

</script>

<template>
    <div>
        <div v-if="selectedCountry == null">
            <Label class="text-sm dark:text-white w-full md:w-1/4 pt-0.5" for="country" :value="$t('public.country')" />
            <div class="flex flex-col w-full">
                <BaseListbox
                    :options="props.countries"
                    v-model="selectedCountry"
                />
            </div>
        </div>

        
            
    </div>

    <!-- <div class="p-5 mt-3 bg-gray-100 dark:bg-gray-600 rounded-lg">
        <div class="flex flex-col items-start gap-3 self-stretch">
            <div class="text-lg font-semibold">
                Payment Information
            </div>
            <div class="flex items-center justify-between gap-2 self-stretch">
                <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                    Payment Method
                </div>
                <div class="text-base text-gray-800 dark:text-white font-semibold">
                    {{ paymentDetails.payment_method }}
                </div>
            </div>
            <div class="flex items-center justify-between gap-2 self-stretch">
                <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                    {{ paymentDetails.payment_method === 'Bank' ? 'Bank Name' : 'Tether' }}
                </div>
                <div class="text-base text-gray-800 dark:text-white font-semibold">
                    {{ paymentDetails.payment_platform_name }}
                </div>
            </div>
            <div class="flex items-center justify-between gap-2 self-stretch">
                <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                    {{ paymentDetails.payment_method === 'Bank' ? 'Account No' : 'Wallet Address' }}
                </div>
                <div class="text-base text-gray-800 dark:text-white font-semibold">
                    {{ paymentDetails.account_no }}
                </div>
            </div>
            <div class="flex items-center justify-between gap-2 self-stretch">
                <div class="font-semibold text-sm text-gray-500 dark:text-gray-400">
                    {{ paymentDetails.payment_method === 'Bank' ? 'Account Name' : 'Wallet Name' }}
                </div>
                <div class="text-base text-gray-800 dark:text-white font-semibold">
                    {{ paymentDetails.payment_account_name }}
                </div>
            </div>
        </div>
    </div> -->
</template>