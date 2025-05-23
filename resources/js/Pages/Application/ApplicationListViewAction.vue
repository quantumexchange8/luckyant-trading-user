<script setup>
import Button from "primevue/button";
import Tag from "primevue/tag";
import {IconHandClick} from "@tabler/icons-vue";
import {ref} from "vue";
import Dialog from "primevue/dialog";
import Skeleton from "primevue/skeleton";
import dayjs from "dayjs";
import {useLangObserver} from "@/Composables/localeObserver.js";
import NoData from "@/Components/NoData.vue";

const props = defineProps({
    application: Object
});

const visible = ref(false);
const isLoading = ref(false);
const applicants = ref([]);
const {locale} = useLangObserver();

const getApplicants = async () => {
    isLoading.value = true;
    try {
        const response = await axios.get(`/application/getApplicants?application_id=${props.application.id}`);
        applicants.value = response.data.applicants;

    } catch (error) {
        console.error('Error fetching countries:', error);
    } finally {
        isLoading.value = false;
    }
};

const openDialog = () => {
    visible.value = true;
    getApplicants();
}

const closeDialog = () => {
    visible.value = false;
}

const getSeverity = (status) => {
    switch (status) {
        case 'approved':
            return 'success';

        case 'rejected':
            return 'danger';

        case 'pending':
            return 'info';
    }
}
</script>

<template>
    <div class="flex items-center justify-between gap-3 w-full">
        <Button
            severity="secondary"
            size="small"
            :label="$t('public.view_details')"
            class="w-full"
            @click="openDialog"
        />

