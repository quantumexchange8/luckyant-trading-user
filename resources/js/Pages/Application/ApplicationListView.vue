<script setup>
import NoData from "@/Components/NoData.vue";
import {ref, watch} from "vue";
import debounce from "lodash/debounce.js";
import Tag from "primevue/tag";
import Paginator from "primevue/paginator";
import Card from "primevue/card";
import Skeleton from "primevue/skeleton";
import Button from "primevue/button";
import {IconHandClick} from "@tabler/icons-vue";
import ApplicationListViewAction from "@/Pages/Application/ApplicationListViewAction.vue";

const props = defineProps({
    applicationsCount: Number
});

const isLoading = ref(false);
const applications = ref([]);
const currentPage = ref(1);
const rowsPerPage = ref(6);
const totalRecords = ref(0);
const search = ref('');
const selectedDate = ref([]);

const getResults = async (page = 1, rowsPerPage = 6) => {
    isLoading.value = true;

    try {
        let url = `/application/getApplicationForms?page=${page}&limit=${rowsPerPage}`;

        if (search.value) {
            url += `&search=${search.value}`;
        }

        if (
            selectedDate.value &&
            Array.isArray(selectedDate.value) &&
            typeof selectedDate.value[0] !== 'undefined' &&
            typeof selectedDate.value[1] !== 'undefined'
        ) {
            const startDateISO = new Date(selectedDate.value[0]).toISOString();
            const endDateISO = new Date(selectedDate.value[1]).toISOString();
            console.log(selectedDate.value);
            url += `&start_date=${startDateISO}&end_date=${endDateISO}`;
        }

        const response = await axios.get(url);
        applications.value = response.data.applications.data;
        totalRecords.value = response.data.totalRecords;
        currentPage.value = response.data.currentPage;
    } catch (error) {
        console.error('Error getting applications:', error);
    } finally {
        isLoading.value = false;
    }
};

// Initial call to populate data
getResults(currentPage.value, rowsPerPage.value);

const onPageChange = (event) => {
    currentPage.value = event.page + 1;
    getResults(currentPage.value, rowsPerPage.value);
};

const clearJoinDate = () => {
    selectedDate.value = [];
}

watch(search, debounce(() => {
    getResults(currentPage.value, rowsPerPage.value);
}, 300));

watch(selectedDate, (newDateRange) => {
    if (
        Array.isArray(newDateRange) &&
        newDateRange.length === 2 &&
        newDateRange[0] &&
        newDateRange[1]
    ) {
        getResults(currentPage.value, rowsPerPage.value);
    } else if (newDateRange.length === 0) {
        getResults(currentPage.value, rowsPerPage.value);
    }
})

const clearSearch = () => {
    search.value = '';
}
</script>

<template>
    <div class="flex flex-col items-center gap-5">
        <!-- Filter -->


        <!-- No Data -->
        <div
            v-if="applicationsCount === 0"
            class="flex justify-center items-center w-full"
        >
            <NoData />
        </div>

        <!-- Content -->
        <div v-else class="w-full">
            <div v-if="isLoading">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 self-stretch">
                    <Card
                        v-for="(application, index) in applicationsCount > 6 ? 6 : applicationsCount"
                        :key="index"
                    >
                        <template #content>
                            <div class="flex flex-col gap-5 items-center self-stretch">
                                <div class="flex gap-5 items-center self-stretch">
                                    <div class="w-full text-gray-950 dark:text-white font-bold text-wrap">
                                        <Skeleton width="10rem" height="1.5rem" class="mb-3"></Skeleton>
                                    </div>
                                </div>

                                <div class="text-sm dark:text-white w-full">
                                    <Skeleton height="1rem" class="w-full"></Skeleton>
                                    <Skeleton height="1rem" class="w-full my-1"></Skeleton>
                                    <Skeleton height="1rem" class="w-full my-1"></Skeleton>
                                    <Skeleton height="1rem" class="w-full mb-1"></Skeleton>
                                </div>
                            </div>
                        </template>
                    </Card>
                </div>
            </div>

            <div v-else-if="!applications.length">
                <NoData />
            </div>

            <div v-else>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5 self-stretch">
                    <Card
                        v-for="(application, index) in applications"
                        :key="index"
                    >
                        <template #content>
                            <div class="flex flex-col gap-5 items-center self-stretch">
                                <div class="flex gap-5 items-center self-stretch">
                                    <div class="w-full text-gray-950 dark:text-white font-bold text-wrap">
                                        {{ application.title }}
                                    </div>
                                </div>

                                <div class="text-sm dark:text-white line-clamp-4 w-full prose dark:prose-invert" v-html="application.content"></div>

                                <ApplicationListViewAction
                                    :application="application"
                                />
                            </div>
                        </template>
                    </Card>
                </div>

                <Paginator
                    :first="(currentPage - 1) * rowsPerPage"
                    :rows="rowsPerPage"
                    :totalRecords="totalRecords"
                    @page="onPageChange"
                />
            </div>
        </div>
    </div>
</template>
