<script setup>
import { Link, useForm } from '@inertiajs/vue3'
import { EyeIcon, EyeOffIcon, CheckIcon, UserAddIcon } from '@heroicons/vue/outline'
import GuestLayout from '@/Layouts/Guest.vue'
import InputIconWrapper from '@/Components/InputIconWrapper.vue'
import Input from '@/Components/Input.vue'
import Label from '@/Components/Label.vue'
import ValidationErrors from '@/Components/ValidationErrors.vue'
import Button from '@/Components/Button.vue'
import {ref, watch, computed, watchEffect} from "vue";
import RegisterCaption from "@/Components/Auth/RegisterCaption.vue";
import ReferralPic from "@/Components/Auth/ReferralPic.vue";
import InputError from "@/Components/InputError.vue";
import BaseListbox from "@/Components/BaseListbox.vue";
import CountryLists from '/public/data/countries.json'
import VueTailwindDatepicker from "vue-tailwind-datepicker";
import Checkbox from "@/Components/Checkbox.vue";
import Terms from "@/Components/Terms.vue";

const props = defineProps({
    countries: Array,
    nationality: Array,
    referral_code: String
})

const formStep = ref(1);
const showPassword = ref(false);
const showPassword2 = ref(false);
const isButtonDisabled = ref(false)
const buttonText = ref('Send OTP')
const formatter = ref({
    date: 'YYYY-MM-DD',
    month: 'MM'
});
const signUpTerm = 'register';

const togglePasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};

const togglePasswordVisibilityConfirm = () => {
    showPassword2.value = !showPassword2.value;
}

const form = useForm({
    form_step: 1,
    name: '',
    chinese_name: '',
    email: '',
    username: '',
    dial_code: '',
    phone: '',
    password: '',
    password_confirmation: '',
    dob_year: '',
    dob_month: '',
    dob_day: '',
    country: '',
    front_identity: null,
    back_identity: null,
    // verification_via: 'email',
    // verification_code: '',
    referral_code: props.referral_code ? props.referral_code : '',
    terms: false,
    market: false,
    responsible: false,
    compensate: false,
    all: false,
    nationality: '',
});

watch(() => [form.terms, form.market, form.responsible, form.compensate], (newValues) => {
    form.all = newValues.every(value => value);
});

watch(() => form.all, (newValue) => {
    form.terms = newValue;
    form.market = newValue;
    form.responsible = newValue;
    form.compensate = newValue;
});

watch(() => form.country, (newCountry) => {
    const foundNationality = props.nationality.find(nationality => nationality.id === newCountry);
    if (foundNationality) {
        form.nationality = foundNationality.value;
    } else {
        form.nationality = null; // Reset if not found
    }
});

const handleFrontIdentity = (event) => {
    form.front_identity = event.target.files[0];
};

const handleBackIdentity = (event) => {
    form.back_identity = event.target.files[0];
};

const submit = () => {
    form.country = form.country;
    form.nationality = form.nationality;

    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    })
}

const nextStep = () => {
    form.post(route('register.first.step'), {
        onSuccess: () => {
            formStep.value++;
            form.form_step++;
        },
    });
}

