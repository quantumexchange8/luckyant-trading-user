<script setup>
import {onMounted, ref, watch} from "vue";
import debounce from "lodash/debounce.js";
import { FilterMatchMode } from '@primevue/core/api';
import Tag from "primevue/tag";
import Popover from "primevue/popover";
import DatePicker from "primevue/datepicker";
import {transactionFormat} from "@/Composables/index.js";
import Card from "primevue/card";
import dayjs from "dayjs";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Input from "@/Components/Input.vue";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import {SearchLgIcon, XIcon, XCircleIcon} from "@/Components/Icons/outline.jsx"
import Button from "primevue/button";
import {
    IconAdjustments
} from "@tabler/icons-vue";
import Loading from "@/Components/Loading.vue";
import {useLangObserver} from "@/Composables/localeObserver.js";
import Select from "primevue/select";

const props = defineProps({
    pammType: String,
})

const isLoading = ref(false);
const dt = ref(null);
const affiliates = ref([]);
const {formatAmount, formatType} = transactionFormat();
const totalRecords = ref(0);
const first = ref(0);
const totalAffiliate = ref();
const totalDeposit = ref();
const {locale} = useLangObserver();

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    pamm_type: { value: props.pammType, matchMode: FilterMatchMode.EQUALS },
    start_join_date: { value: null, matchMode: FilterMatchMode.EQUALS },
    end_join_date: { value: null, matchMode: FilterMatchMode.EQUALS },
    leader_id: { value: null, matchMode: FilterMatchMode.EQUALS },
    master_id: { value: null, matchMode: FilterMatchMode.EQUALS },
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

            const url = route('referral.getAffiliatePamm', params);
            const response = await fetch(url);
            const results = await response.json();

            affiliates.value = results?.data?.data;
            totalRecords.value = results?.data?.total;
            totalAffiliate.value = results?.totalAffiliate;
            totalDeposit.value = results?.totalDeposit;
            isLoading.value = false;

        }, 100);
    }  catch (e) {
        affiliates.value = [];
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
    getReferrers();
    getMastersByType();
}

const leaders = ref();
const loadingReferrers = ref(false);

const getReferrers = async () => {
    loadingReferrers.value = true;
    try {
        const response = await axios.get('/getReferrers');
        leaders.value = response.data;
    } catch (error) {
        console.error('Error fetching referrers:', error);
    } finally {
        loadingReferrers.value = false;
    }
};

const selectedDate = ref([]);

const clearJoinDate = () => {
    selectedDate.value = [];
}

watch(selectedDate, (newDateRange) => {
    if (Array.isArray(newDateRange)) {
        const [startDate, endDate] = newDateRange;
        filters.value['start_join_date'].value = startDate;
        filters.value['end_join_date'].value = endDate;

        if (startDate !== null && endDate !== null) {
            loadLazyData();
        }
    } else {
        console.warn('Invalid date range format:', newDateRange);
    }
})

const masters = ref();
const loadingMasters = ref(false);

const getMastersByType = async () => {
    loadingMasters.value = true;
    try {
        const response = await axios.get(`/getMastersByType?type=${props.pammType}`);
        masters.value = response.data;
    } catch (error) {
        console.error('Error fetching masters:', error);
    } finally {
        loadingMasters.value = false;
    }
};

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

watch(() => props.pammType, () => {
    filters.value['pamm_type'].value = props.pammType;
})

watch([filters.value['pamm_type'], filters.value['leader_id'], filters.value['master_id']], () => {
    loadLazyData()
});

const clearFilterGlobal = () => {
    filters.value['global'].value = null;
}

const emit = defineEmits(['update-totals']);

// Emit the totals whenever they change
watch([totalAffiliate, totalDeposit], () => {
    emit('update-totals', {
        totalAffiliate: totalAffiliate.value,
        totalDeposit: totalDeposit.value,
    });
});

const clearFilter = () => {
    filters.value['global'].value = null;
    filters.value['leader_id'].value = null;
    filters.value['start_join_date'].value = null;
    filters.value['end_join_date'].value = null;
    filters.value['master_id'].value = null;

    selectedDate.value = [];
    lazyParams.value.filters = filters.value ;
};

const getSeverity = (status) => {
    switch (status) {
        case 'Terminated':
            return 'danger';

        case 'Expired':
            return 'danger';

        case 'Active':
            return 'success';
    }
}
</script>

