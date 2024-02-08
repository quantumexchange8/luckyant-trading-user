<script setup>
import InputError from '@/Components/InputError.vue'
import Label from '@/Components/Label.vue'
import Button from '@/Components/Button.vue'
import Input from '@/Components/Input.vue'
import BaseListbox from "@/Components/BaseListbox.vue";
import { Link, useForm, usePage } from '@inertiajs/vue3'
import CountryLists from "../../../../../public/data/countries.json";
import {ref} from "vue";
import {XIcon} from '@heroicons/vue/outline'

const props = defineProps({
    mustVerifyEmail: Boolean,
    status: String,
    frontIdentityImg: String,
    backIdentityImg: String,
    profileImg: String,
})

const user = usePage().props.auth.user
const kycApproval = usePage().props.auth.user.kyc_approval

const form = useForm({
    name: user.name,
    email: user.email,
    dial_code: user.dial_code,
    phone: user.phone,
    proof_front: null,
    proof_back: null,
    profile_photo: null,
})

const submit = () => {
    form.post(route('profile.update'))
}

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

const selectedProfilePhotoFile = ref(null);
const selectedProfilePhotoFileName = ref(null);
const handleProfilePhoto = (event) => {
    const profilePhotoInput = event.target;
    const file = profilePhotoInput.files[0];

    if (file) {
        // Display the selected image
        const reader = new FileReader();
        reader.onload = () => {
            selectedProfilePhotoFile.value = reader.result;
        };
        reader.readAsDataURL(file);
        selectedProfilePhotoFileName.value = file.name;
        form.profile_photo = event.target.files[0];
    } else {
        selectedProfilePhotoFile.value = null;
    }
};

const removeProfilePhoto = () => {
    selectedProfilePhotoFile.value = null;
};


</script>

<template>
    <section>
        <header>
            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                Profile Information
            </h2>

            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Update your account's profile information and email address.
            </p>
        </header>

        <form>
            <section class="grid grid-cols-1 md:grid-cols-2 gap-10">
                <div class="mt-6 space-y-6">
                    <div class="space-y-1.5">
                        <Label for="name" value="Name" />

                        <Input
                            id="name"
                            type="text"
                            class="block w-full"
                            v-model="form.name"
                            required
                            autofocus
                            autocomplete="name"
                            :disabled="kycApproval === 'Verified'"
                        />

                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="email" value="Email" />

                        <Input
                            id="email"
                            type="email"
                            class="block w-full"
                            v-model="form.email"
                            required
                            autocomplete="email"
                            disabled
                        />

                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div
                        v-if="props.mustVerifyEmail && user.email_verified_at === null"
                    >
                        <p class="text-sm mt-2 text-gray-800 dark:text-gray-200">
                            Your email address is unverified.
                            <Link
                                :href="route('verification.send')"
                                method="post"
                                as="button"
                                class="underline text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800"
                            >
                                Click here to re-send the verification email.
                            </Link>
                        </p>

                        <div
                            v-show="props.status === 'verification-link-sent'"
                            class="mt-2 font-medium text-sm text-green-600 dark:text-green-400"
                        >
                            A new verification link has been sent to your email address.
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label
                            for="phone"
                            :value="$t('public.Mobile Phone')"
                        />
                        <div class="flex gap-3">
                            <BaseListbox
                                class="w-[180px]"
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
                                placeholder="123456789"
                                v-model="form.phone"
                                :invalid="form.errors.phone"
                            />
                        </div>
                        <InputError :message="form.errors.phone"/>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button @click="submit" :disabled="form.processing">Save</Button>
                    </div>
                </div>
                <div class="mt-6 space-y-6">
                    <div class="space-y-1.5">
                        <Label class="text-sm dark:text-white" for="proof_front" value="Proof of Identity (Front)" />
                        <div class="flex gap-3">
                            <input
                                ref="frontProofInput"
                                id="proof_front"
                                type="file"
                                class="hidden"
                                accept="image/*"
                                @change="handleProofFront"
                            />
                            <Button
                                type="button"
                                variant="primary"
                                @click="$refs.frontProofInput.click()"
                                :disabled="kycApproval === 'Verified'"
                            >
                                Browse
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
                        <Label class="text-sm dark:text-white" for="proof_back" value="Proof of Identity (Back)" />
                        <div class="flex gap-3">
                            <input
                                ref="backProofInput"
                                id="proof_back"
                                type="file"
                                class="hidden"
                                accept="image/*"
                                @change="handleProofBack"
                            />
                            <Button
                                type="button"
                                variant="primary"
                                @click="$refs.backProofInput.click()"
                                :disabled="kycApproval === 'Verified'"
                            >
                                Browse
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

                    <div class="space-y-1.5">
                        <Label class="text-sm dark:text-white" for="profile_photo" value="Profile Photo" />
                        <div class="flex gap-3">
                            <input
                                ref="profilePhotoInput"
                                id="profile_photo"
                                type="file"
                                class="hidden"
                                accept="image/*"
                                @change="handleProfilePhoto"
                            />
                            <Button
                                type="button"
                                variant="primary"
                                @click="$refs.profilePhotoInput.click()"
                            >
                                Browse
                            </Button>
                            <InputError :message="form.errors.profile_photo" class="mt-2" />
                        </div>
                        <div
                            v-if="selectedProfilePhotoFile"
                            class="relative w-full py-2 pl-4 flex justify-between rounded-lg border focus:ring-1 focus:outline-none"
                            :class="[
                                    {
                                          'border-error-300 focus-within:ring-error-300 hover:border-error-300 focus-within:border-error-300 focus-within:shadow-error-light dark:border-error-600 dark:focus-within:ring-error-600 dark:hover:border-error-600 dark:focus-within:border-error-600 dark:focus-within:shadow-error-dark': form.errors.profile_photo,
                                          'border-gray-light-300 dark:border-gray-dark-800 focus:ring-primary-400 hover:border-primary-400 focus-within:border-primary-400 focus-within:shadow-primary-light dark:focus-within:ring-primary-500 dark:hover:border-primary-500 dark:focus-within:border-primary-500 dark:focus-within:shadow-primary-dark': !form.errors.profile_photo,
                                    }
                                ]"
                        >
                            <div class="inline-flex items-center gap-3">
                                <img :src="selectedProfilePhotoFile" alt="Selected Image" class="max-w-full h-9 object-contain rounded" />
                                <div class="text-gray-light-900 dark:text-gray-dark-50">
                                    {{ selectedProfilePhotoFileName }}
                                </div>
                            </div>
                            <Button
                                type="button"
                                variant="transparent"
                                @click="removeProfilePhoto"
                            >
                                <XIcon class="text-gray-700 w-5 h-5" />
                            </Button>
                        </div>
                    </div>
                </div>
            </section>
        </form>
    </section>
</template>