const prevStep = () => {
    formStep.value--;
    form.form_step--;
}
const passwordRules = [
    { message: 'register_terms_1', regex: /.{6,}/ },
    { message: 'register_terms_2', regex: /[A-Z]+/ },
    { message: 'register_terms_3', regex: /[a-z]+/ },
    { message: 'register_terms_4', regex: /[0-9]+/ },
    { message: 'register_terms_5', regex: /[!@#$%^&*()_+{}\[\]:;<>,.?~\\-]+/ }
];

const passwordValidation = () => {
    let valid = false;
    let messages = [];

    for (let condition of passwordRules) {
        const isConditionValid = condition.regex.test(form.password);

        if (isConditionValid) {
            valid = true;
        }

        messages.push({
            message: condition.message,
            valid: isConditionValid,
        });
    }

    // Check if the new password matches the confirm password
    const isMatch = form.password === form.password_confirmation;

    messages.push({
        message: 'register_terms_6',
        valid: isMatch && form.password !== '',
    });

    // Set valid to false if there's any condition that failed
    valid = valid && isMatch;

    return { valid, messages };
};

// function generateOTP() {
//     const otp = Math.floor(100000 + Math.random() * 900000);
//     return otp.toString();
// }
//
// function startCountdown() {
//     isButtonDisabled.value = true;
//     buttonText.value = '180';
//
//     let count = 180;
//     const countdownInterval = setInterval(() => {
//         count--;
//         buttonText.value = count.toString();
//
//         if (count === 0) {
//             clearInterval(countdownInterval);
//             isButtonDisabled.value = false;
//             buttonText.value = 'Button';
//         }
//
//         // Generate OTP
//         if (count === 179) {
//             const otp = generateOTP();
//             const email = form.email;
//             let url = 'register/send-otp';
//             // if (props.referral_code) {
//             //     url = `register/${props.referral_code}/send-otp`;
//             // }
//             axios
//                 .post(url, { otp, email })
//                 .catch(error => {
//                     // Handle the error if needed
//                     console.log(error);
//                 });
//         }
//     }, 1000);
// }
</script>

<template>
    <GuestLayout :title="$t('public.register')">
        <form>
            <div class="grid gap-6">
                <!-- Progress Bar -->
                <div class="w-full py-6">
                    <div class="flex">
                        <div class="w-1/3">
                            <div class="relative mb-2">
                                <div class="w-10 h-10 mx-auto rounded-full text-lg text-white flex items-center" :class="{'bg-primary-400': formStep === 1, 'bg-gray-400 dark:bg-dark-eval-2': formStep !== 1}">
                                    <span class="text-center text-white w-full font-montserrat font-semibold">1</span>
                                </div>
                            </div>
                        </div>

                        <div class="w-1/3">
                            <div class="relative mb-2">
                                <div class="absolute flex align-center items-center align-middle content-center" style="width: calc(100% - 2.5rem - 1rem); top: 50%; transform: translate(-50%, -50%)">
                                    <div class="w-full rounded items-center align-middle align-center flex-1">
                                        <div class="w-0 pt-1 rounded" :class="{'bg-primary-400': formStep >= 2, 'bg-gray-200': formStep < 2}" style="width: 100%"></div>
                                    </div>
                                </div>

                                <div class="w-10 h-10 mx-auto rounded-full text-lg text-white flex items-center" :class="{'bg-primary-400': formStep === 2, 'bg-gray-400 dark:bg-dark-eval-2': formStep !== 2}">
                                    <span class="text-center text-white w-full font-montserrat font-semibold">2</span>
                                </div>
                            </div>
                        </div>

                        <div class="w-1/3">
                            <div class="relative mb-2">
                                <div class="absolute flex align-center items-center align-middle content-center" style="width: calc(100% - 2.5rem - 1rem); top: 50%; transform: translate(-50%, -50%)">
                                    <div class="w-full rounded items-center align-middle align-center flex-1">
                                        <div class="w-0 pt-1 rounded" :class="{'bg-primary-400': formStep === 3, 'bg-gray-200': formStep !== 3}" style="width: 100%"></div>
                                    </div>
                                </div>

                                <div class="w-10 h-10 mx-auto rounded-full text-lg text-white flex items-center" :class="{'bg-primary-400': formStep === 3, 'bg-gray-400 dark:bg-dark-eval-2': formStep !== 3}">
                                    <span class="text-center text-white w-full font-montserrat font-semibold">3</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Page 1 -->
                <div v-show="formStep === 1" class="space-y-4">
                    <div class="text-center">
                        <RegisterCaption
                            :title="$t('public.register_welcome')"
                            :caption="$t('public.register_welcome_message')"
                        />
                    </div>

                    <div class="space-y-1.5">
                        <Label
                            for="email"
                            :value="$t('public.email')"
                        />
                        <Input
                            id="email"
                            type="email"
                            class="block w-full"
                            :placeholder="$t('public.email')"
                            v-model="form.email"
                            autocomplete="email"
                            autofocus
                            :invalid="form.errors.email"
                        />
                        <InputError :message="form.errors.email" />
                    </div>

                    <div class="space-y-1.5">
                        <Label
                            for="username"
                            :value="$t('public.username')"
                        />
                        <Input
                            id="username"
                            type="username"
                            class="block w-full"
                            :placeholder="$t('public.username')"
                            v-model="form.username"
                            :invalid="form.errors.username"
                        />
                        <InputError :message="form.errors.username" />
                    </div>

                    <div class="space-y-1.5">
                        <Label
                            for="phone"
                            :value="$t('public.mobile_phone')"
                        />
                        <div class="flex gap-3">
                            <BaseListbox
                                class="w-[180px]"
                                :options="CountryLists"
                                v-model="form.dial_code"
                                :placeholder="$t('public.placeholder')"
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
                        <Label for="password" :value="$t('public.password')" />
                        <div class="relative">
                            <Input
                                id="password"
                                :type="showPassword ? 'text' : 'password'"
                                class="block w-full"
                                :placeholder="$t('public.new_password')"
                                :invalid="form.errors.password"
                                v-model="form.password"
                            />
                            <div
                                class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer"
                                @click="togglePasswordVisibility"
                            >
                                <template v-if="showPassword">
                                    <EyeIcon aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                                </template>
                                <template v-else>
                                    <EyeOffIcon aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                                </template>
                            </div>
                        </div>

                        <InputError :message="form.errors.password" class="mt-2" />
                    </div>
                    <div class="space-y-1.5">
                        <Label for="password_confirmation" :value="$t('public.confirm_password')" />
                        <div class="relative">
                            <Input
                                id="password_confirmation"
                                :type="showPassword2 ? 'text' : 'password'"
                                class="block w-full"
                                :placeholder="$t('public.confirm_password')"
                                :invalid="form.errors.password"
                                v-model="form.password_confirmation"
                            />
                            <div
                                class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer"
                                @click="togglePasswordVisibilityConfirm"
                            >
                                <template v-if="showPassword2">
                                    <EyeIcon aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                                </template>
                                <template v-else>
                                    <EyeOffIcon aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                                </template>
                            </div>
                        </div>
                        <InputError :message="form.errors.password_confirmation" class="mt-2" />
                    </div>
                    <div class="flex flex-col items-start gap-3 self-stretch">
                        <div v-for="message in passwordValidation().messages" :key="message.key" class="flex items-center gap-2 self-stretch">
                            <div
                                :class="{
                                        'bg-success-500': message.valid,
                                        'bg-gray-400 dark:bg-dark-eval-3': !message.valid
                                    }"
                                class="flex justify-center items-center w-5 h-5 rounded-full grow-0 shrink-0"
                            >
                                <CheckIcon aria-hidden="true" class="text-white" />
                            </div>
                            <div
                                class="text-sm"
                                :class="{
                                        'text-gray-600 dark:text-gray-300': message.valid,
                                        'text-gray-400 dark:text-gray-500': !message.valid
                                    }"
                            >
                                {{ $t('public.' + message.message) }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Page 2 -->
                <div v-show="formStep === 2" class="space-y-4">
                    <div class="space-y-1.5">
                        <Label
                            for="name"
                            :value="$t('public.full_name')"
                        />
                        <Input
                            id="name"
                            type="text"
                            class="block w-full"
                            :placeholder="$t('public.full_name')"
                            v-model="form.name"
                            autocomplete="name"
                            :invalid="form.errors.name"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="space-y-1.5">
                        <Label
                            for="chinese_name"
                        >
                            {{ $t('public.chinese_name') + " (" + $t('public.optional') + ")" }}
                        </Label>
                        <Input
                            id="chinese_name"
                            type="text"
                            class="block w-full"
                            :placeholder="$t('public.chinese_name')"
                            v-model="form.chinese_name"
                            autocomplete="chinese_name"
                            :invalid="form.errors.chinese_name"
                        />
                        <InputError :message="form.errors.chinese_name" />
                    </div>

                    <div class="space-x-2 flex">
                        <!-- Year Input -->
                        <div class="space-y-1.5">
                            <Label for="dob_year">{{ $t('public.year') }}</Label>
                            <Input
                                id="dob_year"
                                type="number"
                                class="block w-full"
                                :placeholder="$t('public.year')"
                                v-model="form.dob_year"
                                autocomplete="dob_year"
                                :invalid="form.errors.dob_year"
                            />
                            <InputError :message="form.errors.dob_year" />
                        </div>

                        <!-- Month Input -->
                        <div class="space-y-1.5">
                            <Label for="dob_month">{{ $t('public.month') }}</Label>
                            <Input
                                id="dob_month"
                                type="number"
                                class="block w-full"
                                :placeholder="$t('public.month')"
                                v-model="form.dob_month"
                                autocomplete="dob_month"
                                :invalid="form.errors.dob_month"
                            />
                            <InputError :message="form.errors.dob_month" />
                        </div>

                        <!-- Day Input -->
                        <div class="space-y-1.5">
                            <Label for="dob_day">{{ $t('public.day') }}</Label>
                            <Input
                                id="dob_day"
                                type="number"
                                class="block w-full"
                                :placeholder="$t('public.day')"
                                v-model="form.dob_day"
                                autocomplete="dob_day"
                                :invalid="form.errors.dob_day"
                            />
                            <InputError :message="form.errors.dob_day" />
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label
                            for="country"
                            :value="$t('public.country')"
                        />
                        <BaseListbox
                            :options="countries"
                            v-model="form.country"
                            :placeholder="$t('public.placeholder')"
                        />
                        <InputError :message="form.errors.country" />
                    </div>

                    <div class="space-y-1.5">
                        <Label
                            for="nationality"
                            :value="$t('public.nationality')"
                        />
                        <BaseListbox
                            :options="nationality"
                            v-model="form.nationality"
                            :placeholder="$t('public.placeholder')"
                        />
                        <InputError :message="form.errors.nationality" />
                    </div>
                </div>

                <!-- Page 3 -->
                <div v-show="formStep === 3" class="flex flex-col">
<!--                    <div class="grid w-full gap-6 md:grid-cols-2">-->
<!--                        <div class="space-y-1.5">-->
<!--                            <Label-->
<!--                                for="front_identity"-->
<!--                            >-->
<!--                                {{ $t('public.Proof of Identity (FRONT)') }}-->
<!--                            </Label>-->
<!--                            <input-->
<!--                                type="file"-->
<!--                                id="front_identity"-->
<!--                                accept="image/*"-->
<!--                                @change="handleFrontIdentity"-->
<!--                                :class="[-->
<!--                                    'block border dark:bg-dark-eval-2 w-full rounded-lg text-sm text-gray-300 file:mr-4 file:py-2.5 file:px-4 file:border-transparent file:text-sm file:font-semibold file:bg-blue-500 file:text-white hover:file:bg-blue-600',-->
<!--                                    'disabled:bg-gray-50 disabled:cursor-not-allowed dark:disabled:bg-gray-900',-->
<!--                                    {-->
<!--                                        'border-gray-300 dark:border-dark-eval-2 focus:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500' :!form.errors.front_identity,-->
<!--                                        'border-error-300 focus:ring-error-300 hover:border-error-300 focus:border-error-300 dark:border-error-600 dark:focus:ring-error-600 dark:hover:border-error-600 dark:focus:border-error-600' :form.errors.front_identity,-->
<!--                                    }-->
<!--                                ]"-->
<!--                            />-->
<!--                            <InputError :message="form.errors.front_identity"/>-->
<!--                        </div>-->

