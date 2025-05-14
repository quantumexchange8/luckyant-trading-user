<script setup>
import Card from "primevue/card"
import Column from "primevue/column";
import DataTable from "primevue/datatable";
import {onMounted, ref, watch, watchEffect} from "vue";
import {FilterMatchMode} from "@primevue/core/api";
import {usePage} from "@inertiajs/vue3";
import dayjs from "dayjs";
import InputText from "primevue/inputtext";
import DatePicker from "primevue/datepicker"
import debounce from "lodash/debounce.js";
import {
    IconCircleXFilled,
    IconSearch,
} from "@tabler/icons-vue";
import NoData from "@/Components/NoData.vue";
import ProgressSpinner from "primevue/progressspinner";
import {transactionFormat} from "@/Composables/index.js";

const props = defineProps({
    selectedType: String,
});

const isLoading = ref(false);
const dt = ref(null);
const histories = ref([]);
const {formatAmount} = transactionFormat();
const totalRecords = ref(0);
const first = ref(0);
const totalPerformanceIncentive = ref();
const totalAffiliateAmount = ref();
const totalPersonalAmount = ref();

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    type: { value: props.selectedType, matchMode: FilterMatchMode.EQUALS },
    start_date: { value: null, matchMode: FilterMatchMode.EQUALS },
    end_date: { value: null, matchMode: FilterMatchMode.EQUALS },
});

const lazyParams = ref({});

