<script setup>
import Card from "primevue/card"
import Column from "primevue/column";
import DataTable from "primevue/datatable";
import Input from "@/Components/Input.vue";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import {XCircleIcon} from "@heroicons/vue/outline";
import Loading from "@/Components/Loading.vue";
import {onMounted, ref, watch, watchEffect} from "vue";
import {transactionFormat} from "@/Composables/index.js";
import {FilterMatchMode} from "@primevue/core/api";
import {usePage} from "@inertiajs/vue3";
import Button from "primevue/button";
import {
    XIcon,
    SearchLgIcon
} from "@/Components/Icons/outline.jsx"
import dayjs from "dayjs";
import Popover from "primevue/popover";
import Select from "primevue/select";
import DatePicker from "primevue/datepicker"
import debounce from "lodash/debounce.js";
import { IconAdjustments} from "@tabler/icons-vue";
import Tag from "primevue/tag";
import NoData from "@/Components/NoData.vue";

const isLoading = ref(false);
const dt = ref(null);
const rebates = ref([]);
const exportTable = ref('no');
const {formatAmount} = transactionFormat();
const totalRecords = ref(0);
const first = ref(0);
const totalRebateAmount = ref();
const totalAffiliateRebate = ref();
const totalPersonalRebate = ref();
const totalAffiliateLot = ref();
const totalPersonalLot = ref();
const totalTradeLots = ref();

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    type: { value: null, matchMode: FilterMatchMode.EQUALS },
    start_date: { value: null, matchMode: FilterMatchMode.EQUALS },
    end_date: { value: null, matchMode: FilterMatchMode.EQUALS },
    fund_type: { value: null, matchMode: FilterMatchMode.EQUALS },
    status: { value: null, matchMode: FilterMatchMode.EQUALS },
});

const lazyParams = ref({});

const loadLazyData = (event) => {
    isLoading.value = true;

    lazyParams.value = { ...lazyParams.value, first: event?.first || first.value };

    try {
        setTimeout(async () => {
            const params = {
                page: JSON.stringify(event?.page + 1),
                sortField: event?.sortField,
                sortOrder: event?.sortOrder,
                include: [],
                lazyEvent: JSON.stringify(lazyParams.value)
            };

            const url = route('report.getTradeRebateHistories', params);
            const response = await fetch(url);
            const results = await response.json();

            rebates.value = results?.data?.data;
            totalRecords.value = results?.data?.total;
            totalRebateAmount.value = results?.totalRebateAmount;
            totalAffiliateRebate.value = results?.totalAffiliateRebate;
            totalPersonalRebate.value = results?.totalPersonalRebate;
            totalAffiliateLot.value = results?.totalAffiliateLot;
            totalPersonalLot.value = results?.totalPersonalLot;
            totalTradeLots.value = results?.totalTradeLots;
            isLoading.value = false;
        }, 100);
    }  catch (e) {
        rebates.value = [];
        totalRecords.value = 0;
        isLoading.value = false;
    }
};
const onPage = (event) => {
    lazyParams.value = event;
    loadLazyData(event);
};
const onSort = (event) => {
    lazyParams.value = event;
    loadLazyData(event);
};
const onFilter = (event) => {
    lazyParams.value.filters = filters.value ;
    loadLazyData(event);
};

const op = ref();
const toggle = (event) => {
    op.value.toggle(event);
}

const selectedDate = ref([]);

const rebateTypes = ref([
    'affiliate',
    'personal',
]);

const clearJoinDate = () => {
    selectedDate.value = [];
}

watch(selectedDate, (newDateRange) => {
    if (Array.isArray(newDateRange)) {
        const [startDate, endDate] = newDateRange;
        filters.value['start_date'].value = startDate;
        filters.value['end_date'].value = endDate;

        if (startDate !== null && endDate !== null) {
            loadLazyData();
        }
    } else {
        console.warn('Invalid date range format:', newDateRange);
    }
})

onMounted(() => {
    lazyParams.value = {
        first: dt.value.first,
        rows: dt.value.rows,
        sortField: null,
        sortOrder: null,
        filters: filters.value
    };

    loadLazyData();
});

watch(
    filters.value['global'],
    debounce(() => {
        loadLazyData();
    }, 300)
);