<!--                        <div class="space-y-1.5">-->
<!--                            <Label-->
<!--                                for="back_identity"-->
<!--                                class="text-white">{{ $t('public.Proof of Identity (BACK)') }}</Label>-->
<!--                            <input-->
<!--                                type="file"-->
<!--                                id="back_identity"-->
<!--                                accept="image/*"-->
<!--                                @change="handleBackIdentity"-->
<!--                                :class="[-->
<!--                                    'block border dark:bg-dark-eval-2 w-full rounded-lg text-sm text-gray-300 file:mr-4 file:py-2.5 file:px-4 file:border-transparent file:text-sm file:font-semibold file:bg-blue-500 file:text-white hover:file:bg-blue-600',-->
<!--                                    'disabled:bg-gray-50 disabled:cursor-not-allowed dark:disabled:bg-gray-900',-->
<!--                                    {-->
<!--                                        'border-gray-300 dark:border-dark-eval-2 focus-within:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500' :!form.errors.back_identity,-->
<!--                                        'border-error-300 focus:ring-error-300 hover:border-error-300 focus:border-error-300 dark:border-error-600 dark:focus:ring-error-600 dark:hover:border-error-600 dark:focus:border-error-600' :form.errors.back_identity,-->
<!--                                    }-->
<!--                                ]"-->
<!--                            />-->
<!--                            <InputError :message="form.errors.back_identity"/>-->
<!--                        </div>-->
<!--                    </div>-->
<!--                    <div class="space-y-1.5 w-full">-->
<!--                        <Label-->
<!--                            for="verification_code"-->
<!--                            :value="$t('public.Verification Code')"-->
<!--                        />-->
<!--                        <div class="flex rounded-md shadow-sm">-->
<!--                            <button-->
<!--                                type="button"-->
<!--                                class="py-2 px-4 inline-flex flex-shrink-0 justify-center items-center gap-2 rounded-l-lg border border-transparent font-semibold bg-blue-500 text-white hover:bg-blue-600 focus:z-10 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all text-sm"-->
<!--                                :disabled="isButtonDisabled"-->
<!--                                @click="startCountdown"-->
<!--                            >-->
<!--                                {{ buttonText }}-->
<!--                            </button>-->
<!--                            <input-->
<!--                                type="number"-->
<!--                                id="verification_code"-->
<!--                                :class="[-->
<!--                                    'py-2.5 w-full rounded-r-lg text-base font-normal shadow-xs border placeholder:text-gray-400 dark:placeholder:text-gray-500 text-gray-900 dark:text-gray-50',-->
<!--                                    'bg-white dark:bg-dark-eval-2',-->
<!--                                    'disabled:bg-gray-50 disabled:cursor-not-allowed dark:disabled:bg-gray-900',-->
<!--                                    {-->
<!--                                        'border-gray-300 dark:border-dark-eval-2 focus:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500' :!form.errors.verification_code,-->
<!--                                        'border-error-300 focus:ring-error-300 hover:border-error-300 focus:border-error-300 dark:border-error-600 dark:focus:ring-error-600 dark:hover:border-error-600 dark:focus:border-error-600' :form.errors.verification_code,-->
<!--                                    }-->
<!--                                ]"-->
<!--                                v-model="form.verification_code">-->
<!--                        </div>-->
<!--                        <InputError :message="form.errors.verification_code"/>-->
<!--                    </div>-->
                    <div class="flex justify-center">
                        <ReferralPic class="w-full" />
                    </div>

                    <div class="space-y-1.5 w-full">
                        <Label
                            for="referral_code"
                            :value="$t('public.referral_code')"
                        />
                        <Input
                            id="referral_code"
                            type="text"
                            class="block w-full"
                            :placeholder="$t('public.referral_code')"
                            v-model="form.referral_code"
                            :invalid="form.errors.referral_code"
                        />
                        <InputError :message="form.errors.referral_code" />
                    </div>
                </div>

                <div v-if="formStep === 3" class="flex flex-col gap-1">
                    <label class="flex items-start gap-2">
                        <div class="flex">
                            <Checkbox name="remember" v-model:checked="form.terms" />
                            <span class="ml-2 text-xs text-gray-600 dark:text-white">
                                {{ $t('public.terms_acknowledgment') }}
                                <!-- <Terms :type=signUpTerm /> -->
                            </span>
                        </div>
                    </label>
                    <label class="flex items-start gap-2">
                        <div class="flex">
                            <Checkbox name="remember" v-model:checked="form.market" />
                            <span class="ml-2 text-xs text-gray-600 dark:text-white">
                                {{ $t('public.market_information_disclosure') }}
                            </span>
                        </div>
                    </label>
                    <label class="flex items-start gap-2">
                        <div class="flex">
                            <Checkbox name="remember" v-model:checked="form.responsible" />
                            <span class="ml-2 text-xs text-gray-600 dark:text-white">
                                {{ $t('public.trade_assessment_acknowledgment') }}
                            </span>
                        </div>
                    </label>
                    <label class="flex items-start gap-2">
                        <div class="flex">
                            <Checkbox name="remember" v-model:checked="form.compensate" />
                            <span class="ml-2 text-xs text-gray-600 dark:text-white">
                                {{ $t('public.compensation_disclosure') }}
                            </span>
                        </div>
                    </label>
                    <label class="flex items-start gap-2">
                        <div class="flex items-center">
                            <Checkbox name="remember" v-model:checked="form.all" />
                            <span class="ml-2 font-semibold text-sm text-gray-600 dark:text-white">
                                {{ $t('public.accept_all') }}
                            </span>
                        </div>
                    </label>

                    <InputError :message="form.errors.all" />
                </div>

                <div class="flex items-center justify-center gap-8 mt-4">
                    <Button
                        type="button"
                        variant="transparent"
                        :disabled="formStep === 1"
                        @click="prevStep"
                        class="px-12"
                    >
                        <span>{{ $t('public.back') }}</span>
                    </Button>

                    <Button type="button" v-if="formStep !== 3" @click="nextStep" class="px-12">
                        <span>{{ $t('public.next') }}</span>
                    </Button>

                    <Button v-else @click="submit" :disabled="form.processing" class="px-12">
                        <span>{{ $t('public.register') }}</span>
                    </Button>

                </div>

                <p class="text-sm text-dark-eval-4 dark:text-gray-400">
                    {{ $t('public.got_account') }}
                    <Link :href="route('login')" class="text-primary-600 hover:underline">
                        {{ $t('public.login') }}
                    </Link>
                </p>
            </div>
        </form>

    </GuestLayout>
</template>
