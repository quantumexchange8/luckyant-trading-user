<script setup>
import {ref, watch} from 'vue';
import Datepicker from 'primevue/datepicker';
import Select from "primevue/select";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import {transactionFormat} from '@/Composables/index.js';
import Dialog from 'primevue/dialog';
import StatusBadge from '@/Components/StatusBadge.vue';
import {trans} from "laravel-vue-i18n";
import Tag from 'primevue/tag';
import dayjs from 'dayjs';
import Loading from "@/Components/Loading.vue";
import {XCircleIcon} from "@/Components/Icons/outline.jsx";

const {formatType, formatDateTime, formatAmount} = transactionFormat();

const props = defineProps({
    account: Object,
});

const transactions = ref(null);
const selectedDate = ref();
const selectedOption = ref('all');
const loading = ref(false);
const visible = ref(false);
const data = ref({});
const tooltipText = ref('copy')

const transferOptions = [
    {name: trans('public.all'), value: 'all'},
    {name: trans('public.balance_in'), value: 'balance_in'},
    {name: trans('public.balance_out'), value: 'balance_out'},
    {name: trans('public.internal_transfer'), value: 'internal_transfer'},
    {name: trans('public.management_fee'), value: 'management_fee'},
    {name: trans('public.top_up'), value: 'top_up'}
];

const getAccountReport = async (filterDate = null, selectedOption = null) => {
    if (loading.value) return;
    loading.value = true;

    try {
        let url = `/account_info/getAccountReport?meta_login=${props.account.meta_login}`;

        if (filterDate) {
            const [startDate, endDate] = filterDate;
            url += `&startDate=${dayjs(startDate).format('YYYY-MM-DD')}&endDate=${dayjs(endDate).format('YYYY-MM-DD')}`;
        }

        if (selectedOption) {
            url += `&type=${selectedOption}`;
        }

        const response = await axios.get(url);
        transactions.value = response.data;
    } catch (error) {
        console.error('Error fetching account report:', error);
    } finally {
        loading.value = false;
    }
};

getAccountReport();

const today = dayjs();
const ninetyDaysAgo = today.subtract(90, 'day');

selectedDate.value = [ninetyDaysAgo.toDate(), today.toDate()];

watch(selectedDate, (newDateRange) => {
    if (Array.isArray(newDateRange)) {
        const [startDate, endDate] = newDateRange;

        if (startDate && endDate) {
            getAccountReport([startDate, endDate], selectedOption.value);
        } else if (startDate || endDate) {
            getAccountReport([startDate || endDate, endDate || startDate], selectedOption.value);
        } else {
            getAccountReport([], selectedOption.value);
        }
    } else {
        console.warn('Invalid date range format:', newDateRange);
    }
});

watch(selectedOption, (newOption) => {
    getAccountReport(selectedDate.value, newOption);
});

const clearDate = () => {
    selectedDate.value = null;
};

const openDialog = (rowData) => {
    visible.value = true;
    data.value = rowData;
};

function copyToClipboard(text) {
    const textToCopy = text;

    const textArea = document.createElement('textarea');
    document.body.appendChild(textArea);

    textArea.value = textToCopy;
    textArea.select();

    try {
        const successful = document.execCommand('copy');

        tooltipText.value = 'copied';
        setTimeout(() => {
            tooltipText.value = 'copy';
        }, 1500);
    } catch (err) {
        console.error('Copy to clipboard failed:', err);
    }

    document.body.removeChild(textArea);
}
</script>

<template>
    <DataTable
        :value="transactions"
        paginator
        removableSort
        selectionMode="single"
        :rows="10"
        :rowsPerPageOptions="[10, 20, 50, 100]"
        tableStyle="md:min-width: 50rem"
        paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
        currentPageReportTemplate="Showing {first} to {last} of {totalRecords} entries"
        @row-click="(event) => openDialog(event.data)"
        :loading="loading"
    >
        <template #header>
            <div class="grid grid-cols-1 md:grid-cols-2 w-full gap-3">
                <div class="relative w-full">
                    <Datepicker
                        v-model="selectedDate"
                        selectionMode="range"
                        dateFormat="yy/mm/dd"
                        showIcon
                        iconDisplay="input"
                        :placeholder="$t('public.date_placeholder')"
                        class="w-full font-normal"
                    />
                    <div
                        v-if="selectedDate && selectedDate.length > 0"
                        class="absolute top-2/4 -mt-3 right-2 text-gray-400 select-none cursor-pointer bg-white dark:bg-surface-950"
                        @click="clearDate"
                    >
                        <XCircleIcon class="w-5"/>
                    </div>
                </div>
                <Select
                    v-model="selectedOption"
                    :options="transferOptions"
                    optionLabel="name"
                    optionValue="value"
                    :placeholder="$t('public.transaction_type_option_placeholder')"
                    class="w-full font-normal"
                    scroll-height="236px"
                />
            </div>
        </template>
        <template #empty>
            <span class="font-semibold dark:text-white">{{ $t('public.no_history') }}</span>
        </template>
        <template #loading>
            <div class="flex flex-col gap-2 items-center justify-center">
                <Loading/>
                <span class="text-sm text-gray-700">{{ $t('public.loading') }}</span>
            </div>
        </template>
        <Column
            field="created_at"
            sortable
            :header="$t('public.date')"
            class="hidden md:table-cell"
        >
            <template #body="slotProps">
                {{ formatDateTime(slotProps.data.created_at) }}
            </template>
        </Column>
        <Column
            :header="$t('public.type')"
            class="hidden md:table-cell"
        >
            <template #body="slotProps">
                <span>{{ $t(`public.${formatType(slotProps.data.transaction_type).toLowerCase().replace(/\s+/g, '_')}`) }}</span>
            </template>
        </Column>
        <Column
            :header="$t('public.remarks')"
            class="hidden md:table-cell"
        >
            <template #body="slotProps">
                <div v-if="['balance_in', 'top_up', 'internal_transfer'].includes(slotProps.data.transaction_type)">
                    {{ $t('public.to') }} {{ slotProps.data.to_meta_login }}
                </div>
                <div v-else>
                    {{ $t('public.from') }} {{ slotProps.data.from_meta_login }}
                </div>
            </template>
        </Column>
        <Column
            field="transaction_amount"
            sortable
            :header="$t('public.amount') + ' ($)'"
            class="hidden md:table-cell"
        >
            <template #body="slotProps">
                <div
                    :class="{
                            'text-success-500': slotProps.data.to_meta_login,
                            'text-error-500': slotProps.data.from_meta_login,
                        }"
                >
                    {{ formatAmount(slotProps.data.transaction_amount > 0 ? slotProps.data.transaction_amount : 0) }}
                </div>
            </template>
        </Column>
        <Column class="md:hidden">
            <template #body="slotProps">
                <div class="flex items-center justify-between">
                    <div class="flex flex-col items-start gap-1 flex-grow">
                        <span class="overflow-hidden text-gray-950 text-ellipsis text-sm font-semibold">
                            {{ slotProps.data.transaction_type }}
                        </span>
                        <span class="text-gray-500 text-xs">
                            {{ formatDateTime(slotProps.data.created_at) }}
                        </span>
                    </div>
                    <div
                        class="overflow-hidden text-right text-ellipsis font-semibold"
                        :class="{
                            'text-success-500': slotProps.data.to_meta_login,
                            'text-error-500': slotProps.data.from_meta_login,
                        }"
                    >
                        {{ formatAmount(slotProps.data.transaction_amount > 0 ? slotProps.data.transaction_amount : 0) }}
                    </div>
                </div>
            </template>
        </Column>
    </DataTable>
</template>
