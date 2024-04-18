<script setup>
import {
    useVueTable,
    FlexRender,
    getCoreRowModel,
    getSortedRowModel
} from "@tanstack/vue-table";
import {ref, watch, h} from "vue";
import Button from "@/Components/Button.vue";
import {
    ArrowsDownIcon,
    ArrowsUpIcon,
    SwitchVertical01Icon,
    ChevronLeftIcon,
    ChevronRightIcon,
    ChevronLeftDoubleIcon,
    ChevronRightDoubleIcon,
} from "./Icons/outline.jsx";
import Input from "@/Components/Input.vue";

const props = defineProps({
    data: Object,
    columns: Array,
})

const sorting = ref([]);
const pageIndex = ref(1);
const emit = defineEmits(['update:sorting', 'update:action', 'update:currentPage']);

let table = useVueTable({
    data: props.data.data,
    columns: props.columns,
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    manualPagination: true,
    manualSorting: true,
    rowCount: props.data.total,
    onSortingChange: updaterOrValue => {
        sorting.value =
            typeof updaterOrValue === 'function'
                ? updaterOrValue(sorting.value)
                : updaterOrValue

        emit('update:sorting', sorting.value[0]);
    },
    autoResetPage: false,
});

watch(() => props.data, (newData) => {
    // Update table data when members value changes
    table = useVueTable({
        data: newData.data,
        columns: props.columns,
        getCoreRowModel: getCoreRowModel(),
        getSortedRowModel: getSortedRowModel(),
        manualPagination: true,
        manualSorting: true,
        rowCount: newData.total,
        state: {
            pagination: {
                pageIndex: newData.current_page - 1,
                pageSize: newData.per_page
            },
            get sorting() {
                return sorting.value
            }
        },
        onSortingChange: updaterOrValue => {
            sorting.value =
                typeof updaterOrValue === 'function'
                    ? updaterOrValue(sorting.value)
                    : updaterOrValue

            emit('update:sorting', sorting.value[0]);
        },
        autoResetPage: false,
    });
});

const internalChange = ref(false);

const handlePageChange = (action, index) => {
    internalChange.value = true;
    emit('update:action', action);
    if (action === 'previous') {
        pageIndex.value = index;
        emit('update:currentPage', pageIndex.value);
    } else {
        pageIndex.value = index + 2;
        emit('update:currentPage', pageIndex.value);
    }
    internalChange.value = false;
};

const handleFirstLastPage = (action) => {
    internalChange.value = true;
    if (action === 'goToFirstPage') {
        emit('update:action', action);
        emit('update:currentPage', 1);
        pageIndex.value = 1;
    } else {
        emit('update:action', action);
        emit('update:currentPage', table.getPageCount());
        pageIndex.value = table.getPageCount();
    }
    internalChange.value = false;
};

watch(pageIndex, (newPageNumber) => {
    if (!internalChange.value) {
        emit('update:currentPage', newPageNumber);
    }
});

</script>

<template>
    <div class="relative overflow-x-auto sm:rounded-lg">
        <table class="w-[850px] md:w-full text-sm text-left text-gray-500 dark:text-gray-400 mt-5">
            <thead class="text-xs font-medium text-gray-400 uppercase dark:bg-transparent dark:text-gray-400 border-b dark:border-gray-800">
            <tr v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                <th
                    v-for="header in headerGroup.headers"
                    scope="col"
                    class="p-3"
                    :class="{
                        'cursor-pointer select-none': header.column.getCanSort(),
                        'bg-primary-100 dark:bg-gray-800': header.column.getIsSorted()
                    }"
                    @click="header.column.getToggleSortingHandler()?.($event)"
                >
                    <div class="flex items-center gap-2">
                        <FlexRender
                            :render="$t('public.' + header.column.columnDef.header)"
                            :props="header.getContext()"
                        />
                        <div v-if="header.column.getIsSorted()">
                            <ArrowsUpIcon
                                v-if="header.column.getIsSorted() === 'asc'"
                                class="w-4 h-4 text-primary-600 dark:text-white"
                            />
                            <ArrowsDownIcon
                                v-else-if="header.column.getIsSorted() === 'desc'"
                                class="w-4 h-4 text-primary-600 dark:text-white"
                            />
                        </div>
                        <div v-else>
                            <SwitchVertical01Icon v-if="header.column.getCanSort()" class="w-4 h-4" />
                        </div>
                    </div>
                </th>
            </tr>
            </thead>
            <tbody>
            <tr
                v-for="row in table.getRowModel().rows"
                :key="row.id"
                class="bg-white dark:bg-transparent text-xs font-normal text-gray-900 dark:text-white border-b dark:border-gray-800"
            >
                <td v-for="cell in row.getVisibleCells()" :key="cell.id" class="p-3">
                    <FlexRender
                        :render="cell.column.columnDef.cell"
                        :props="cell.getContext()"
                    />
                </td>
            </tr>
            </tbody>
        </table>
    </div>
    <div class="py-4 flex flex-col md:flex-row gap-2 md:items-center md:justify-between w-full">
        <div class="text-sm">
            {{ $t('public.pagination_result', { page_index: table.getState().pagination.pageIndex + 1, page_count: table.getPageCount(), row_count: table.getRowCount() }) }}
        </div>

        <div class="flex items-center justify-center gap-4">
            <Button
                variant="gray"
                type="button"
                @click="handleFirstLastPage('goToFirstPage')"
                pill
                size="sm"
                v-slot="{iconSizeClasses}"
                :disabled="!table.getCanPreviousPage()"
            >
                <ChevronLeftDoubleIcon class="w-4" />
            </Button>
            <Button
                type="button"
                variant="gray"
                @click="handlePageChange('previous', table.getState().pagination.pageIndex)"
                :disabled="!table.getCanPreviousPage()"
                pill
                size="sm"
                v-slot="{iconSizeClasses}"
            >
                <ChevronLeftIcon class="w-4" />
            </Button>
            <Input
                id="page"
                type="number"
                min="1"
                :max="table.getPageCount()"
                class="block w-20"
                placeholder="Page"
                v-model="pageIndex"
            />
            <Button
                type="button"
                variant="gray"
                @click="handlePageChange('next', table.getState().pagination.pageIndex)"
                :disabled="!table.getCanNextPage()"
                pill
                size="sm"
                v-slot="{iconSizeClasses}"
            >
                <ChevronRightIcon class="w-4" />
            </Button>
            <Button
                variant="gray"
                type="button"
                @click="handleFirstLastPage('goToLastPage')"
                pill
                size="sm"
                v-slot="{iconSizeClasses}"
                :disabled="!table.getCanNextPage()"
            >
                <ChevronRightDoubleIcon class="w-4" />
            </Button>
        </div>
    </div>
</template>
