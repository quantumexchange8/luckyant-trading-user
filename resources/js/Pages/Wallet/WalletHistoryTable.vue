<script setup>
import Card from "primevue/card"
import Column from "primevue/column";
import DataTable from "primevue/datatable";
import {onMounted, ref, watch, watchEffect} from "vue";
import {FilterMatchMode} from "@primevue/core/api";
import {usePage} from "@inertiajs/vue3";
import Button from "primevue/button";
import dayjs from "dayjs";
import InputText from "primevue/inputtext";
import DatePicker from "primevue/datepicker"
import debounce from "lodash/debounce.js";
import {
    IconCircleXFilled,
    IconSearch,
    IconAdjustments,
    IconXboxX,
    IconListSearch
} from "@tabler/icons-vue";
import NoData from "@/Components/NoData.vue";
import ProgressSpinner from "primevue/progressspinner";
import {transactionFormat} from "@/Composables/index.js";
import Tag from "primevue/tag";
import Popover from "primevue/popover";
import RadioButton from "primevue/radiobutton";
import MultiSelect from "primevue/multiselect";
import Dialog from "primevue/dialog";

const props = defineProps({
    transactionTypeSel: Array,
});

const isLoading = ref(false);
const dt = ref(null);
const histories = ref([]);
const {formatAmount, formatType} = transactionFormat();
const totalRecords = ref(0);
const first = ref(0);
const cashWalletAmount = ref();
const bonusWalletAmount = ref();
const eWalletAmount = ref();

