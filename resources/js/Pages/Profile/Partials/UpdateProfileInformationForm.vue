<script setup>
import InputError from '@/Components/InputError.vue'
import Label from '@/Components/Label.vue'
import Button from '@/Components/Button.vue'
import Input from '@/Components/Input.vue'
import BaseListbox from "@/Components/BaseListbox.vue";
import { Link, useForm, usePage } from '@inertiajs/vue3'
import CountryLists from "../../../../../public/data/countries.json";
import {ref, watchEffect, watch} from "vue";
import {XIcon} from '@heroicons/vue/outline'
import Badge from "@/Components/Badge.vue";
import {RadioGroup, RadioGroupLabel, RadioGroupOption} from "@headlessui/vue";
import AvatarInput from "@/Pages/Profile/Partials/AvatarInput.vue";
import Modal from "@/Components/Modal.vue";

const props = defineProps({
    mustVerifyEmail: Boolean,
    status: String,
    frontIdentityImg: String,
    backIdentityImg: String,
    profileImg: String,
    nationalities: Array,
    countries: Array,
    rank: String,
})

const user = usePage().props.auth.user
const kycApproval = ref(usePage().props.auth.user.kyc_approval)

const form = useForm({
    name: user.name,
    username: user.username,
    email: user.email,
    dial_code: user.dial_code,
    phone: user.phone,
    dob: user.dob,
    gender: user.gender,
    address: user.address_1,
    country: user.country,
    nationality: user.nationality,
    identification_number: user.identification_number,
    proof_front: null,
    proof_back: null,
    profile_photo: null,
})

const submit = () => {
    if (selected.value) {
        form.gender = selected.value.value
    }
    form.post(route('profile.update'), {
        onSuccess: () => {
            closeModal();
        },
        onError: () => {
            closeModal();
        }
    })
}

watch(() => form.country, (newCountry) => {
    const foundNationality = props.nationalities.find(nationality => nationality.id === newCountry);
    if (foundNationality) {
        form.nationality = foundNationality.value;
    } else {
        form.nationality = null; // Reset if not found
    }
});

const selectedProofFrontFile = ref(null);
const selectedProofFrontFileName = ref(null);
const handleProofFront = (event) => {
    const frontProofInput = event.target;
    const file = frontProofInput.files[0];

    if (file) {
        // Display the selected image
        const reader = new FileReader();
        reader.onload = () => {
            selectedProofFrontFile.value = reader.result;
        };
        reader.readAsDataURL(file);
        selectedProofFrontFileName.value = file.name;
        form.proof_front = event.target.files[0];
    } else {
        selectedProofFrontFile.value = null;
    }
};

const removeProofFront = () => {
    selectedProofFrontFile.value = null;
};

const selectedProofBackFile = ref(null);
const selectedProofBackFileName = ref(null);
const handleProofBack = (event) => {
    const backProofInput = event.target;
    const file = backProofInput.files[0];

    if (file) {
        // Display the selected image
        const reader = new FileReader();
        reader.onload = () => {
            selectedProofBackFile.value = reader.result;
        };
        reader.readAsDataURL(file);
        selectedProofBackFileName.value = file.name;
        form.proof_back = event.target.files[0];
    } else {
        selectedProofBackFile.value = null;
    }
};

const removeProofBack = () => {
    selectedProofBackFile.value = null;
};

const statusVariant = (userKyc) => {
    if (userKyc === 'Pending') return 'warning';
    if (userKyc === 'Verified') return 'success';
    if (userKyc === 'Unverified') return 'danger';
}

const genders = [
    {
        name: 'male',
        value: 'male',
    },
    {
        name: 'female',
        value: 'female',
    },
]

const getUserGender = (user_gender) => {
    return genders.find(gender => gender.value === user_gender);
}
const selected = ref(getUserGender(user.gender));
const confirmModal = ref(false);

const openConfirmModal = () => {
    confirmModal.value = true;
}

const closeModal = () => {
    confirmModal.value = false
}