const loadLazyData = (event) => {
    isLoading.value = true;

    lazyParams.value = { ...lazyParams.value, first: event?.first || first.value };
    lazyParams.value.filters = filters.value;
    try {
        setTimeout(async () => {
            const params = {
                page: JSON.stringify(event?.page + 1),
                sortField: event?.sortField,
                sortOrder: event?.sortOrder,
                include: [],
                lazyEvent: JSON.stringify(lazyParams.value)
            };

            const url = route('report.getPerformanceIncentive', params);
            const response = await fetch(url);
            const results = await response.json();

            histories.value = results?.data?.data;
            totalRecords.value = results?.data?.total;
            totalPerformanceIncentive.value = results?.totalPerformanceIncentive;
            totalAffiliateAmount.value = results?.totalAffiliateAmount;
            totalPersonalAmount.value = results?.totalPersonalAmount;
            isLoading.value = false;
        }, 100);
    }  catch (e) {
        histories.value = [];
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

const selectedDate = ref([]);

const clearDate = () => {
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

watch([filters.value['type']], () => {
    loadLazyData()
});

watch(() => props.selectedType, (newType) => {
    filters.value['type'].value = newType;
    loadLazyData();
})


const clearFilterGlobal = () => {
    filters.value['global'].value = null;
}

watchEffect(() => {
    if (usePage().props.toast !== null) {
        loadLazyData();
    }
});

const emit = defineEmits(['update-totals']);

watch([totalPerformanceIncentive, totalAffiliateAmount, totalPersonalAmount], () => {
    emit('update-totals', {
        totalPerformanceIncentive: totalPerformanceIncentive.value,
        totalAffiliateAmount: totalAffiliateAmount.value,
        totalPersonalAmount: totalPersonalAmount.value,
    });
});
</script>

<template>
    <Card class="w-full">
        <template #content>
            <div
                class="w-full"
            >
                <DataTable
                    :value="histories"
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
                    :globalFilterFields="['username', 'email', 'meta_login']"
                >
                    <template #header>
                        <div class="flex flex-col md:flex-row gap-3 items-center self-stretch pb-5">
                            <div class="relative w-full md:w-60">
                                <div class="absolute top-2/4 -mt-[9px] left-4 text-gray-400">
                                    <IconSearch size="20" stroke-width="1.5"/>
                                </div>
                                <InputText
                                    v-model="filters['global'].value"
                                    :placeholder="$t('public.search')"
                                    class="font-normal pl-12 w-full md:w-60"
                                />
                                <div
                                    v-if="filters['global'].value !== null"
                                    class="absolute top-2/4 -mt-2 right-4 text-gray-300 hover:text-gray-400 dark:text-gray-500 dark:hover:text-gray-400 select-none cursor-pointer"
                                    @click="clearFilterGlobal"
                                >
                                    <IconCircleXFilled size="16"/>
                                </div>
                            </div>
                            <div class="relative w-full md:w-72">
                                <DatePicker
                                    v-model="selectedDate"
                                    dateFormat="yy-mm-dd"
                                    class="w-full md:w-72 font-normal"
                                    selectionMode="range"
                                    placeholder="YYYY-MM-DD - YYYY-MM-DD"
                                />
                                <div
                                    v-if="selectedDate && selectedDate.length > 0"
                                    class="absolute top-2/4 -mt-2 right-4 text-gray-300 hover:text-gray-400 dark:text-gray-500 dark:hover:text-gray-400 select-none cursor-pointer"
                                    @click="clearDate"
                                >
                                    <IconCircleXFilled size="16"/>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template #empty>
                        <NoData
                            v-if="!isLoading"
                        />
                    </template>
                    <template #loading>
                        <div class="flex flex-col gap-2 items-center justify-center">
                            <ProgressSpinner
                                strokeWidth="4"
                            />
                            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $t('public.loading') }}</span>
                        </div>
                    </template>
                    <template v-if="histories?.length > 0">
                        <Column
                            field="created_at"
                            sortable
                            class="table-cell min-w-32"
                            :header="$t('public.date')"
                        >
                            <template #body="{data}">
                                {{ dayjs(data.created_at).format('YYYY-MM-DD') }}
                                <div class="text-xs text-gray-500">
                                    {{ dayjs(data.created_at).format('HH:mm:ss') }}
                                </div>
                            </template>
                        </Column>

                        <Column
                            field="name"
                            :header="$t('public.username')"
                        >
                            <template #body="{ data }">
                                <div v-if="data.meta_login" class="flex flex-col">
                                    <span>{{ data.user.username ?? '-' }}</span>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ data.user.email }}</span>
                                </div>
                                <div v-else>
                                    <div v-if="data.category === 'pamm'" class="flex flex-col">
                                        <span>{{ data.pamm_subscription.user.username ?? '-' }}</span>
                                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ data.pamm_subscription.user.email }}</span>
                                    </div>
                                    <div v-else class="flex flex-col">
                                        <span>{{ data.subscription.user.username ?? '-' }}</span>
                                        <span class="text-xs text-gray-400 dark:text-gray-500">{{ data.subscription.user.email }}</span>
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column
                            field="meta_login"
                            class="table-cell min-w-24"
                            :header="$t('public.account_no')"
                        >
                            <template #body="{ data }">
                                <div v-if="data.meta_login" class="flex flex-col">
                                    <span>{{ data.meta_login }}</span>
                                </div>
                                <div v-else>
                                    <div v-if="data.category === 'pamm'" class="flex flex-col">
                                        <span>{{ data.pamm_subscription.meta_login ?? '-' }}</span>
                                    </div>
                                    <div v-else class="flex flex-col">
                                        <span>{{ data.subscription.meta_login ?? '-' }}</span>
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column
                            field="subscription_amount"
                            class="table-cell min-w-24"
                            :header="$t('public.fund')"
                        >
                            <template #body="{ data }">
                                <span class="font-medium">${{ formatAmount(data.subscription.meta_balance) }}</span>
                            </template>
                        </Column>

                        <Column
                            field="subscription_profit_amt"
                            class="table-cell min-w-24"
                            sortable
                            :header="$t('public.profit')"
                        >
                            <template #body="{ data }">
                                <span class="font-medium">${{ formatAmount(data.subscription_profit_amt) }}</span>
                            </template>
                        </Column>

                        <Column
                            field="personal_bonus_amt"
                            class="table-cell min-w-32"
                            sortable
                            :header="$t('public.performance_incentive')"
                        >
                            <template #body="{ data }">
                                <span class="font-medium text-success-500">${{ formatAmount(data.personal_bonus_amt) }}</span>
                            </template>
                        </Column>
                    </template>
                </DataTable>
            </div>
        </template>
    </Card>
</template>
