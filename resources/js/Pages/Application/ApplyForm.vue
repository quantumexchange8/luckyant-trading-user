<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import Card from "primevue/card";
import Button from "primevue/button";
import dayjs from "dayjs";
import Stepper from 'primevue/stepper';
import StepList from 'primevue/steplist';
import StepPanels from 'primevue/steppanels';
import Step from 'primevue/step';
import StepPanel from 'primevue/steppanel';
import {computed, ref, watch} from "vue";
import {useForm, usePage} from "@inertiajs/vue3";
import InputLabel from "@/Components/Label.vue";
import InputText from "primevue/inputtext";
import InputNumber from "primevue/inputnumber";
import RadioButton from "primevue/radiobutton";
import Checkbox from "primevue/checkbox";
import Select from "primevue/select";
import InputError from "@/Components/InputError.vue";
import {
    IconArrowNarrowRight,
    IconArrowNarrowLeft,
    IconPlaneOff
} from "@tabler/icons-vue";
import {useLangObserver} from "@/Composables/localeObserver.js";
import DatePicker from "@/Components/DatePicker.vue";

const props = defineProps({
    application: Object
});

const activeStep = ref(1);
const totalSteps = 4;
const {locale} = useLangObserver();

const form = useForm({
    step: 1,
    application_form_id: props.application.id,
    candidates_count: 0,
    candidate_details: {},
});

watch(() => form.candidates_count, (newCount) => {
    // Reset candidate_details
    form.candidate_details = {};
    for (let i = 1; i <= newCount; i++) {
        form.candidate_details[i] = {
            name: '',
            email: '',
            gender: '',
            country: '',
            phone_number: '',
            identity_number: '',
            requires_transport: '',
            requires_accommodation: '',
            requires_ib_training: '',
            transport_details: {
                name: '',
                gender: '',
                country: '',
                dob: '',
                phone_number: '',
                identity_number: '',
                departure_address: '',
                return_address: '',
            },
        };
    }
});

const transportCandidates = computed(() => {
    const indices = [];
    for (let i = 1; i <= form.candidates_count; i++) {
        if (form.candidate_details[i]?.requires_transport) {
            indices.push(i);
        }
    }
    return indices;
});

const countries = ref([]);
const loadingCountries = ref(false);

const getCountries = async () => {
    loadingCountries.value = true;
    try {
        const response = await axios.get('/getCountries');
        countries.value = response.data;
    } catch (error) {
        console.error('Error fetching countries:', error);
    } finally {
        loadingCountries.value = false;
    }
};

getCountries();

const handleContinue = () => {
    form.step = activeStep.value;
    form.post(route('application.submitApplicationForm'), {
        onSuccess: () => {
            if (activeStep.value < totalSteps) {
                activeStep.value += 1;
            }
        },
        preserveScroll: true,
        preserveState: true,
    });
};
</script>