watchEffect(() => {
    if (usePage().props.title !== null) {
        kycApproval.value = usePage().props.auth.user.kyc_approval;
    }
});
</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ $t('public.details') }}
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ $t('public.profile_update_message') }}
            </p>
        </header>

        <div class="flex flex-col sm:flex-row gap-5">
            <div class="flex flex-col gap-4 items-center justify-center w-full sm:w-1/3">
                <AvatarInput class="h-24 w-24 rounded-full" v-model="form.profile_photo" :default-src="profileImg ? profileImg : 'https://img.freepik.com/free-icon/user_318-159711.jpg'" />
                <InputError :message="form.errors.profile_photo" class="mt-2" />
                <div class="flex flex-col items-center">
                    <div class="font-semibold text-gray-800 dark:text-white">
                        {{ user.name }}
                    </div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">
                        {{ user.email }}
                    </div>
                </div>
                <div class="flex gap-2 w-full items-center justify-center">
                    <Badge
                        variant="primary"
                        width="auto"
                    >
                        <span class="text-sm">{{ props.rank }}</span>
                    </Badge>
                    <div
                        class="flex px-2 py-1 justify-center text-white rounded-lg hover:-translate-y-1 transition-all duration-300 ease-in-out w-20"
                        :class="{
                            'bg-success-400 dark:bg-success-500': kycApproval === 'Verified',
                            'bg-error-400 dark:bg-error-500': kycApproval === 'Unverified',
                            'bg-warning-400 dark:bg-warning-500': kycApproval === 'Pending',
                        }"
                    >
                        <span class="text-sm">{{ $t('public.' + kycApproval.toLowerCase()) }}</span>
                    </div>
                </div>
            </div>
            <form class="w-full sm:w-2/3">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <Label for="name" :value="$t('public.name')" />

                        <Input
                            id="name"
                            type="text"
                            class="block w-full"
                            v-model="form.name"
                            autofocus
                            autocomplete="name"
                        />

                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="username" :value="$t('public.username')" />

                        <Input
                            id="username"
                            type="text"
                            class="block w-full"
                            v-model="form.username"
                            autocomplete="username"
                        />

                        <InputError class="mt-2" :message="form.errors.username" />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="email" :value="$t('public.email')" />

                        <Input
                            id="email"
                            type="email"
                            class="block w-full"
                            v-model="form.email"
                            autocomplete="email"
                        />

                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div class="space-y-1.5">
                        <Label
                            for="phone"
                            :value="$t('public.mobile_phone')"
                        />
                        <div class="flex gap-3">
                            <BaseListbox
                                class="w-[240px]"
                                :options="CountryLists"
                                v-model="form.dial_code"
                                with-img
                                is-phone-code
                                :error="!!form.errors.phone"
                            />
                            <Input
                                id="phone"
                                type="text"
                                class="block w-full"
                                :placeholder="$t('public.phone_placeholder')"
                                v-model="form.phone"
                                :invalid="form.errors.phone"
                            />
                        </div>
                        <InputError :message="form.errors.phone"/>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="dob" :value="$t('public.date_of_birth')" />

                        <Input
                            id="dob"
                            type="text"
                            class="block w-full"
                            v-model="form.dob"
                            autocomplete="dob"
                        />

                        <InputError class="mt-2" :message="form.errors.dob" />
                    </div>

                    <div class="space-y-1.5">
                        <Label
                            for="gender"
                            :value="$t('public.gender')"
                        />
                        <RadioGroup v-model="selected">
                            <RadioGroupLabel class="sr-only">{{ $t('public.signal_status') }}</RadioGroupLabel>
                            <div class="flex gap-3 items-center self-stretch w-full">
                                <RadioGroupOption
                                    as="template"
                                    v-for="(gender, index) in genders"
                                    :key="index"
                                    :value="gender"
                                    v-slot="{ active, checked }"
                                >
                                    <div
                                        :class="[
                                    active
                                      ? 'ring-0 ring-white ring-offset-0'
                                      : '',
                                    checked ? 'border-primary-600 dark:border-white bg-primary-500 dark:bg-gray-600 text-white' : 'border-gray-300 bg-white dark:bg-gray-700',
                                ]"
                                        class="relative flex cursor-pointer rounded-xl border p-3 focus:outline-none w-full"
                                    >
                                        <div class="flex items-center w-full">
                                            <div class="text-sm flex flex-col gap-3 w-full">
                                                <RadioGroupLabel
                                                    as="div"
                                                    class="font-medium"
                                                >
                                                    <div class="flex justify-center items-center gap-3">
                                                        {{ $t('public.' + gender.name) }}
                                                    </div>
                                                </RadioGroupLabel>
                                            </div>
                                        </div>
                                    </div>
                                </RadioGroupOption>
                            </div>
                            <InputError :message="form.errors.gender" class="mt-2" />
                        </RadioGroup>
                    </div>

                    <div class="space-y-1.5">
                        <Label
                            for="country"
                            :value="$t('public.country')"
                        />
                        <BaseListbox
                            v-model="form.country"
                            :options="countries"
                            :placeholder="$t('public.country')"
                            class="w-full"
                            :error="!!form.errors.country"
                        />
                        <InputError class="mt-2" :message="form.errors.country" />
                    </div>

                    <div class="space-y-1.5">
                        <Label
                            for="nationality"
                            :value="$t('public.nationality')"
                        />
                        <BaseListbox
                            v-model="form.nationality"
                            :options="nationalities"
                            :placeholder="$t('public.nationality')"
                            class="w-full"
                            :error="!!form.errors.nationality"
                        />
                        <InputError class="mt-2" :message="form.errors.nationality" />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="address" :value="$t('public.address')" />
                        <Input
                            id="address"
                            type="text"
                            class="block w-full"
                            v-model="form.address"
                            autocomplete="address"
                            :invalid="form.errors.address"
                        />
                        <InputError class="mt-2" :message="form.errors.address" />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="identification_number" :value="$t('public.identification_no')" />
                        <Input
                            id="identification_number"
                            type="text"
                            class="block w-full"
                            v-model="form.identification_number"
                            :invalid="form.errors.identification_number"
                        />
                        <InputError class="mt-2" :message="form.errors.identification_number" />
                    </div>

                    <div class="space-y-1.5">
                        <Label class="text-sm dark:text-white" for="proof_front" :value="$t('public.proof_of_identity') + ' (' + $t('public.front') + ')'" />
                        <div class="flex gap-3">
                            <input
                                ref="frontProofInput"
                                id="proof_front"
                                type="file"
                                class="hidden"
                                accept="image/*"
                                @change="handleProofFront"
                                :disabled="kycApproval === 'Verified'"
                            />
                            <Button
                                type="button"
                                variant="primary"
                                @click="$refs.frontProofInput.click()"
                                :disabled="kycApproval === 'Verified'"
                            >
                                {{ $t('public.browse') }}
                            </Button>
                            <InputError :message="form.errors.proof_front" class="mt-2" />
                        </div>
                        <div
                            v-if="selectedProofFrontFile"
                            class="relative w-full py-2 pl-4 flex justify-between rounded-lg border focus:ring-1 focus:outline-none"
                            :class="[
                                    {
                                          'border-error-300 focus-within:ring-error-300 hover:border-error-300 focus-within:border-error-300 focus-within:shadow-error-light dark:border-error-600 dark:focus-within:ring-error-600 dark:hover:border-error-600 dark:focus-within:border-error-600 dark:focus-within:shadow-error-dark': form.errors.proof_front,
                                          'border-gray-light-300 dark:border-gray-dark-800 focus:ring-primary-400 hover:border-primary-400 focus-within:border-primary-400 focus-within:shadow-primary-light dark:focus-within:ring-primary-500 dark:hover:border-primary-500 dark:focus-within:border-primary-500 dark:focus-within:shadow-primary-dark': !form.errors.proof_front,
                                    }
                                ]"
                        >
                            <div class="inline-flex items-center gap-3">
                                <img :src="selectedProofFrontFile" alt="Selected Image" class="max-w-full h-9 object-contain rounded" />
                                <div class="text-gray-light-900 dark:text-gray-dark-50">
                                    {{ selectedProofFrontFileName }}
                                </div>
                            </div>
                            <Button
                                type="button"
                                variant="transparent"
                                @click="removeProofFront"
                            >
                                <XIcon class="text-gray-700 w-5 h-5" />
                            </Button>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label class="text-sm dark:text-white" for="proof_back" :value="$t('public.proof_of_identity') + ' (' + $t('public.back') + ')'" />
                        <div class="flex gap-3">
                            <input
                                ref="backProofInput"
                                id="proof_back"
                                type="file"
                                class="hidden"
                                accept="image/*"
                                @change="handleProofBack"
                                :disabled="kycApproval === 'Verified'"
                            />
                            <Button
                                type="button"
                                variant="primary"
                                @click="$refs.backProofInput.click()"
                                :disabled="kycApproval === 'Verified'"
                            >
                                {{ $t('public.browse') }}
                            </Button>
                            <InputError :message="form.errors.proof_back" class="mt-2" />
                        </div>
                        <div
                            v-if="selectedProofBackFile"
                            class="relative w-full py-2 pl-4 flex justify-between rounded-lg border focus:ring-1 focus:outline-none"
                            :class="[
                                {
                                      'border-error-300 focus-within:ring-error-300 hover:border-error-300 focus-within:border-error-300 focus-within:shadow-error-light dark:border-error-600 dark:focus-within:ring-error-600 dark:hover:border-error-600 dark:focus-within:border-error-600 dark:focus-within:shadow-error-dark': form.errors.proof_back,
                                      'border-gray-light-300 dark:border-gray-dark-800 focus:ring-primary-400 hover:border-primary-400 focus-within:border-primary-400 focus-within:shadow-primary-light dark:focus-within:ring-primary-500 dark:hover:border-primary-500 dark:focus-within:border-primary-500 dark:focus-within:shadow-primary-dark': !form.errors.proof_back,
                                }
                            ]"
                        >
                            <div class="inline-flex items-center gap-3">
                                <img :src="selectedProofBackFile" alt="Selected Image" class="max-w-full h-9 object-contain rounded" />
                                <div class="text-gray-light-900 dark:text-gray-dark-50">
                                    {{ selectedProofBackFileName }}
                                </div>
                            </div>
                            <Button
                                type="button"
                                variant="transparent"
                                @click="removeProofBack"
                            >
                                <XIcon class="text-gray-700 w-5 h-5" />
                            </Button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <div class="flex justify-end mt-8">
            <Button @click.prevent="openConfirmModal">{{ $t('public.save') }}</Button>
        </div>
    </section>

    <Modal :show="confirmModal" :title="$t('public.profile_update_confirmation')" max-width="md" @close="closeModal">
        <div class="dark:text-white">
            {{ $t('public.profile_update_alert') }}
        </div>
        <div class="pt-5 grid grid-cols-2 mt-5 gap-4 w-full">
            <Button variant="transparent" type="button" class="justify-center" @click.prevent="closeModal">
                {{$t('public.cancel')}}
            </Button>
            <Button class="justify-center" @click="submit" :disabled="form.processing">{{$t('public.confirm')}}</Button>
        </div>
    </Modal>
</template>