const filters = ref({
    global: { value: null, matchMode: FilterMatchMode.CONTAINS },
    types: { value: null, matchMode: FilterMatchMode.CONTAINS },
    wallet_type: { value: null, matchMode: FilterMatchMode.EQUALS },
    start_date: { value: null, matchMode: FilterMatchMode.EQUALS },
    end_date: { value: null, matchMode: FilterMatchMode.EQUALS },
    status: { value: null, matchMode: FilterMatchMode.EQUALS },
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

            const url = route('wallet.getWalletHistories', params);
            const response = await fetch(url);
            const results = await response.json();

            histories.value = results?.data?.data;
            totalRecords.value = results?.data?.total;
            cashWalletAmount.value = results?.cashWalletAmount;
            bonusWalletAmount.value = results?.bonusWalletAmount;
            eWalletAmount.value = results?.eWalletAmount;
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

const op = ref();
const toggle = (event) => {
    op.value.toggle(event);
}

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

watch([filters.value['types'], filters.value['wallet_type'], filters.value['status']], () => {
    loadLazyData()
});

const clearAll = () => {
    filters.value['global'].value = null;
    filters.value['types'].value = null;
    filters.value['wallet_type'].value = null;
    filters.value['start_date'].value = null;
    filters.value['end_date'].value = null;
    filters.value['status'].value = null;

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

const emit = defineEmits(['update-totals']);

watch([cashWalletAmount, bonusWalletAmount, eWalletAmount], () => {
    emit('update-totals', {
        cashWalletAmount: cashWalletAmount.value,
        bonusWalletAmount: bonusWalletAmount.value,
        eWalletAmount: eWalletAmount.value,
    });
});

const getSeverity = (status) => {
    switch (status) {
        case 'Rejected':
            return 'danger';

        case 'Processing':
            return 'info';

        case 'Success':
            return 'success';
    }
}

const transactionClass = (data) => {
    const userId = usePage().props.auth.user.id;

    const positiveTypes = [
        'Deposit', 'LotSizeRebate', 'SameLevelRewards', 'PerformanceIncentive',
        'BalanceOut', 'ProfitSharing', 'TerminationReturn'
    ];

    const negativeTypes = [
        'Withdrawal', 'ManagementFee', 'BalanceIn', 'TopUp', 'DepositCapital'
    ];

    const adjustmentTypes = [
        'Transfer', 'WalletRedemption', 'WalletAdjustment', 'ReturnedAmount'
    ];

    if (
        positiveTypes.includes(data.transaction_type) ||
        (adjustmentTypes.includes(data.transaction_type) && data.to_wallet?.user.id === userId)
    ) {
        return 'text-primary-500';
    }

    if (
        negativeTypes.includes(data.transaction_type) ||
        (adjustmentTypes.includes(data.transaction_type) && data.from_wallet?.user.id === userId)
    ) {
        return 'text-error-500';
    }

    return '';
};

const walletSel = [
    'cash_wallet',
    'bonus_wallet',
    'e_wallet',
]

const statusOptions = [
    'Processing',
    'Success',
    'Rejected',
]

const visible = ref(false);
const transactionData = ref(null);

const openDialog = (data) => {
    visible.value = true;
    transactionData.value = data;
}
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
                    :globalFilterFields="['from_meta_login', 'to_meta_login', 'from_wallet.wallet_address', 'to_wallet.wallet_address', 'transaction_number']"
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
                            <div class="flex justify-between items-center w-full gap-3">
                                <Button
                                    type="button"
                                    severity="secondary"
                                    outlined
                                    class="flex gap-3 items-center justify-center w-full md:w-[130px]"
                                    @click="toggle"
                                >
                                    <IconAdjustments size="16" stroke-width="1.5"/>
                                    <div class="text-sm font-medium">
                                        {{ $t('public.filter') }}
                                    </div>
                                </Button>
                            </div>
                        </div>
                    </template>
                    <template #empty>
                        <NoData
                            v-if="!isLoading"
                            :title="$t('public.no_data_to_display')"
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
                            field="from_wallet"
                            class="table-cell"
                            :header="$t('public.from')"
                        >
                            <template #body="{data}">
                                <div v-if="data.from_wallet">
                                    <div
                                        v-if="data.transaction_type === 'Transfer'"
                                        class="flex flex-col"
                                    >
                                        <div class="text-xs font-semibold">
                                            {{ data.from_wallet.user.username }}
                                        </div>
                                        <div class="text-gray-400 dark:text-gray-500 text-xs font-medium">
                                            {{ data.from_wallet.wallet_address }}
                                        </div>
                                    </div>
                                    <div v-else>
                                        {{ $t(`public.${data.from_wallet?.type}`) }}
                                    </div>
                                </div>
                                <div v-else-if="data.from_meta_login && ['BalanceOut', 'ProfitSharing', 'TerminationReturn'].includes(data.transaction_type)">
                                    <span class="font-medium">{{ data.from_meta_login ?? '-' }}</span>
                                </div>
                                <div v-else>
                                    -
                                </div>
                            </template>
                        </Column>
                        <Column
                            field="to_wallet"
                            class="table-cell"
                            :header="$t('public.to')"
                        >
                            <template #body="{data}">
                                <div v-if="data.to_wallet">
                                    <div
                                        v-if="data.transaction_type === 'Transfer'"
                                        class="flex flex-col"
                                    >
                                        <div class="text-xs font-semibold">
                                            {{ data.to_wallet.user.username }}
                                        </div>
                                        <div class="text-gray-400 dark:text-gray-500 text-xs font-medium">
                                            {{ data.to_wallet.wallet_address }}
                                        </div>
                                    </div>
                                    <div v-else>
                                        {{ $t(`public.${data.to_wallet?.type}`) }}
                                    </div>
                                </div>
                                <div v-else-if="data.to_meta_login">
                                    {{ data.to_meta_login ?? '-' }}
                                </div>
                                <div v-else>
                                    {{ $t(`public.${formatType(data.payment_method).toLowerCase().replace(/\s+/g, '_')}`) ?? '-' }}
                                </div>
                            </template>
                        </Column>
                        <Column
                            field="transaction_type"
                            class="table-cell min-w-28"
                        >
                            <template #header>
                                <span class="block">{{ $t('public.type') }}</span>
                            </template>
                            <template #body="{data}">
                                <Tag
                                    severity="secondary"
                                    :value="$t(`public.${formatType(data.transaction_type).toLowerCase().replace(/\s+/g, '_')}`)"
                                />
                            </template>
                        </Column>
                        <Column
                            field="transaction_number"
                            class="table-cell"
                            sortable
                            :header="$t('public.transaction_no')"
                        >
                            <template #body="{data}">
                                {{ data.transaction_number ?? '-' }}
                            </template>
                        </Column>
                        <Column
                            field="amount"
                            sortable
                            class="table-cell"
                            :header="$t('public.amount')"
                        >
                            <template #body="{data}">
                                <div :class="['font-medium', transactionClass(data)]">
                                    ${{ formatAmount(data.amount ?? 0) }}
                                </div>

                            </template>
                        </Column>
                        <Column
                            field="status"
                            class="table-cell min-w-24"
                            :header="$t('public.status')"
                        >
                            <template #body="{data}">
                                <Tag
                                    :severity="getSeverity(data.status)"
                                    :value="$t(`public.${formatType(data.status).toLowerCase().replace(/\s+/g, '_')}`)"
                                />
                            </template>
                        </Column>
                        <Column
                            field="action"
                            header=""
                            class="table-cell"
                        >
                            <template #body="{data}">
                                <div class="flex items-center justify-center">
                                    <Button
                                        type="button"
                                        severity="secondary"
                                        outlined
                                        rounded
                                        size="small"
                                        class="!p-2"
                                        @click="openDialog(data)"
                                    >
                                        <IconListSearch size="14" stroke-width="1.5" />
                                    </Button>
                                </div>
                            </template>
                        </Column>
                    </template>
                </DataTable>
            </div>
        </template>
    </Card>

    <Popover ref="op">
        <div class="flex flex-col gap-6 w-60">
            <!-- Filter Request Date -->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs text-gray-950 dark:text-white font-semibold">
                    {{ $t('public.filter_date') }}
                </div>
                <div class="relative w-full">
                    <DatePicker
                        v-model="selectedDate"
                        dateFormat="yy-mm-dd"
                        class="w-full"
                        selectionMode="range"
                        placeholder="YYYY-MM-DD - YYYY-MM-DD"
                    />
                    <div
                        v-if="selectedDate && selectedDate.length > 0"
                        class="absolute top-2/4 -mt-1.5 right-2 text-gray-400 select-none cursor-pointer bg-transparent"
                        @click="clearDate"
                    >
                        <IconXboxX size="12" stoke-width="1.5" />
                    </div>
                </div>
            </div>

            <!-- Filter Types -->
            <div
                class="flex flex-col gap-2 items-center self-stretch"
            >
                <div class="flex self-stretch text-xs text-gray-950 dark:text-white font-semibold">
                    {{ $t('public.filter_by_type') }}
                </div>
                <MultiSelect
                    v-model="filters['types'].value"
                    :options="transactionTypeSel"
                    :placeholder="$t('public.select_type')"
                    :maxSelectedLabels="3"
                    class="w-full"
                >
                    <template #value="slotProps">
                        <div v-if="slotProps.value && slotProps.value.length > 0" class="flex gap-1 items-center">
                            <div v-for="data in slotProps.value">
                                {{ $t(`public.${formatType(data).toLowerCase().replace(/\s+/g, '_')}`) }},
                            </div>
                        </div>
                        <span v-else>{{ slotProps.placeholder }}</span>
                    </template>
                    <template #option="slotProps">
                        {{ $t(`public.${formatType(slotProps.option).toLowerCase().replace(/\s+/g, '_')}`) }}
                    </template>
                </MultiSelect>
            </div>

            <!-- Filter Wallet -->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs text-gray-950 dark:text-white font-semibold">
                    {{ $t('public.wallet') }}
                </div>
                <div
                    v-for="wallet in walletSel"
                    :key="wallet"
                    class="flex items-center gap-1 w-full"
                >
                    <RadioButton
                        v-model="filters['wallet_type'].value"
                        :inputId="wallet"
                        :value="wallet"
                    />
                    <label :for="wallet" class="text-sm">{{ $t(`public.${wallet}`) }}</label>
                </div>
            </div>

            <!-- Filter Status -->
            <div class="flex flex-col gap-2 items-center self-stretch">
                <div class="flex self-stretch text-xs text-gray-950 dark:text-white font-semibold">
                    {{ $t('public.status') }}
                </div>
                <div
                    v-for="status in statusOptions"
                    :key="status"
                    class="flex items-center gap-1 w-full"
                >
                    <RadioButton
                        v-model="filters['status'].value"
                        :inputId="status"
                        :value="status"
                    />
                    <label :for="status" class="text-sm">{{ $t(`public.${formatType(status).toLowerCase().replace(/\s+/g, '_')}`) }}</label>
                </div>
            </div>

            <Button
                type="button"
                severity="info"
                class="w-full"
                size="small"
                outlined
                @click="clearAll"
                :label="$t('public.clear_all')"
            />
        </div>
    </Popover>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.details')"
        class="dialog-xs md:dialog-md"
    >
        <div class="flex flex-col-reverse md:flex-row md:items-center gap-3 self-stretch w-full pb-4 border-b dark:border-gray-600">
            <div class="flex flex-col items-start w-full">
                <Tag
                    class="!text-base"
                    severity="info"
                    :value="$t(`public.${formatType(transactionData.transaction_type).toLowerCase().replace(/\s+/g, '_')}`)"
                />
            </div>
            <div class="min-w-[180px] text-gray-950 dark:text-white font-semibold text-xl md:text-right">
                ${{ formatAmount(transactionData.amount ?? 0) }}
            </div>
        </div>

        <div class="flex flex-col items-center gap-4 divide-y dark:divide-gray-600 self-stretch">
            <div class="flex flex-col gap-3 items-start w-full pt-4">
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.date') }}
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        {{ dayjs(transactionData.created_at).format('YYYY/MM/DD HH:mm:ss') }}
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-start gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.from') }}
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        <div v-if="transactionData.from_wallet">
                            <div
                                v-if="transactionData.transaction_type === 'Transfer'"
                                class="flex flex-col"
                            >
                                <div class="text-xs font-semibold">
                                    {{ transactionData.from_wallet.user.username }}
                                </div>
                                <div class="text-gray-400 dark:text-gray-500 text-xs font-medium">
                                    {{ transactionData.from_wallet.wallet_address }}
                                </div>
                            </div>
                            <div v-else>
                                <Tag
                                    severity="secondary"
                                    :value="$t(`public.${transactionData.from_wallet.type}`)"
                                />
                            </div>
                        </div>
                        <div v-else-if="transactionData.from_meta_login && ['BalanceOut', 'ProfitSharing', 'TerminationReturn'].includes(transactionData.transaction_type)">
                            {{ transactionData.from_meta_login ?? '-' }}
                        </div>
                        <div v-else>
                            -
                        </div>
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-start gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.to') }}
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        <div v-if="transactionData.to_wallet">
                            <div
                                v-if="transactionData.transaction_type === 'Transfer'"
                                class="flex flex-col"
                            >
                                <div class="text-xs font-semibold">
                                    {{ transactionData.to_wallet.user.username }}
                                </div>
                                <div class="text-gray-400 dark:text-gray-500 text-xs font-medium">
                                    {{ transactionData.to_wallet.wallet_address }}
                                </div>
                            </div>
                            <div v-else>
                                <Tag
                                    severity="secondary"
                                    :value="$t(`public.${transactionData.to_wallet.type}`)"
                                />
                            </div>
                        </div>
                        <div v-else-if="transactionData.to_meta_login">
                            {{ transactionData.to_meta_login ?? '-' }}
                        </div>
                        <div v-else>
                            {{ $t(`public.${formatType(transactionData.payment_method).toLowerCase().replace(/\s+/g, '_')}`) ?? '-' }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-3 items-start w-full pt-4">
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.transaction_no') }}
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        {{ transactionData.transaction_number ?? '-' }}
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.status') }}
                    </div>
                    <div>
                        <Tag
                            :severity="getSeverity(transactionData.status)"
                            :value="$t(`public.${formatType(transactionData.status).toLowerCase().replace(/\s+/g, '_')}`)"
                        />
                    </div>
                </div>
            </div>

            <div class="flex flex-col gap-3 items-start w-full pt-4">
                <div
                    v-if="transactionData.transaction_type === 'Withdrawal'"
                    class="flex flex-col md:flex-row md:items-center gap-1 self-stretch"
                >
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.platform') }}
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        {{ transactionData.payment_account?.payment_platform_name ?? '-' }}
                    </div>
                </div>
                <div
                    v-if="transactionData.transaction_type === 'Withdrawal'"
                    class="flex flex-col md:flex-row md:items-center gap-1 self-stretch"
                >
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.beneficiary_name') }}
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        {{ transactionData.payment_account_name ?? transactionData.payment_account?.payment_account_name ?? '-' }}
                    </div>
                </div>
                <div
                    v-if="['Deposit', 'Withdrawal'].includes(transactionData.transaction_type)"
                    class="flex flex-col md:flex-row md:items-center gap-1 self-stretch"
                >
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.account_number') }}
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        {{ transactionData.to_wallet_address }}
                    </div>
                </div>
                <div v-if="transactionData.transaction_charges > 0" class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.payment_charges') }}
                    </div>
                    <div class="text-error-500 text-sm font-medium">
                        -${{ formatAmount(transactionData.transaction_charges) }}
                    </div>
                </div>
                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                        {{ $t('public.receive_amount') }}
                    </div>
                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                        <div v-if="transactionData.transaction_type === 'Withdrawal'">
                            ${{ formatAmount(transactionData.transaction_amount ?? 0) }} <span v-if="transactionData.payment_method === 'Bank'" class="text-gray-500">(¥{{ formatAmount(transactionData.conversion_amount) }})</span>
                        </div>
                        <div v-else>
                            ${{ formatAmount(transactionData.amount ?? 0) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </Dialog>
</template>