<template>
    <AuthenticatedLayout :title="$t('public.apply_form')">
        <template #header>
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    {{ $t('public.apply_form') }}
                </h2>
            </div>
        </template>

        <div class="flex flex-col gap-5 items-center self-stretch w-full">
            <Card class="w-full">
                <template #content>
                    <div class="flex flex-col gap-5 self-stretch w-full">
                        <div class="flex gap-5 items-center self-stretch">
                            <div class="w-full text-gray-950 dark:text-white font-bold text-wrap">
                                {{ application.title }}
                            </div>
                        </div>

                        <div class="text-sm dark:text-white min-w-full prose dark:prose-invert" v-html="application.content"></div>
                    </div>
                </template>
            </Card>

            <Stepper v-model:value="activeStep" linear class="w-full">
                <StepList>
                    <Step :value="1">{{ $t('public.candidate_enrollment') }}</Step>
                    <Step :value="2">{{ $t('public.candidate_detail') }}</Step>
                    <Step :value="3">{{ $t('public.transport_information') }}</Step>
                    <Step :value="4">{{ $t('public.final_check') }}</Step>
                </StepList>
                <StepPanels>
                    <StepPanel :value="1">
                        <div class="flex flex-col gap-1 items-start self-stretch">
                            <InputLabel
                                for="candidates_count"
                                :invalid="!!form.errors.candidates_count"
                            >
                                {{ $t('public.candidate_enrollment_count') }}
                            </InputLabel>
                            <InputNumber
                                v-model="form.candidates_count"
                                inputId="candidates_count"
                                fluid
                                class="w-full"
                                :min="0"
                                :invalid="!!form.errors.candidates_count"
                            />
                            <InputError :message="form.errors.candidates_count" />
                        </div>

                        <div class="flex pt-6 justify-end">
                            <Button
                                type="button"
                                size="small"
                                severity="secondary"
                                @click="handleContinue"
                                :disabled="form.processing"
                            >
                                {{ $t('public.next') }}
                                <IconArrowNarrowRight size="20" stroke-witdth="1.5" />
                            </Button>
                        </div>
                    </StepPanel>
                    <StepPanel :value="2">
                        <div class="grid md:grid-cols-2 gap-3 w-full">
                            <Card
                                v-for="index in form.candidates_count"
                                :key="'input-' + index"
                            >
                                <template #content>
                                    <div class="flex flex-col gap-3 items-center self-stretch">
                                        <span class="font-bold text-sm text-gray-950 dark:text-white w-full text-left">{{ `${$t('public.candidate')} #${index}` }}</span>
                                        <!-- Candidate Name -->
                                        <div class="flex flex-col items-start gap-1 self-stretch">
                                            <InputLabel
                                                :for="'candidate_name_' + index"
                                                :value="`${$t('public.name')}`"
                                                :invalid="!!form.errors[`candidate_details.${index}.name`]"
                                            />
                                            <InputText
                                                :id="'candidate_name_' + index"
                                                type="text"
                                                class="block w-full"
                                                v-model="form.candidate_details[index].name"
                                                :placeholder="`${$t('public.name')} #${index}`"
                                                :invalid="!!form.errors[`candidate_details.${index}.name`]"
                                            />
                                            <InputError :message="form.errors[`candidate_details.${index}.name`]" />
                                        </div>

                                        <!-- Candidate Email -->
                                        <div class="flex flex-col items-start gap-1 self-stretch">
                                            <InputLabel
                                                :for="'candidate_email_' + index"
                                                :value="`${$t('public.email')}`"
                                                :invalid="!!form.errors[`candidate_details.${index}.email`]"
                                            />
                                            <InputText
                                                :id="'candidate_email_' + index"
                                                type="email"
                                                class="block w-full"
                                                v-model="form.candidate_details[index].email"
                                                :placeholder="`${$t('public.email')} #${index}`"
                                                :invalid="!!form.errors[`candidate_details.${index}.email`]"
                                            />
                                            <InputError :message="form.errors[`candidate_details.${index}.email`]" />
                                        </div>

                                        <!-- Candidate Gender -->
                                        <div class="flex flex-col items-start gap-1 self-stretch">
                                            <InputLabel
                                                :for="'candidate_gender_' + index"
                                                :value="`${$t('public.gender')}`"
                                                :invalid="!!form.errors[`candidate_details.${index}.gender`]"
                                            />
                                            <div class="flex flex-wrap gap-5">
                                                <div class="flex items-center gap-2">
                                                    <RadioButton
                                                        v-model="form.candidate_details[index].gender"
                                                        :inputId="'male_' + index"
                                                        value="male"
                                                    />
                                                    <label :for="'male_' + index" class="dark:text-white">{{ $t('public.male') }}</label>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <RadioButton
                                                        v-model="form.candidate_details[index].gender"
                                                        :inputId="'female_' + index"
                                                        value="female"
                                                    />
                                                    <label :for="'female_' + index" class="dark:text-white">{{ $t('public.female') }}</label>
                                                </div>
                                            </div>
                                            <InputError :message="form.errors[`candidate_details.${index}.gender`]" />
                                        </div>

                                        <!-- Candidate Country -->
                                        <div class="flex flex-col items-start gap-1 self-stretch">
                                            <InputLabel
                                                :for="'candidate_country_' + index"
                                                :value="`${$t('public.country')}`"
                                                :invalid="!!form.errors[`candidate_details.${index}.country`]"
                                            />
                                            <Select
                                                v-model="form.candidate_details[index].country"
                                                :options="countries"
                                                optionLabel="name"
                                                :placeholder="$t('public.select_country')"
                                                class="w-full"
                                                filter
                                                :filter-fields="['name', 'iso2', 'currency']"
                                                :loading="loadingCountries"
                                                :invalid="!!form.errors[`candidate_details.${index}.country`]"
                                            >
                                                <template #value="slotProps">
                                                    <div v-if="slotProps.value" class="flex items-center">
                                                        <div class="leading-tight">{{ JSON.parse(slotProps.value.translations)[locale] || slotProps.value.name }}</div>
                                                    </div>
                                                    <span v-else class="text-surface-400 dark:text-surface-500">{{ slotProps.placeholder }}</span>
                                                </template>
                                                <template #option="slotProps">
                                                    <div class="flex items-center gap-1">
                                                        <img
                                                            v-if="slotProps.option.iso2"
                                                            :src="`https://flagcdn.com/w40/${slotProps.option.iso2.toLowerCase()}.png`"
                                                            :alt="slotProps.option.iso2"
                                                            width="18"
                                                            height="12"
                                                        />
                                                        <div class="max-w-[200px] truncate">{{ JSON.parse(slotProps.option.translations)[locale] || slotProps.option.name }}</div>
                                                    </div>
                                                </template>
                                            </Select>
                                            <InputError :message="form.errors[`candidate_details.${index}.country`]" />
                                        </div>

                                        <!-- Candidate Phone -->
                                        <div class="flex flex-col items-start gap-1 self-stretch">
                                            <InputLabel
                                                :for="'candidate_phone_number_' + index"
                                                :value="`${$t('public.mobile_phone')}`"
                                                :invalid="!!form.errors[`candidate_details.${index}.phone_number`]"
                                            />
                                            <InputText
                                                :id="'candidate_phone_number_' + index"
                                                type="text"
                                                class="block w-full"
                                                v-model="form.candidate_details[index].phone_number"
                                                :placeholder="`${$t('public.mobile_phone')} #${index}`"
                                                :invalid="!!form.errors[`candidate_details.${index}.phone_number`]"
                                            />
                                            <InputError :message="form.errors[`candidate_details.${index}.phone_number`]" />
                                        </div>

                                        <!-- Candidate ID -->
                                        <div class="flex flex-col items-start gap-1 self-stretch">
                                            <InputLabel
                                                :for="'candidate_identity_number_' + index"
                                                :value="`${$t('public.ic_passport_number')}`"
                                                :invalid="!!form.errors[`candidate_details.${index}.identity_number`]"
                                            />
                                            <InputText
                                                :id="'candidate_identity_number_' + index"
                                                type="text"
                                                class="block w-full"
                                                v-model="form.candidate_details[index].identity_number"
                                                :placeholder="`${$t('public.ic_passport_number')} #${index}`"
                                                :invalid="!!form.errors[`candidate_details.${index}.identity_number`]"
                                            />
                                            <InputError :message="form.errors[`candidate_details.${index}.identity_number`]" />
                                        </div>

                                        <!-- Candidate Request -->
                                        <div class="flex flex-col items-start gap-1 self-stretch">
                                            <InputLabel
                                                :for="'candidate_request_' + index"
                                                :value="`${$t('public.request')}`"
                                                :invalid="!!form.errors[`candidate_details.${index}.request`]"
                                            />
                                            <div class="flex flex-col gap-1">
                                                <div class="flex items-center gap-2">
                                                    <Checkbox
                                                        v-model="form.candidate_details[index].requires_transport"
                                                        :inputId="'transport_' + index"
                                                        value="accept"
                                                        binary
                                                    />
                                                    <label :for="'transport_' + index" class="dark:text-white text-sm">{{ $t('public.transport') }}</label>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <Checkbox
                                                        v-model="form.candidate_details[index].requires_accommodation"
                                                        :inputId="'accommodation_' + index"
                                                        value="accept"
                                                        binary
                                                    />
                                                    <label :for="'accommodation_' + index" class="dark:text-white text-sm">{{ $t('public.accommodation') }}</label>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <Checkbox
                                                        v-model="form.candidate_details[index].requires_ib_training"
                                                        :inputId="'ib_training_' + index"
                                                        value="accept"
                                                        binary
                                                    />
                                                    <label :for="'ib_training_' + index" class="dark:text-white text-sm">{{ $t('public.ib_training') }}</label>
                                                </div>
                                            </div>
                                            <InputError :message="form.errors[`candidate_details.${index}.request`]" />
                                        </div>
                                    </div>
                                </template>
                            </Card>
                        </div>

                        <div class="pt-6 flex justify-between items-center self-stretch w-full">
                            <Button
                                type="button"
                                size="small"
                                severity="secondary"
                                :disabled="form.processing"
                                @click="activeStep = 1"
                            >
                                <IconArrowNarrowLeft size="20" stroke-witdth="1.5" />
                                {{ $t('public.back') }}
                            </Button>

                            <Button
                                type="button"
                                size="small"
                                severity="secondary"
                                @click="handleContinue"
                                :disabled="form.processing"
                            >
                                {{ $t('public.next') }}
                                <IconArrowNarrowRight size="20" stroke-witdth="1.5" />
                            </Button>
                        </div>
                    </StepPanel>
                    <StepPanel :value="3">
                        <div v-if="transportCandidates.length > 0" class="grid md:grid-cols-2 gap-3 w-full">
                            <Card
                                v-for="index in transportCandidates"
                                :key="'transport-' + index"
                            >
                                <template #content>
                                    <div class="flex flex-col gap-3 items-start self-stretch">
                                        <span class="font-bold text-sm text-gray-950 dark:text-white">{{ $t('public.transport_details_for') }} {{ form.candidate_details[index].name ? form.candidate_details[index].name : ($t('public.candidate') + ' #' + index) }}</span>

                                        <!-- Transport Name -->
                                        <div class="flex flex-col items-start gap-1 self-stretch">
                                            <InputLabel
                                                :for="'transport_name_' + index"
                                                :value="$t('public.name_on_ic_passport')"
                                                :invalid="!!form.errors[`candidate_details.${index}.transport_details.name`]"
                                            />
                                            <InputText
                                                :id="'transport_name_' + index"
                                                type="text"
                                                class="block w-full"
                                                v-model="form.candidate_details[index].transport_details.name"
                                                :placeholder="$t('public.name_on_ic_passport')"
                                                :invalid="!!form.errors[`candidate_details.${index}.transport_details.name`]"
                                            />
                                            <InputError :message="form.errors[`candidate_details.${index}.transport_details.name`]" />
                                        </div>

                                        <!-- Transport Gender -->
                                        <div class="flex flex-col items-start gap-1 self-stretch">
                                            <InputLabel
                                                :for="'transport_gender' + index"
                                                :value="`${$t('public.gender')}`"
                                                :invalid="!!form.errors[`candidate_details.${index}.transport_details.gender`]"
                                            />
                                            <div class="flex flex-wrap gap-5">
                                                <div class="flex items-center gap-2">
                                                    <RadioButton
                                                        v-model="form.candidate_details[index].transport_details.gender"
                                                        :inputId="'transport_male_' + index"
                                                        value="male"
                                                    />
                                                    <label :for="'transport_male_' + index" class="dark:text-white">{{ $t('public.male') }}</label>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <RadioButton
                                                        v-model="form.candidate_details[index].transport_details.gender"
                                                        :inputId="'transport_female_' + index"
                                                        value="female"
                                                    />
                                                    <label :for="'transport_female_' + index" class="dark:text-white">{{ $t('public.female') }}</label>
                                                </div>
                                            </div>
                                            <InputError :message="form.errors[`candidate_details.${index}.transport_details.gender`]" />
                                        </div>

                                        <!-- Transport Country -->
                                        <div class="flex flex-col items-start gap-1 self-stretch">
                                            <InputLabel
                                                :for="'candidate_country_' + index"
                                                :value="`${$t('public.country')}`"
                                                :invalid="!!form.errors[`candidate_details.${index}.transport_details.country`]"
                                            />
                                            <Select
                                                v-model="form.candidate_details[index].transport_details.country"
                                                :options="countries"
                                                optionLabel="name"
                                                :placeholder="$t('public.select_country')"
                                                class="w-full"
                                                filter
                                                :filter-fields="['name', 'iso2', 'currency']"
                                                :loading="loadingCountries"
                                                :invalid="!!form.errors[`candidate_details.${index}.transport_details.country`]"
                                            >
                                                <template #value="slotProps">
                                                    <div v-if="slotProps.value" class="flex items-center">
                                                        <div class="leading-tight">{{ JSON.parse(slotProps.value.translations)[locale] || slotProps.value.name }}</div>
                                                    </div>
                                                    <span v-else class="text-surface-400 dark:text-surface-500">{{ slotProps.placeholder }}</span>
                                                </template>
                                                <template #option="slotProps">
                                                    <div class="flex items-center gap-1">
                                                        <img
                                                            v-if="slotProps.option.iso2"
                                                            :src="`https://flagcdn.com/w40/${slotProps.option.iso2.toLowerCase()}.png`"
                                                            :alt="slotProps.option.iso2"
                                                            width="18"
                                                            height="12"
                                                        />
                                                        <div class="max-w-[200px] truncate">{{ JSON.parse(slotProps.option.translations)[locale] || slotProps.option.name }}</div>
                                                    </div>
                                                </template>
                                            </Select>
                                            <InputError :message="form.errors[`candidate_details.${index}.transport_details.country`]" />
                                        </div>

                                        <!-- Transport DOB -->
                                        <div class="flex flex-col items-start gap-1 self-stretch">
                                            <InputLabel
                                                :for="'transport_dob_' + index"
                                                :value="$t('public.date_of_birth')"
                                                :invalid="!!form.errors[`candidate_details.${index}.transport_details.dob`]"
                                            />
                                            <DatePicker
                                                :invalid="!!form.errors[`candidate_details.${index}.transport_details.dob`]"
                                                @update:date="form.candidate_details[index].transport_details.dob = $event"
                                            />
                                            <InputError :message="form.errors[`candidate_details.${index}.transport_details.dob`]" />
                                        </div>

                                        <!-- Transport Phone Number -->
                                        <div class="flex flex-col items-start gap-1 self-stretch">
                                            <InputLabel
                                                :for="'transport_phone_' + index"
                                                :value="$t('public.mobile_phone')"
                                                :invalid="!!form.errors[`candidate_details.${index}.transport_details.phone_number`]"
                                            />
                                            <InputText
                                                :id="'transport_phone_' + index"
                                                type="text"
                                                class="block w-full"
                                                v-model="form.candidate_details[index].transport_details.phone_number"
                                                :placeholder="$t('public.mobile_phone')"
                                                :invalid="!!form.errors[`candidate_details.${index}.transport_details.phone_number`]"
                                            />
                                            <InputError :message="form.errors[`candidate_details.${index}.transport_details.phone_number`]" />
                                        </div>

                                        <!-- Transport ID Number -->
                                        <div class="flex flex-col items-start gap-1 self-stretch">
                                            <InputLabel
                                                :for="'transport_id_' + index"
                                                :value="$t('public.ic_passport_number')"
                                                :invalid="!!form.errors[`candidate_details.${index}.transport_details.identity_number`]"
                                            />
                                            <InputText
                                                :id="'transport_id_' + index"
                                                type="text"
                                                class="block w-full"
                                                v-model="form.candidate_details[index].transport_details.identity_number"
                                                :placeholder="$t('public.ic_passport_number')"
                                                :invalid="!!form.errors[`candidate_details.${index}.transport_details.identity_number`]"
                                            />
                                            <InputError :message="form.errors[`candidate_details.${index}.transport_details.identity_number`]" />
                                        </div>

                                        <!-- Transport Departure -->
                                        <div class="flex flex-col items-start gap-1 self-stretch">
                                            <InputLabel
                                                :for="'transport_departure_address_' + index"
                                                :value="$t('public.departure_address')"
                                                :invalid="!!form.errors[`candidate_details.${index}.transport_details.departure_address`]"
                                            />
                                            <InputText
                                                :id="'transport_departure_address_' + index"
                                                type="text"
                                                class="block w-full"
                                                v-model="form.candidate_details[index].transport_details.departure_address"
                                                :placeholder="$t('public.departure_address')"
                                                :invalid="!!form.errors[`candidate_details.${index}.transport_details.departure_address`]"
                                            />
                                            <InputError :message="form.errors[`candidate_details.${index}.transport_details.departure_address`]" />
                                        </div>

                                        <!-- Transport Return -->
                                        <div class="flex flex-col items-start gap-1 self-stretch">
                                            <InputLabel
                                                :for="'transport_return_address_' + index"
                                                :value="$t('public.return_address')"
                                                :invalid="!!form.errors[`candidate_details.${index}.transport_details.return_address`]"
                                            />
                                            <InputText
                                                :id="'transport_return_address_' + index"
                                                type="text"
                                                class="block w-full"
                                                v-model="form.candidate_details[index].transport_details.return_address"
                                                :placeholder="$t('public.return_address')"
                                                :invalid="!!form.errors[`candidate_details.${index}.transport_details.return_address`]"
                                            />
                                            <InputError :message="form.errors[`candidate_details.${index}.transport_details.return_address`]" />
                                        </div>
                                    </div>
                                </template>
                            </Card>
                        </div>

                        <div v-else class="w-full">
                            <Card>
                                <template #content>
                                    <div class="flex flex-col gap-5 items-center self-stretch">
                                        <div class="flex items-center justify-center w-20 h-20 rounded-full grow-0 shrink-0 bg-success-100 dark:bg-success-800 text-success-700 dark:text-success-100">
                                            <IconPlaneOff size="32" stroke-width="1.5" />
                                        </div>
                                        <div class="flex flex-col gap-1 items-center self-stretch">
                                            <div class="text-lg font-medium text-gray-950 dark:text-white">
                                                {{ $t('public.no_transport_required') }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                {{ $t('public.no_transport_required_caption') }}
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </Card>
                        </div>

                        <div class="pt-6 flex justify-between items-center self-stretch w-full">
                            <Button
                                type="button"
                                size="small"
                                severity="secondary"
                                :disabled="form.processing"
                                @click="activeStep = 2"
                            >
                                <IconArrowNarrowLeft size="20" stroke-witdth="1.5" />
                                {{ $t('public.back') }}
                            </Button>

                            <Button
                                type="button"
                                size="small"
                                severity="secondary"
                                @click="handleContinue"
                                :disabled="form.processing"
                            >
                                {{ $t('public.next') }}
                                <IconArrowNarrowRight size="20" stroke-witdth="1.5" />
                            </Button>
                        </div>
                    </StepPanel>

                    <StepPanel :value="4">
                        <div class="grid md:grid-cols-2 gap-3 w-full">
                            <Card
                                v-for="index in form.candidates_count"
                                :key="'check-' + index"
                            >
                                <template #content>
                                    <div class="flex flex-col gap-3 items-center self-stretch">
                                        <span class="font-bold text-sm text-gray-950 dark:text-white w-full text-left">{{ `${$t('public.candidate')} #${index}` }}</span>

                                        <div class="flex flex-col gap-1 items-start w-full">
                                            <!-- Name -->
                                            <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                                                <div class="w-[140px] text-gray-500 text-xs font-medium">
                                                    {{ $t('public.name') }}
                                                </div>
                                                <div class="text-gray-950 dark:text-white text-sm font-medium">
                                                    {{ form.candidate_details[index].name }}
                                                </div>
                                            </div>

                                            <!-- Email -->
                                            <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                                                <div class="w-[140px] text-gray-500 text-xs font-medium">
                                                    {{ $t('public.email') }}
                                                </div>
                                                <div class="text-gray-950 dark:text-white text-sm font-medium">
                                                    {{ form.candidate_details[index].email }}
                                                </div>
                                            </div>

                                            <!-- Gender -->
                                            <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                                                <div class="w-[140px] text-gray-500 text-xs font-medium">
                                                    {{ $t('public.gender') }}
                                                </div>
                                                <div class="text-gray-950 dark:text-white text-sm font-medium">
                                                    {{ $t(`public.${form.candidate_details[index].gender}`) }}
                                                </div>
                                            </div>

                                            <!-- Country -->
                                            <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                                                <div class="w-[140px] text-gray-500 text-xs font-medium">
                                                    {{ $t('public.country') }}
                                                </div>
                                                <div class="text-gray-950 dark:text-white text-sm font-medium">
                                                    <div
                                                        v-if="form.candidate_details[index].country"
                                                         class="flex items-center gap-1"
                                                    >
                                                        <img
                                                            v-if="form.candidate_details[index].country.iso2"
                                                            :src="`https://flagcdn.com/w40/${form.candidate_details[index].country.iso2.toLowerCase()}.png`"
                                                            :alt="form.candidate_details[index].country.iso2"
                                                            width="18"
                                                            height="12"
                                                        />
                                                        <div class="leading-tight">
                                                            {{ JSON.parse(form.candidate_details[index].country.translations)[locale] || form.candidate_details[index].country.name }}
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
                                                    {{ form.candidate_details[index].phone_number }}
                                                </div>
                                            </div>

                                            <!-- ID No -->
                                            <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                                                <div class="w-[140px] text-gray-500 text-xs font-medium">
                                                    {{ $t('public.ic_passport_number') }}
                                                </div>
                                                <div class="text-gray-950 dark:text-white text-sm font-medium">
                                                    {{ form.candidate_details[index].identity_number }}
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex flex-col gap-3 items-center self-stretch w-full pt-3">
                                            <span class="font-medium text-sm text-gray-600 dark:text-gray-400 w-full text-left">{{ $t('public.transport_information') }}</span>

                                            <!-- Transport details -->
                                            <div
                                                v-if="form.candidate_details[index].requires_transport"
                                                class="flex flex-col items-start gap-1 self-stretch"
                                            >
                                                <!-- Name -->
                                                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                                                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                                                        {{ $t('public.name') }}
                                                    </div>
                                                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                                                        {{ form.candidate_details[index].transport_details.name }}
                                                    </div>
                                                </div>

                                                <!-- Gender -->
                                                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                                                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                                                        {{ $t('public.gender') }}
                                                    </div>
                                                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                                                        {{ $t(`public.${form.candidate_details[index].transport_details.gender}`) }}
                                                    </div>
                                                </div>

                                                <!-- Country -->
                                                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                                                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                                                        {{ $t('public.country') }}
                                                    </div>
                                                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                                                        <div
                                                            v-if="form.candidate_details[index].country"
                                                            class="flex items-center gap-1"
                                                        >
                                                            <img
                                                                v-if="form.candidate_details[index].transport_details.country.iso2"
                                                                :src="`https://flagcdn.com/w40/${form.candidate_details[index].transport_details.country.iso2.toLowerCase()}.png`"
                                                                :alt="form.candidate_details[index].transport_details.country.iso2"
                                                                width="18"
                                                                height="12"
                                                            />
                                                            <div class="leading-tight">
                                                                {{ JSON.parse(form.candidate_details[index].country.translations)[locale] || form.candidate_details[index].country.name }}
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
                                                        {{ dayjs(form.candidate_details[index].transport_details.dob).format('YYYY-MM-DD') }}
                                                    </div>
                                                </div>

                                                <!-- Phone -->
                                                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                                                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                                                        {{ $t('public.mobile_phone') }}
                                                    </div>
                                                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                                                        {{ form.candidate_details[index].transport_details.phone_number }}
                                                    </div>
                                                </div>

                                                <!-- ID No -->
                                                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                                                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                                                        {{ $t('public.ic_passport_number') }}
                                                    </div>
                                                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                                                        {{ form.candidate_details[index].transport_details.identity_number }}
                                                    </div>
                                                </div>

                                                <!-- Departure -->
                                                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                                                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                                                        {{ $t('public.departure_address') }}
                                                    </div>
                                                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                                                        {{ form.candidate_details[index].transport_details.departure_address }}
                                                    </div>
                                                </div>

                                                <!-- Departure -->
                                                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                                                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                                                        {{ $t('public.return_address') }}
                                                    </div>
                                                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                                                        {{ form.candidate_details[index].transport_details.return_address }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div v-else class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                                                <div class="w-[140px] text-gray-500 text-xs font-medium">
                                                    {{ $t('public.transport') }}
                                                </div>
                                                <div class="text-gray-950 dark:text-white text-sm font-medium">
                                                    -
                                                </div>
                                            </div>

                                        </div>

                                        <div class="flex flex-col gap-3 items-center self-stretch w-full pt-3">
                                            <span class="font-medium text-sm text-gray-600 dark:text-gray-400 w-full text-left">{{ $t('public.additional_information') }}</span>

                                            <div class="flex flex-col gap-1 items-start w-full">
                                                <!-- Accommodation details -->
                                                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                                                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                                                        {{ $t('public.accommodation') }}
                                                    </div>
                                                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                                                        {{ form.candidate_details[index].requires_accommodation ? $t('public.yes') : '-' }}
                                                    </div>
                                                </div>

                                                <!-- Accommodation details -->
                                                <div class="flex flex-col md:flex-row md:items-center gap-1 self-stretch">
                                                    <div class="w-[140px] text-gray-500 text-xs font-medium">
                                                        {{ $t('public.ib_training') }}
                                                    </div>
                                                    <div class="text-gray-950 dark:text-white text-sm font-medium">
                                                        {{ form.candidate_details[index].requires_ib_training ? $t('public.yes') : '-' }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </Card>
                        </div>

                        <div class="pt-6 flex justify-between items-center self-stretch w-full">
                            <Button
                                type="button"
                                size="small"
                                severity="secondary"
                                :disabled="form.processing"
                                @click="activeStep = 3"
                            >
                                <IconArrowNarrowLeft size="20" stroke-witdth="1.5" />
                                {{ $t('public.back') }}
                            </Button>

                            <Button
                                type="button"
                                size="small"
                                @click="handleContinue"
                                :disabled="form.processing"
                            >
                                {{ $t('public.confirm_submit') }}
                            </Button>
                        </div>
                    </StepPanel>
                </StepPanels>
            </Stepper>
        </div>
    </AuthenticatedLayout>
</template>