watch([filters.value['type'], filters.value['fund_type'], filters.value['status']], () => {
    loadLazyData()
});

const clearAll = () => {
    filters.value['global'].value = null;
    filters.value['type'].value = null;
    filters.value['start_date'].value = null;
    filters.value['end_date'].value = null;

    selectedDate.value = [];
};

const clearFilterGlobal = () => {
    filters.value['global'].value = null;
}

watchEffect(() => {
    if (usePage().props.toast !== null) {
        loadLazyData();
    }
});

const getSeverity = (status) => {
    switch (status) {
        case 'Rejected':
            return 'danger';

        case 'Approved':
            return 'success';

        case 'Processing':
            return 'info';
    }
}

const emit = defineEmits(['update-totals']);

watch([totalRebateAmount, totalAffiliateRebate, totalPersonalRebate], () => {
    emit('update-totals', {
        totalRebateAmount: totalRebateAmount.value,
        totalAffiliateRebate: totalAffiliateRebate.value,
        totalPersonalRebate: totalPersonalRebate.value,
        totalAffiliateLot: totalAffiliateLot.value,
        totalPersonalLot: totalPersonalLot.value,
        totalTradeLots: totalTradeLots.value,
    });
});

const exportStatus = ref(false);

const exportReport = () => {
    exportStatus.value = true;
    isLoading.value = true;

    lazyParams.value = { ...lazyParams.value, first: event?.first || first.value };

    const params = {
        page: JSON.stringify(event?.page + 1),
        sortField: event?.sortField,
        sortOrder: event?.sortOrder,
        include: [],
        lazyEvent: JSON.stringify(lazyParams.value),
        exportStatus: true,
    };

    const url = route('transaction.getTransactionHistory', params);  // Construct the export URL

    try {
        // Send the request to the backend to trigger the export
        window.location.href = url;  // This will trigger the download directly
    } catch (e) {
        console.error('Error occurred during export:', e);  // Log the error if any
    } finally {
        isLoading.value = false;  // Reset loading state
        exportStatus.value = false;  // Reset export status
    }
};
</script>

<template>
    <Card class="w-full">
        <template #content>
            <div
                class="w-full"
            >
                <DataTable
                    :value="rebates"
                    :rowsPerPageOptions="[10, 20, 50, 100]"
                    lazy
                    paginator
                    removableSort
                    paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                    :currentPageReportTemplate="$t('public.paginator_caption')"
                    :first="first"
                    :rows="10"
                    v-model:filters="filters"
                    ref="dt"
                    dataKey="id"
                    :totalRecords="totalRecords"
                    :loading="isLoading"
                    @page="onPage($event)"
                    @sort="onSort($event)"
                    @filter="onFilter($event)"
                    :globalFilterFields="['user.name', 'user.email', 'user.username', 'upline_meta_login', 'meta_login']"
                >
                    <template #header>
                        <div class="flex flex-col md:flex-row gap-3 items-center self-stretch md:pb-5">
                            <div class="relative w-full md:w-60">
                                <InputIconWrapper class="md:col-span-2">
                                    <template #icon>
                                        <SearchLgIcon aria-hidden="true" class="w-5 h-5" />
                                    </template>
                                    <Input
                                        withIcon
                                        id="search"
                                        type="text"
                                        class="block w-full"
                                        placeholder="Search"
                                        v-model="filters['global'].value"
                                    />
                                </InputIconWrapper>
                                <div
                                    v-if="filters['global'].value !== null"
                                    class="absolute top-2/4 -mt-2 right-4 text-gray-300 hover:text-gray-400 select-none cursor-pointer"
                                    @click="clearFilterGlobal"
                                >
                                    <XCircleIcon aria-hidden="true" class="w-4 h-4"/>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 w-full gap-3">
                                <Button
                                    class="w-full md:w-28 flex gap-2"
                                    severity="secondary"
                                    outlined
                                    @click="toggle"
                                >
                                    <IconAdjustments class="w-4 h-4" />
                                    {{ $t('public.filter') }}
                                </Button>
