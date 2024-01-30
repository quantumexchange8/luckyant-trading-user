<script setup>
import {TailwindPagination} from "laravel-vue-pagination";
import {ref} from "vue";
import {transactionFormat} from "@/Composables/index.js";

const props = defineProps({
    category: String,
})

const tableData = ref({data: []});
const isLoading = ref(false);
const { getChannelName, formatDate, formatAmount, getStatusClass } = transactionFormat();
const getResults = async (page = 1, search = '', dateRange) => {
    try {
        let url = `/transaction/getTransactionData/${props.category}?page=${page}`;

        if (search) {
            url += `&search=${search}`;
        }

        if (dateRange) {
            if (dateRange.length === 2) {
                const formattedDates = dateRange.map(date => `date[]=${date}`).join('&');
                url += `&${formattedDates}`;
            }
        }

        const response = await axios.get(url);
        tableData.value = response.data;
    } catch (error) {
        console.error(error);
    } finally {
        isLoading.value = false;
    }
}

getResults();

const paginationClass = [
    'bg-transparent border-0 text-gray-500'
];

const paginationActiveClass = [
    'dark:bg-transparent border-0 text-primary-500'
];
</script>

<template>
    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
        <thead class="text-sm font-bold text-gray-700 uppercase bg-gray-50 dark:bg-transparent dark:text-white">
        <tr>
            <th scope="col" class="p-3">
                {{ $t('public.Date')}}
            </th>
            <th scope="col" class="p-3">
                {{ $t('public.Deposit Method')}}
            </th>
            <th scope="col" class="p-3">
                {{ $t('public.Trading Account No')}}
            </th>
            <th scope="col" class="p-3">
                {{ $t('public.Amount')}}
            </th>
            <th scope="col" class="p-3">
                {{ $t('public.Status')}}
            </th>
        </tr>
        </thead>
        <tbody>
            <tr
                v-for="data in tableData.data"
                class="bg-white odd:dark:bg-transparent even:dark:bg-dark-eval-0 text-xs font-thin text-gray-900 dark:text-white"
            >
                <td class="p-3">
                    {{ formatDate(data.created_at) }}
                </td>
                <td class="p-3">
                    <span v-if="data.channel">{{ $t('public.' + getChannelName(data.channel)) }}</span>
                    <span v-else>-</span>
                </td>
                <td class="p-3">
                    {{ data.to ?? '-' }}
                </td>
                <td class="p-3">
                    $ {{ formatAmount(data.amount) }}
                </td>
                <td class="p-3">
                    {{ $t('public.' + data.status) }}
                </td>
            </tr>
        </tbody>
    </table>
    <div class="flex justify-end mt-4">
        <TailwindPagination
            :item-classes=paginationClass
            :active-classes=paginationActiveClass
            :data="tableData"
        />
    </div>
</template>