<!--        <Button-->
<!--            as="a"-->
<!--            :href="route('application.application_form', { id: application.id })"-->
<!--            type="button"-->
<!--            size="small"-->
<!--            class="flex gap-2 w-full"-->
<!--        >-->
<!--            <IconHandClick size="20" stroke-width="1.5" />-->
<!--            {{ $t('public.apply_form') }}-->
<!--        </Button>-->
    </div>

    <Dialog
        v-model:visible="visible"
        modal
        :header="$t('public.view_details')"
        class="dialog-xs md:dialog-lg"
    >
        <div v-if="isLoading" class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="flex flex-col mb-4">
                <Skeleton width="10rem" class="mb-2"></Skeleton>
                <Skeleton width="5rem" class="mb-2"></Skeleton>
                <Skeleton height=".5rem"></Skeleton>
            </div>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div
                v-for="(applicant, index) in applicants"
            >
                <div class="flex flex-col gap-3 items-center self-stretch">
                    <div class="font-bold text-sm text-gray-950 dark:text-white w-full text-left bg-gray-100 dark:bg-gray-800 p-3">
                        {{ `${$t('public.applicant')} #${index + 1}` }}
                    </div>

                    <div class="flex flex-col gap-1 items-start w-full">
                        <!-- Name -->
                        <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                            <div class="w-[140px] text-gray-500 text-xs font-medium">
                                {{ $t('public.name') }}
                            </div>
                            <div class="text-gray-950 dark:text-white text-sm font-medium">
                                {{ applicant.name }}
                            </div>
                        </div>

                        <!-- Email -->
                        <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                            <div class="w-[140px] text-gray-500 text-xs font-medium">
                                {{ $t('public.email') }}
                            </div>
                            <div class="text-gray-950 dark:text-white text-sm font-medium">
                                {{ applicant.email }}
                            </div>
                        </div>

                        <!-- Gender -->
                        <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                            <div class="w-[140px] text-gray-500 text-xs font-medium">
                                {{ $t('public.gender') }}
                            </div>
                            <div class="text-gray-950 dark:text-white text-sm font-medium">
                                {{ $t(`public.${applicant.gender}`) }}
                            </div>
                        </div>

                        <!-- Country -->
                        <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                            <div class="w-[140px] text-gray-500 text-xs font-medium">
                                {{ $t('public.country') }}
                            </div>
                            <div class="text-gray-950 dark:text-white text-sm font-medium">
                                <div
                                    v-if="applicant.country"
                                    class="flex items-center gap-1"
                                >
                                    <img
                                        v-if="applicant.country.iso2"
                                        :src="`https://flagcdn.com/w40/${applicant.country.iso2.toLowerCase()}.png`"
                                        :alt="applicant.country.iso2"
                                        width="18"
                                        height="12"
                                    />
                                    <div class="leading-tight">
                                        {{ JSON.parse(applicant.country.translations)[locale] || applicant.country.name }}
                                    </div>
                                </div>
                                <span v-else class="text-surface-400 dark:text-surface-500">-</span>
                            </div>
                        </div>

                        <!-- Phone -->
                        <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                            <div class="w-[140px] text-gray-500 text-xs font-medium">
                                {{ $t('public.mobile_phone') }}
                            </div>
                            <div class="text-gray-950 dark:text-white text-sm font-medium">
                                {{ applicant.phone_number }}
                            </div>
                        </div>

                        <!-- ID No -->
                        <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                            <div class="w-[140px] text-gray-500 text-xs font-medium">
                                {{ $t('public.ic_passport_number') }}
                            </div>
                            <div class="text-gray-950 dark:text-white text-sm font-medium">
                                {{ applicant.identity_number }}
                            </div>
                        </div>

                        <!-- Ticket Type -->
                        <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                            <div class="w-[140px] text-gray-500 text-xs font-medium">
                                {{ $t('public.ticket_type') }}
                            </div>
                            <div class="text-gray-950 dark:text-white text-sm font-medium">
                                {{ $t(`public.${applicant.ticket_type}`) }}
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 items-center self-stretch w-full pt-3">
                        <span class="font-medium text-sm text-gray-600 dark:text-gray-400 w-full text-left">{{ $t('public.flight_information') }}</span>

                        <!-- Transport details -->
                        <div
                            v-if="applicant.requires_transport"
                            class="flex flex-col items-start gap-1 self-stretch"
                        >
                            <!-- Name -->
                            <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                                <div class="w-[140px] text-gray-500 text-xs font-medium">
                                    {{ $t('public.name') }}
                                </div>
                                <div class="text-gray-950 dark:text-white text-sm font-medium">
                                    {{ applicant.transport_detail.name }}
                                </div>
                            </div>

                            <!-- Gender -->
                            <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                                <div class="w-[140px] text-gray-500 text-xs font-medium">
                                    {{ $t('public.gender') }}
                                </div>
                                <div class="text-gray-950 dark:text-white text-sm font-medium">
                                    {{ $t(`public.${applicant.transport_detail.gender}`) }}
                                </div>
                            </div>

                            <!-- Country -->
                            <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                                <div class="w-[140px] text-gray-500 text-xs font-medium">
                                    {{ $t('public.country') }}
                                </div>
                                <div class="text-gray-950 dark:text-white text-sm font-medium">
                                    <div
                                        v-if="applicant.transport_detail.country"
                                        class="flex items-center gap-1"
                                    >
                                        <img
                                            v-if="applicant.transport_detail.country.iso2"
                                            :src="`https://flagcdn.com/w40/${applicant.transport_detail.country.iso2.toLowerCase()}.png`"
                                            :alt="applicant.transport_detail.country.iso2"
                                            width="18"
                                            height="12"
                                        />
                                        <div class="leading-tight">
                                            {{ JSON.parse(applicant.transport_detail.country.translations)[locale] || applicant.transport_detail.country.name }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- DOB -->
                            <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                                <div class="w-[140px] text-gray-500 text-xs font-medium">
                                    {{ $t('public.date_of_birth') }}
                                </div>
                                <div class="text-gray-950 dark:text-white text-sm font-medium">
                                    {{ dayjs(applicant.transport_detail.dob).format('YYYY-MM-DD') }}
                                </div>
                            </div>

                            <!-- Phone -->
                            <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                                <div class="w-[140px] text-gray-500 text-xs font-medium">
                                    {{ $t('public.mobile_phone') }}
                                </div>
                                <div class="text-gray-950 dark:text-white text-sm font-medium">
                                    {{ applicant.transport_detail.phone_number }}
                                </div>
                            </div>

                            <!-- ID No -->
                            <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                                <div class="w-[140px] text-gray-500 text-xs font-medium">
                                    {{ $t('public.ic_passport_number') }}
                                </div>
                                <div class="text-gray-950 dark:text-white text-sm font-medium">
                                    {{ applicant.transport_detail.identity_number }}
                                </div>
                            </div>

                            <!-- Departure -->
                            <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                                <div class="w-[140px] text-gray-500 text-xs font-medium">
                                    {{ $t('public.departure_address') }}
                                </div>
                                <div class="text-gray-950 dark:text-white text-sm font-medium">
                                    {{ applicant.transport_detail.departure_address }}
                                </div>
                            </div>

                            <!-- Departure -->
                            <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                                <div class="w-[140px] text-gray-500 text-xs font-medium">
                                    {{ $t('public.return_address') }}
                                </div>
                                <div class="text-gray-950 dark:text-white text-sm font-medium">
                                    {{ applicant.transport_detail.return_address }}
                                </div>
                            </div>
                        </div>
                        <div v-else class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                            <div class="w-[140px] text-gray-500 text-xs font-medium">
                                {{ $t('public.flight') }}
                            </div>
                            <div class="text-gray-950 dark:text-white text-sm font-medium">
                                {{ $t('public.no') }}
                            </div>
                        </div>

                    </div>

                    <div class="flex flex-col gap-3 items-center self-stretch w-full pt-3">
                        <span class="font-medium text-sm text-gray-600 dark:text-gray-400 w-full text-left">{{ $t('public.additional_information') }}</span>

                        <div class="flex flex-col gap-1 items-start w-full">
                            <!-- Status -->
                            <div class="flex flex-col md:flex-row items-start md:items-center gap-1 self-stretch">
                                <div class="w-[140px] text-gray-500 text-xs font-medium">
                                    {{ $t('public.status') }}
                                </div>
                                <Tag
                                    :severity="getSeverity(applicant.status)"
                                    :value="$t(`public.${applicant.status}`)"
                                />
                            </div>

                            <!-- Remarks -->
                            <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                                <div class="w-[140px] text-gray-500 text-xs font-medium">
                                    {{ $t('public.remarks') }}
                                </div>
                                <div class="text-gray-950 dark:text-white text-sm font-medium">
                                    {{ applicant.remarks ?? '-' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="!applicants.length" class="flex justify-center items-center md:col-span-2">
                <NoData />
            </div>
        </div>
    </Dialog>
</template>
