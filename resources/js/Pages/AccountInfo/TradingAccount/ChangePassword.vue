<script setup>
import {useForm} from "@inertiajs/vue3";
import Label from "@/Components/Label.vue";
import Input from "@/Components/Input.vue";
import InputError from "@/Components/InputError.vue";
import Button from "@/Components/Button.vue";
import BaseListbox from "@/Components/BaseListbox.vue";
import { EyeIcon, EyeOffIcon, CheckIcon, UserAddIcon } from '@heroicons/vue/outline'
import {ref} from "vue";

const props = defineProps({
    account: Object,
})
const showPassword = ref(false);
const showPassword2 = ref(false);
const showPassword3 = ref(false);
const showPassword4 = ref(false);
const emit = defineEmits(['update:accountActionModal']);
const form = useForm({
    meta_login: props.account.meta_login,
    master_password: '',
    confirm_master_password: '',
    investor_password: '',
    confirm_investor_password: '',
})

const closeModal = () => {
    emit('update:accountActionModal', false);
}

const submit = () => {
    form.post(route('account_info.changePassword'), {
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    });
}

const toggleMasterPasswordVisibility = () => {
    showPassword.value = !showPassword.value;
};

const toggleMasterPasswordVisibilityConfirm = () => {
    showPassword2.value = !showPassword2.value;
}

const toggleInvestorPasswordVisibility = () => {
    showPassword3.value = !showPassword3.value;
}

const toggleInvestorPasswordVisibilityConfirm = () => {
    showPassword4.value = !showPassword4.value;
}

</script>

<template>
    <form class="space-y-2">
        <div class="flex flex-col sm:flex-row gap-4 py-2">
            <Label class="text-sm dark:text-white w-full md:w-1/4" for="master_password" :value="$t('public.master_password')" />
            <div class="flex flex-col w-full">
                <div class="relative">
                <Input
                    id="master_password"
                    :type="showPassword ? 'text' : 'password'"
                    :placeholder="$t('public.new_password')"
                    class="block w-full"
                    v-model="form.master_password"
                    :invalid="form.errors.master_password"
                />
                <div
                    class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer"
                    @click="toggleMasterPasswordVisibility"
                >
                    <template v-if="showPassword">
                        <EyeIcon aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                    </template>
                    <template v-else>
                        <EyeOffIcon aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                    </template>
                </div>
                </div>
                <InputError :message="form.errors.master_password" class="mt-2" />
            </div>
        </div>
        <div class="flex flex-col sm:flex-row gap-4 py-2">
            <Label class="text-sm dark:text-white w-full md:w-1/4" for="confirm_master_password" :value="$t('public.confirm_master_password')" />
            <div class="flex flex-col w-full">
                <div class="relative">
                <Input
                    id="confirm_master_password"
                    :type="showPassword2 ? 'text' : 'password'"
                    :placeholder="$t('public.confirm_password')"
                    class="block w-full"
                    v-model="form.confirm_master_password"
                    :invalid="form.errors.confirm_master_password"
                />
                <div
                    class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer"
                    @click="toggleMasterPasswordVisibilityConfirm"
                >
                    <template v-if="showPassword2">
                        <EyeIcon aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                    </template>
                    <template v-else>
                        <EyeOffIcon aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                    </template>
                </div>
                </div>
                <InputError :message="form.errors.confirm_master_password" class="mt-2" />
            </div>
        </div>
        <div class="border-t border-gray-300 py-2"></div> 
        <div class="flex flex-col sm:flex-row gap-4 py-2">
            <Label class="text-sm dark:text-white w-full md:w-1/4" for="investor_password" :value="$t('public.investor_password')" />
            <div class="flex flex-col w-full">
                <div class="relative">
                <Input
                    id="investor_password"
                    :type="showPassword3 ? 'text' : 'password'"
                    :placeholder="$t('public.new_password')"
                    class="block w-full"
                    v-model="form.investor_password"
                    :invalid="form.errors.investor_password"
                />
                <div
                    class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer"
                    @click="toggleInvestorPasswordVisibility"
                >
                    <template v-if="showPassword3">
                        <EyeIcon aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                    </template>
                    <template v-else>
                        <EyeOffIcon aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                    </template>
                </div>
                </div>
                <InputError :message="form.errors.investor_password" class="mt-2" />
            </div>
        </div>
        <div class="flex flex-col sm:flex-row gap-4 py-2">
            <Label class="text-sm dark:text-white w-full md:w-1/4" for="confirm_investor_password" :value="$t('public.confirm_investor_password')" />
            <div class="flex flex-col w-full">
                <div class="relative">
                <Input
                    id="confirm_investor_password"
                    :type="showPassword4 ? 'text' : 'password'"
                    :placeholder="$t('public.confirm_password')"
                    class="block w-full"
                    v-model="form.confirm_investor_password"
                    :invalid="form.errors.confirm_investor_password"
                />
                <div
                    class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer"
                    @click="toggleInvestorPasswordVisibilityConfirm"
                >
                    <template v-if="showPassword4">
                        <EyeIcon aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                    </template>
                    <template v-else>
                        <EyeOffIcon aria-hidden="true" class="w-5 h-5 text-gray-500 dark:text-gray-400" />
                    </template>
                </div>
                </div>
                <InputError :message="form.errors.confirm_investor_password" class="mt-2" />
            </div>
        </div>

        <div class="pt-5 grid grid-cols-2 gap-4 w-full md:w-1/3 md:float-right">
            <Button variant="transparent" type="button" class="justify-center" @click.prevent="closeModal">
                {{$t('public.cancel')}}
            </Button>
            <Button class="justify-center" @click="submit" :disabled="form.processing">{{$t('public.confirm')}}</Button>
        </div>
    </form>
</template>