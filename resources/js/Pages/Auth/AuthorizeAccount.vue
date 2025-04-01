<script setup>
import GuestLayout from "@/Layouts/Guest.vue";
import {useForm} from "@inertiajs/vue3";
import Label from "@/Components/Label.vue";
import InputText from "primevue/inputtext";
import InputError from "@/Components/InputError.vue";
import Password from "primevue/password";
import Button from "primevue/button";
import Card from "primevue/card";

const props = defineProps({
    username: String,
});

const form = useForm({
    username: props.username,
    password: ''
});

const submitForm = () => {
    form.post(route('authorize_account'));
}
</script>

<template>
    <GuestLayout :title="$t('public.authorize_account')">
        <div class="flex flex-col items-center p-5 bg-gray-200 dark:bg-gray-800 my-4">
            <span class="font-semibold dark:text-white">{{ $t('public.authorize_account_caption') }}</span>
            <span class="text-sm text-gray-700 dark:text-gray-300">{{ $t('public.authorize_account_message') }}</span>
        </div>
        <form class="flex flex-col gap-6 items-center self-stretch">
            <div class="flex flex-col gap-1 items-start self-stretch">
                <Label
                    for="username"
                    :value="$t('public.username')"
                />
                <InputText
                    id="username"
                    type="text"
                    class="block w-full"
                    v-model="form.username"
                    autofocus
                    :placeholder="$t('public.username')"
                    :invalid="!!form.errors.username"
                    autocomplete="username"
                />
                <InputError :message="form.errors.username" />
            </div>
            <div class="flex flex-col gap-1 items-start self-stretch">
                <Label for="password" :value="$t('public.password')" />
                <Password
                    v-model="form.password"
                    toggleMask
                    placeholder="••••••••"
                    :invalid="!!form.errors.password"
                    :feedback="false"
                />
                <InputError :message="form.errors.password" />
            </div>

            <Button
                class="w-full"
                type="submit"
                :label="$t('public.proceed')"
                @click.prevent="submitForm"
            />
        </form>
    </GuestLayout>
</template>
