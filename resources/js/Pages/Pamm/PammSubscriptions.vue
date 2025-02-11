<script setup>
import NoData from "@/Components/NoData.vue";
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
import Tag from "primevue/tag";
import RadioButton from "primevue/radiobutton";
import {
    XIcon,
    SearchLgIcon
} from "@/Components/Icons/outline.jsx"
import dayjs from "dayjs";
import Popover from "primevue/popover";
import DatePicker from "primevue/datepicker"
import {IconAdjustments} from "@tabler/icons-vue";
import PammSubscriptionAccount from "@/Pages/Pamm/Partials/PammSubscriptionAccount.vue";
import PammSubscriptionsAction from "@/Pages/Pamm/PammSubscriptionsAction.vue";

const props = defineProps({
    strategyType: String,
    pammType: String,
    pammsCount: Number,
    walletSel: Array,
})

const isLoading = ref(false);
const subscriptions = ref([]);
const {formatAmount, formatType} = transactionFormat();
const meta_login = ref('');
const master_id = ref('');
const joinDatePicker = ref([]);

const getResults = async (filterJoinDate = null) => {
    isLoading.value = true;
    try {
        let url = `/${props.strategyType}_strategy/getPammSubscriptions?meta_login=${meta_login.value}&master_id=${master_id.value}`;

        if (filterJoinDate?.length > 0) {
            const [startDate, endDate] = filterJoinDate;
            url += `&joinStartDate=${dayjs(startDate).format('YYYY-MM-DD')}&joinEndDate=${dayjs(endDate).format('YYYY-MM-DD')}`;
        }

        const response = await axios.get(url);
        subscriptions.value = response.data;
    } catch (error) {
        console.error('Error fetching subscriptions:', error);
    } finally {
        isLoading.value = false;
    }
};

const op = ref();
const toggle = (event) => {
    op.value.toggle(event);
}

const filters = ref({
    global: {value: null, matchMode: FilterMatchMode.CONTAINS},
    status: {value: null, matchMode: FilterMatchMode.EQUALS},
});

watch(
    [master_id, meta_login],
    () => {
        getResults();
    }
);

const clearJoinDate = () => {
    joinDatePicker.value = [];
}

watch(joinDatePicker, (newDateRange) => {
    if (Array.isArray(newDateRange)) {
        const [startDate, endDate] = newDateRange;

        if (startDate && endDate) {
            getResults([startDate, endDate]);
        } else if (startDate || endDate) {
            getResults([startDate || endDate, endDate || startDate]);
        } else {
            getResults();
        }
    } else {
        console.warn('Invalid date range format:', newDateRange);
    }
})

const clearAll = () => {
    filters.value['status'].value = null;
    joinDatePicker.value = [];
}

const clearFilterGlobal = () => {
    filters.value['global'].value = null;
}

watchEffect(() => {
    if (usePage().props.toast !== null) {
        getResults();
    }
});

const getJoinedDays = (subscriber) => {
    const approvalDate = dayjs(subscriber.approval_date);
    const endDate =
        subscriber.status === 'Terminated'
            ? dayjs(subscriber.termination_date)
            : dayjs();

    return endDate.diff(approvalDate, 'day'); // Calculate the difference in days
};

const getSeverity = (status) => {
    switch (status) {
        case 'Terminated':
            return 'danger';

        case 'Rejected':
            return 'danger';

        case 'Active':
            return 'success';

        case 'Mature':
            return 'info';

        case 'Expiring':
            return 'warning';
    }
}
</script>

