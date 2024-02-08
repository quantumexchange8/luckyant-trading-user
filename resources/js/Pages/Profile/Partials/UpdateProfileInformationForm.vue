<script setup>
import InputError from '@/Components/InputError.vue'
import Label from '@/Components/Label.vue'
import Button from '@/Components/Button.vue'
import Input from '@/Components/Input.vue'
import BaseListbox from "@/Components/BaseListbox.vue";
import { Link, useForm, usePage } from '@inertiajs/vue3'
import InputIconWrapper from '@/Components/InputIconWrapper.vue'
import { MailIcon, PhoneIcon, KeyIcon, HomeIcon } from '@heroicons/vue/outline'
import {computed, ref, watch} from "vue";

const props = defineProps({
    mustVerifyEmail: Boolean,
    status: String,
    countries: Array,
    frontIdentityImg: String,
    backIdentityImg: String,
    profileImg: String,
})

const user = usePage().props.auth.user

const form = useForm({
    name: user.name,
    email: user.email,
    country: user.country,
    phone: user.phone,
    address_1: user.address_1,
    address_2: user.address_2,
    kyc_approval: user.kyc_approval,
    // proof_front: props.frontIdentityImg,
    // proof_back: props.backIdentityImg,
    // profile_photo: props.profileImg,
})

const selectedCountry = ref(form.country);

function onchangeDropdown() {
    const selectedCountryName = selectedCountry.value;
    const country = props.countries.find((country) => country.label === selectedCountryName);

    if (country) {
        form.phone = `${country.phone_code}`;
        form.country = selectedCountry;
    }
}

const submit = () => {
    form.post(route('profile.update'))
}

watch(selectedCountry, () => {
    onchangeDropdown();
});
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

        <form
            @submit.prevent="form.patch(route('profile.update'))"
        >
            <section class="grid grid-cols-1 md:grid-cols-2 gap-10">
                <div class="mt-6 space-y-6">
                    <div>
                        <Label for="name" value="Name" />

                        <Input
                            id="name"
                            type="text"
                            class="mt-1 block w-full"
                            v-model="form.name"
                            required
                            autofocus
                            autocomplete="name"
                        />

                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div>
                        <Label class="text-[14px] dark:text-white mb-2" for="country" value="Country" />

                        <BaseListbox
                            v-model="selectedCountry"
                            :options="props.countries"
                            :error="form.errors.country"
                            disabled
                        />

                        <InputError class="mt-2" :message="form.errors.country" />
                    </div>
                    
                    <div>
                        <Label class="text-[14px] dark:text-white mb-2" for="phone" value="Phone Number" />

                        <InputIconWrapper>
                            <template #icon>
                                <PhoneIcon aria-hidden="true" class="w-5 h-5 text-gray-400" />
                            </template>
                            <Input
                                withIcon id="phone" type="text" :placeholder="$t('public.profile.phone_number_placeholder')" class="block w-full" v-model="form.phone" required disabled autocomplete="phone"
                                :class="form.errors.phone ? 'border border-error-500 dark:border-error-500' : 'border border-gray-400 dark:border-gray-600'"
                            />
                        </InputIconWrapper>

                        <InputError class="mt-2" :message="form.errors.phone" />
                    </div>

                    <div>
                        <Label for="email" value="Email" />

                        <Input
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
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

                    <div class="flex items-center gap-4">
                        <Button :disabled="form.processing">Save</Button>

                        <Transition
                            enter-from-class="opacity-0"
                            leave-to-class="opacity-0"
                            class="transition ease-in-out"
                        >
                            <p
                                v-if="form.recentlySuccessful"
                                class="text-sm text-gray-600 dark:text-gray-400"
                            >
                                Saved.
                            </p>
                        </Transition>
                    </div>
                </div>
                <div class="mt-6 space-y-6">
                    <div>
                        <Label class="text-[14px] dark:text-white mb-2" for="proof_front" value="Proof of Identity (Front)" />
                        <Input
                            id="proof_front"
                            type="file"
                            class="mt-1 block w-full"
                            v-model="form.proof_front"
                        />
                        <InputError :message="form.errors.proof_front" class="mt-2" />
                    </div>
                    <div>
                        <Label class="text-[14px] dark:text-white mb-2" for="proof_back" value="Proof of Identity (Back)" />

                        <Input
                            id="proof_back"
                            type="file"
                            class="mt-1 block w-full"
                            v-model="form.proof_back"
                        />
                            <InputError :message="form.errors.proof_back" class="mt-2" />
                    </div>
                    <div>
                        <Label class="text-[14px] dark:text-white mb-2" for="profile_photo" value="Profile Photo" />

                        <Input
                            id="profile_photo"
                            type="file"
                            class="mt-1 block w-full"
                            v-model="form.profile_photo"
                        />
                        <InputError :message="form.errors.profile_photo" class="mt-2" />
                    </div>
                </div>
            </section>
        </form>
    </section>
</template>