<!--                                <div class="w-full flex justify-end">-->
<!--                                    <Button-->
<!--                                        class="w-full md:w-28 flex gap-2"-->
<!--                                        severity="secondary"-->
<!--                                        @click="exportReport"-->
<!--                                        :disabled="exportTable==='yes'"-->
<!--                                    >-->
<!--                                        <CloudDownloadIcon class="w-4 h-4" />-->
<!--                                        Export-->
<!--                                    </Button>-->
<!--                                </div>-->
                            </div>
                        </div>
                    </template>
                    <template #empty>
                        <NoData />
                    </template>
                    <template #loading>
                        <div class="flex flex-col gap-2 items-center justify-center">
                            <Loading />
                            <span v-if="exportTable === 'no'" class="text-sm text-gray-700 dark:text-gray-300">{{ $t('public.loading') }}</span>
                            <span v-else class="text-sm text-gray-700 dark:text-gray-300">Exporting Report</span>
                        </div>
                    </template>
                    <template v-if="rebates?.length > 0">
                        <Column
                            field="created_at"
                            sortable
                            class="table-cell min-w-36"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.date') }}</span>
                            </template>
                            <template #body="slotProps">
                                <span class="uppercase">{{ dayjs(slotProps.data.created_at).format('DD/MM/YYYY HH:mm:ss') }}</span>
                            </template>
                        </Column>
                        <Column
                            field="user.username"
                            class="table-cell"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.username') }}</span>
                            </template>
                            <template #body="slotProps">
                                <div class="flex items-center gap-2">
                                    <div v-if="slotProps.data.of_user" class="flex">
                                        {{ slotProps.data.of_user.username }}
                                    </div>
                                    <div v-else>
                                        -
                                    </div>
                                </div>
                            </template>
                        </Column>
                        <Column
                            field="account"
                            class="table-cell"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.account_number') }}</span>
                            </template>
                            <template #body="slotProps">
                                {{ slotProps.data.meta_login }}
                            </template>
                        </Column>
                        <Column
                            field="volume"
                            sortable
                            class="table-cell"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.volume') }}</span>
                            </template>
                            <template #body="slotProps">
                                {{ formatAmount(slotProps.data.volume) }}
                            </template>
                        </Column>
                        <Column
                            field="rebate"
                            sortable
                            class="table-cell min-w-40"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.rebate') }}</span>
                            </template>
                            <template #body="slotProps">
                                $ {{ formatAmount(slotProps.data.rebate ?? 0) }}
                            </template>
                        </Column>
                        <Column
                            field="status"
                            class="table-cell"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.status') }}</span>
                            </template>
                            <template #body="slotProps">
                                <Tag :severity="getSeverity(slotProps.data.status)" :value="slotProps.data.status" />
                            </template>
                        </Column>
                    </template>
                </DataTable>
            </div>
        </template>
    </Card>

    <Popover ref="op">
        <div class="flex flex-col gap-6 w-60">
            <!-- Filter Type-->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs text-gray-950 dark:text-white font-semibold">
                    {{ $t('public.filter_by_type') }}
                </div>
                <Select
                    v-model="filters['type'].value"
                    :options="rebateTypes"
                    optionLabel="name"
                    :placeholder="$t('public.select_type')"
                    class="w-full"
                >
                    <template #value="slotProps">
                        <div v-if="slotProps.value">
                            {{ $t(`public.${slotProps.value}`) }}
                        </div>
                        <span v-else>{{ slotProps.placeholder }}</span>
                    </template>
                    <template #option="slotProps">
                        {{ $t(`public.${slotProps.option}`) }}
                    </template>
                </Select>
            </div>

            <!-- Filter Join Date-->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs text-gray-950 dark:text-white font-semibold">
                    {{ $t('public.filter_date') }}
                </div>
                <div class="relative w-full">
                    <DatePicker
                        v-model="selectedDate"
                        dateFormat="dd/mm/yy"
                        class="w-full"
                        selectionMode="range"
                        placeholder="dd/mm/yyyy - dd/mm/yyyy"
                        tim
                    />
                    <div
                        v-if="selectedDate && selectedDate.length > 0"
                        class="absolute top-2/4 -mt-2 right-2 text-gray-400 select-none cursor-pointer bg-transparent"
                        @click="clearJoinDate"
                    >
                        <XIcon class="w-4 h-4" />
                    </div>
                </div>
            </div>

            <Button
                type="button"
                severity="info"
                class="w-full"
                outlined
                @click="clearAll"
            >
                {{ $t('public.clear_all') }}
            </Button>
        </div>
    </Popover>
</template>