<template>
    <div v-if="pammsCount > 0" class="flex flex-col gap-5 items-start self-stretch">
        <PammSubscriptionAccount
            :strategyType="strategyType"
            :pammType="pammType"
            :pammsCount="pammsCount"
            :walletSel="walletSel"
            @update:master_id="master_id = $event"
            @update:meta_login="meta_login = $event"
        />

        <Card class="w-full">
            <template #content>
                <div class="font-bold mb-5 dark:text-white">
                    {{ $t('public.subscription_history') }}
                </div>
                <div
                    class="w-full"
                >
                    <DataTable
                        v-model:filters="filters"
                        :value="subscriptions"
                        :paginator="subscriptions?.length > 0"
                        removableSort
                        dataKey="id"
                        :rows="10"
                        :rowsPerPageOptions="[10, 20, 50, 100]"
                        tableStyle="md:min-width: 50rem"
                        paginatorTemplate="RowsPerPageDropdown FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport"
                        :currentPageReportTemplate="$t('public.paginator_caption')"
                        :globalFilterFields="['meta_login', 'master_meta_login', 'subscription_number']"
                        ref="dt"
                        :loading="isLoading"
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
                                            :placeholder="$t('public.search')"
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
                                <div class="w-full gap-3 pb-5 md:pb-0">
                                    <Button
                                        class="w-full md:w-28 flex gap-2"
                                        severity="secondary"
                                        outlined
                                        @click="toggle"
                                    >
                                        <IconAdjustments size="16" />
                                        {{ $t('public.filters_placeholder') }}
                                    </Button>
                                </div>
                            </div>
                        </template>
                        <template #empty>
                            <div class="flex flex-col">
                                <span>No subscriptions</span>
                            </div>
                        </template>
                        <template #loading>
                            <div class="flex flex-col gap-2 items-center justify-center">
                                <Loading />
                            </div>
                        </template>
                        <template v-if="subscriptions.length">
                            <Column
                                field="approval_date"
                                sortable
                                class="table-cell min-w-36"
                            >
                                <template #header>
                                    <span class="block">{{ $t('public.join_date') }}</span>
                                </template>
                                <template #body="slotProps">
                                    <span class="uppercase">{{ dayjs(slotProps.data.approval_date).format('DD/MM/YYYY HH:mm:ss') }}</span>
                                </template>
                            </Column>
                            <Column
                                field="meta_login"
                                sortable
                                class="table-cell"
                            >
                                <template #header>
                                    <span class="block">{{ $t('public.account_no') }}</span>
                                </template>
                                <template #body="slotProps">
                                    <span class="font-semibold">{{ slotProps.data.meta_login }}</span>
                                </template>
                            </Column>
                            <Column
                                field="master_meta_login"
                                sortable
                                class="table-cell min-w-52"
                            >
                                <template #header>
                                    <span class="block">{{ $t('public.master') }}</span>
                                </template>
                                <template #body="slotProps">
                                    <div class="flex items-center gap-2">
                                        <div v-if="slotProps.data.master" class="flex flex-col">
                                            <span class="font-semibold">{{ slotProps.data.master.trading_user.name }}</span>
                                            <span class="text-gray-400">{{ slotProps.data.master_meta_login }}</span>
                                        </div>
                                        <div v-else class="h-[37px] flex items-center self-stretch">
                                            -
                                        </div>
                                    </div>
                                </template>
                            </Column>
                            <Column
                                field="subscription_number"
                                sortable
                                class="table-cell"
                            >
                                <template #header>
                                    <span class="block">{{ $t('public.subscription_number') }}</span>
                                </template>
                                <template #body="slotProps">
                                    <span class="uppercase">{{ slotProps.data.subscription_number }}</span>
                                </template>
                            </Column>
                            <Column
                                field="subscription_amount"
                                sortable
                                class="table-cell min-w-40"
                            >
                                <template #header>
                                    <span class="block">{{ $t('public.fund') }}</span>
                                </template>
                                <template #body="slotProps">
                                    $ {{ formatAmount(slotProps.data.subscription_amount ?? 0) }}
                                </template>
                            </Column>
                            <Column
                                field="subscription_period"
                                class="table-cell min-w-40"
                            >
                                <template #header>
                                    <span class="block">{{ $t('public.roi_period') }}</span>
                                </template>
                                <template #body="slotProps">
                                    {{ slotProps.data.settlement_period ?? 0 }} {{ $t('public.days') }}
                                </template>
                            </Column>
                            <Column
                                field="join_day"
                                class="table-cell min-w-40"
                            >
                                <template #header>
                                    <span class="block">{{ $t('public.join_day') }}</span>
                                </template>
                                <template #body="slotProps">
                                    {{ getJoinedDays(slotProps.data) }} {{ $t('public.days') }}
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
                                    <Tag :severity="getSeverity(slotProps.data.status)">
                                        {{ $t(`public.${formatType(slotProps.data.status).toLowerCase().replace(/\s+/g, '_')}`) }}
                                    </Tag>
                                </template>
                            </Column>
                            <Column
                                field="action"
                                class="table-cell"
                            >
                                <template #body="slotProps">
                                    <PammSubscriptionsAction
                                        :strategyType="strategyType"
                                        :subscription="slotProps.data"
                                    />
                                </template>
                            </Column>
                        </template>
                    </DataTable>
                </div>
            </template>
        </Card>
    </div>
    <div v-else class="w-full flex items-center justify-center">
        <NoData/>
    </div>

    <Popover ref="op">
        <div class="flex flex-col gap-6 w-60">
            <!-- Filter tags -->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs text-gray-950 dark:text-white font-semibold">
                    {{ $t('public.join_date')}}
                </div>
                <div class="relative w-full">
                    <DatePicker
                        v-model="joinDatePicker"
                        dateFormat="dd/mm/yy"
                        class="w-full"
                        selectionMode="range"
                        placeholder="dd/mm/yyyy - dd/mm/yyyy"
                    />
                    <div
                        v-if="joinDatePicker && joinDatePicker.length > 0"
                        class="absolute top-2/4 -mt-2 right-4 text-gray-400 select-none cursor-pointer bg-white"
                        @click="clearJoinDate"
                    >
                        <XIcon class="w-4 h-4" />
                    </div>
                </div>
            </div>

            <!-- Filter type -->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs text-gray-950 dark:text-white font-semibold">
                    {{ $t('public.status')}}
                </div>
                <div class="flex flex-col gap-1 self-stretch">
                    <div class="flex items-center gap-2 text-sm text-gray-950 dark:text-gray-300">
                        <RadioButton v-model="filters['status'].value" inputId="status_active" value="Active" class="w-4 h-4" />
                        <label for="status_active">{{ $t('public.active') }}</label>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-gray-950 dark:text-gray-300">
                        <RadioButton v-model="filters['status'].value" inputId="status_terminate" value="Terminated" class="w-4 h-4" />
                        <label for="status_terminate">{{ $t('public.terminated') }}</label>
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
