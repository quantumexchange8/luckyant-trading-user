<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import {SearchIcon} from "@heroicons/vue/outline";
import BaseListbox from "@/Components/BaseListbox.vue";
import InputIconWrapper from "@/Components/InputIconWrapper.vue";
import Input from "@/Components/Input.vue";
import {h, ref, watch, watchEffect} from "vue";
import Button from "@/Components/Button.vue";
import {transactionFormat} from "@/Composables/index.js";
import debounce from "lodash/debounce.js";
import {usePage} from "@inertiajs/vue3";
import TanStackTable from "@/Components/TanStackTable.vue";
import NoData from "@/Components/NoData.vue";
import StatusBadge from "@/Components/StatusBadge.vue";
import {trans} from "laravel-vue-i18n";
import Action from "@/Pages/Pamm/PammListing/Partials/Action.vue";
import PammSubscription from "@/Pages/Pamm/PammListing/PammSubscription.vue";

const props = defineProps({
    terms: Object,
    walletSel: Object,
})

const typeFilter = [
    {value: '', label:"All"},
    {value: 'ESG', label:"ESG"},
    {value: 'StandardGroup', label:"Standard"},
];

const search = ref('');
const type = ref('');

const { formatAmount, formatDateTime } = transactionFormat();

const clearFilter = () => {
    search.value = '';
    type.value = '';
}

const currentLocale = ref(usePage().props.locale);

const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});

const pageSizes = [
    {value: 5, label: 5},
    {value: 10, label: 10},
    {value: 20, label: 20},
    {value: 50, label: 50},
    {value: 100, label: 100},
]

const master = ref('');
const meta_login = ref('');
const subscriptions = ref({data: []});
const sorting = ref();
const pageSize = ref(10);
const action = ref('');
const currentPage = ref(1);
// const isLoading = ref(false);
// const refresh = ref(false);

// function refreshTable() {
//     isLoading.value = !isLoading.value;
//     refresh.value = true;
// }

const getResults = async (page = 1, paginate = 10, filterMetaLogin = meta_login.value, columnName = sorting.value) => {
    try {
        let url = `/pamm/getPammSubscriptions?meta_login=${filterMetaLogin}&page=${page}`;

        if (paginate) {
            url += `&paginate=${paginate}`;
        }

        if (columnName) {
            // Convert the object to JSON and encode it to send as a query parameter
            const encodedColumnName = encodeURIComponent(JSON.stringify(columnName));
            url += `&columnName=${encodedColumnName}`;
        }

        const response = await axios.get(url);
        subscriptions.value = response.data;
    } catch (error) {
        console.error(error);
    }
}

const columns = [
    {
        accessorKey: 'approval_date',
        header: 'date',
        cell: info => info.getValue() ? formatDateTime(info.getValue()) : trans('public.pending'),
    },
    {
        accessorKey: 'meta_login',
        header: 'live_account',
    },
    {
        accessorKey: currentLocale.value === 'cn' ? ('master.trading_user.company' !== null ? 'master.trading_user.company' : 'master.trading_user.name') : 'master.trading_user.name',
        header: 'master',
        enableSorting: false,
    },
    {
        accessorKey: 'master_meta_login',
        header: 'account_no',
    },
    {
        accessorKey: 'subscription_number',
        header: 'subscription_number',
    },
    {
        accessorKey: 'subscription_amount',
        header: 'amount',
        cell: info => '$ ' + formatAmount(info.getValue()),
    },
    {
        accessorKey: 'settlement_period',
        header: 'roi_period',
        cell: info => info.getValue() + ' ' + trans('public.days'),
    },
    {
        accessorKey: 'join_days',
        header: 'join_day',
        cell: info => info.getValue() + ' ' + trans('public.days'),
    },
    {
        accessorKey: 'status',
        header: 'status',
        enableSorting: false,
        cell: ({ row }) => h(StatusBadge, {value: row.original.status}),
    },
    {
        accessorKey: 'action',
        header: 'table_action',
        enableSorting: false,
        cell: ({ row }) => h(Action, {
            subscription: row.original,
            terms: props.terms,
        }),
    },
];

watch([currentPage, action], ([currentPageValue, newAction]) => {
    if (newAction === 'goToFirstPage' || newAction === 'goToLastPage') {
        getResults(currentPageValue, pageSize.value);
    } else {
        getResults(currentPageValue, pageSize.value);
    }
});

watch(
    [sorting, pageSize],
    ([sortingValue, pageSizeValue]) => {
        getResults(1, pageSizeValue, sortingValue);
    }
);

watch(
    [meta_login],
    debounce(([metaLoginValue]) => {
        getResults(1, pageSize.value, metaLoginValue, sorting.value);
    }, 300)
);

watchEffect(() => {
    if (usePage().props.title !== null) {
        getResults();
    }
});
</script>

<template>
    <AuthenticatedLayout :title="$t('public.pamm_subscriptions')">
        <template #header>
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    {{ $t('public.pamm_subscriptions') }}
                </h2>
            </div>
        </template>

        <div class="flex flex-col sm:flex-row items-center gap-5 self-stretch">
            <div class="w-full">
                <InputIconWrapper>
                    <template #icon>
                        <SearchIcon aria-hidden="true" class="w-5 h-5" />
                    </template>
                    <Input
                        withIcon
                        id="search"
                        type="text"
                        class="w-full block dark:border-transparent"
                        :placeholder="$t('public.search_name_and_account_no_placeholder')"
                        v-model="search"
                    />
                </InputIconWrapper>
            </div>
            <div class="w-full">
                <BaseListbox
                    v-model="type"
                    :options="typeFilter"
                    :placeholder="$t('public.filters_placeholder')"
                    class="w-full"
                />
            </div>

            <div class="w-full sm:w-auto">
                <Button
                    type="button"
                    variant="primary-transparent"
                    @click="clearFilter"
                    class="w-full justify-center"
                >
                    {{ $t('public.clear') }}
                </Button>
            </div>
        </div>

        <PammSubscription
            :terms="terms"
            :search="search"
            :type="type"
            @update:master="master = $event"
            @update:meta_login="meta_login = $event"
        />

        <div class="p-5 my-8 bg-white overflow-hidden md:overflow-visible rounded-xl shadow-md dark:bg-gray-900">
            <div class="text-lg font-semibold">
                {{ $t('public.pamm_return') }}
            </div>
            <div
                v-if="subscriptions.data.length === 0"
                class="w-full flex items-center justify-center"
                >
                <NoData/>
            </div>
            <div v-else>
                <TanStackTable
                    :data="subscriptions"
                    :columns="columns"
                    @update:sorting="sorting = $event"
                    @update:action="action = $event"
                    @update:currentPage="currentPage = $event"
                />
            </div>
        </div>
    </AuthenticatedLayout>
</template>