<template>
    <Card class="w-full">
        <template #content>
            <div class="w-full">
                <DataTable
                    :value="affiliates"
                    lazy
                    paginator
                    removableSort
                    :rows="10"
                    :rowsPerPageOptions="[10, 20, 50, 100]"
                    :first="first"
                    paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                    :currentPageReportTemplate="$t('public.paginator_caption')"
                    v-model:filters="filters"
                    ref="dt"
                    dataKey="id"
                    :loading="isLoading"
                    :totalRecords="totalRecords"
                    @page="onPage($event)"
                    @sort="onSort($event)"
                    @filter="onFilter($event)"
                    :globalFilterFields="['username', 'meta_login', 'master_meta_login']"
                >
                    <template #header>
                        <div class="flex flex-col md:flex-row items-center gap-3 w-full md:w-auto mb-5">

                            <!-- Search bar -->
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

                            <!-- filter button -->
                            <Button
                                type="button"
                                severity="secondary"
                                class="w-full md:w-fit flex gap-2 px-4"
                                @click="toggle"
                            >
                                <IconAdjustments :size="16" stroke-width="1.5" />
                                {{ $t('public.filter') }}
                            </Button>
                        </div>
                    </template>

                    <template #empty>
                        <div class="flex flex-col">
                            <span>{{ $t('public.no_history') }}</span>
                        </div>
                    </template>

                    <template #loading>
                        <div class="flex flex-col gap-2 items-center justify-center">
                            <Loading />
                        </div>
                    </template>

                    <template v-if="affiliates?.length > 0">
                        <Column
                            field="created_at"
                            :header="$t('public.join_date')"
                            sortable
                        >
                            <template #body="{ data }">
                                {{ dayjs(data.created_at).format('YYYY-MM-DD') }}
                            </template>
                        </Column>

                        <Column
                            field="name"
                            :header="$t('public.affiliate')"
                        >
                            <template #body="{ data }">
                                <div class="flex flex-col">
                                    <span>{{ data.user.username ?? '-' }}</span>
                                    <span class="text-xs text-gray-400 dark:text-gray-500">{{ data.user.email }}</span>
                                </div>
                            </template>
                        </Column>

                        <Column
                            field="meta_login"
                            :header="$t('public.live_account')"
                            sortable
                        >
                            <template #body="{ data }">
                                {{ data.meta_login }}
                            </template>
                        </Column>

                        <Column
                            field="master"
                            :header="$t('public.master')"
                        >
                            <template #body="{ data }">
                                <div class="flex items-center gap-2">
                                    <div v-if="data.master" class="flex flex-col">
                                        <span class="font-semibold">{{ data.master.trading_user.name }}</span>
                                        <span class="text-gray-400">{{ data.master_meta_login }}</span>
                                    </div>
                                    <div v-else class="h-[37px] flex items-center self-stretch">
                                        -
                                    </div>
                                </div>
                            </template>
                        </Column>

                        <Column
                            field="subscription_amount"
                            sortable
                            :header="$t('public.amount')"
                        >
                            <template #body="{ data }">
                                ${{ formatAmount(data.subscription_amount) }}
                            </template>
                        </Column>

                        <Column
                            field="status"
                            :header="$t('public.status')"
                        >
                            <template #body="{ data }">
                                <Tag
                                    :severity="getSeverity(data.status)"
                                    :value="$t(`public.${formatType(data.status).toLowerCase().replace(/\s+/g, '_')}`)"
                                />
                            </template>
                        </Column>
                    </template>
                </DataTable>
            </div>
        </template>
    </Card>

    <Popover ref="op">
        <div class="flex flex-col gap-6 w-60">
            <!-- Filter Leader-->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs text-gray-950 dark:text-white font-semibold">
                    {{ $t('public.filter_by_referrer') }}
                </div>
                <Select
                    v-model="filters['leader_id'].value"
                    :options="leaders"
                    optionLabel="name"
                    :placeholder="$t('public.select_referrer')"
                    class="w-full"
                    filter
                    :filter-fields="['name', 'email']"
                    :loading="loadingReferrers"
                >
                    <template #value="slotProps">
                        <div v-if="slotProps.value" class="flex items-center">
                            {{ slotProps.value.username }}
                        </div>
                        <span v-else>{{ slotProps.placeholder }}</span>
                    </template>
                    <template #option="slotProps">
                        <div class="flex flex-col max-w-[220px] truncate">
                            <div>{{ slotProps.option.username }}</div>
                        </div>
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

            <!-- Filter Master -->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs text-gray-950 dark:text-white font-semibold">
                    {{ $t('public.filter_by_master') }}
                </div>
                <Select
                    v-model="filters['master_id'].value"
                    :options="masters"
                    optionLabel="name"
                    :placeholder="$t('public.select_master')"
                    filter
                    :filter-fields="['trading_user.name', 'trading_user.company', 'meta_login']"
                    class="w-full"
                    :loading="loadingMasters"
                >
                    <template #value="slotProps">
                        <div v-if="slotProps.value" class="flex items-center">
                            <div v-if="locale === 'cn'">
                                {{ slotProps.value.trading_user.company ? slotProps.value.trading_user.company : slotProps.value.trading_user.name }} <span class="text-gray-400 dark:text-gray-600">({{ slotProps.value.meta_login }})</span>
                            </div>
                            <div v-else>
                                {{ slotProps.value.trading_user.name }} <span class="text-gray-400 dark:text-gray-600">({{ slotProps.value.meta_login }})</span>
                            </div>
                        </div>
                        <span v-else>{{ slotProps.placeholder }}</span>
                    </template>
                    <template #option="slotProps">
                        <div v-if="locale === 'cn'">
                            {{ slotProps.option.trading_user.company ? slotProps.option.trading_user.company : slotProps.option.trading_user.name }} <span class="text-gray-400 dark:text-gray-600">({{ slotProps.option.meta_login }})</span>
                        </div>
                        <div v-else>
                            {{ slotProps.option.trading_user.name }} <span class="text-gray-400 dark:text-gray-600">({{ slotProps.option.meta_login }})</span>
                        </div>
                    </template>
                </Select>
            </div>

            <Button
                type="button"
                severity="info"
                class="w-full"
                outlined
                @click="clearFilter"
            >
                {{ $t('public.clear_all') }}
            </Button>
        </div>
    </Popover>
</template>
