<script setup>
import {useForm} from "@inertiajs/vue3";
import Label from "@/Components/Label.vue";
import Input from "@/Components/Input.vue";
import InputError from "@/Components/InputError.vue";
import Button from "@/Components/Button.vue";
import {transactionFormat} from "@/Composables/index.js";
import {BanIcon} from "@heroicons/vue/outline"
import BaseListbox from "@/Components/BaseListbox.vue";

const props = defineProps({
    account: Object,
})
const emit = defineEmits(['update:accountActionModal']);
const { formatAmount } = transactionFormat();
const form = useForm({
    meta_login: props.account.meta_login,
    leverage: props.account.margin_leverage,
})

const closeModal = () => {
    emit('update:accountActionModal', false);
}

const submit = () => {
    form.post(route('account_info.updateLeverage'), {
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    });
}

const leverages = [
    { label: '1:1', value: 1 },
    { label: '1:10', value: 10 },
    { label: '1:20', value: 20 },
    { label: '1:50', value: 50 },
    { label: '1:100', value: 100 },
    { label: '1:200', value: 200 },
    { label: '1:300', value: 300 },
    { label: '1:400', value: 400 },
    { label: '1:500', value: 500 },
]

</script>

<template>
    <form class="space-y-2">
        <div class="flex flex-col sm:flex-row gap-4">
            <Label class="text-sm dark:text-white w-full md:w-1/4" for="leverage" :value="$t('public.leverage')" />
            <div class="flex flex-col w-full">
                <BaseListbox
                        :options="leverages"
                        v-model="form.leverage"
                    />
                <InputError :message="form.errors.leverage" class="mt-2" />
                <span class="text-sm text-gray-600 dark:text-gray-400 mt-2">{{ account.meta_login }}: 1 : {{ account.margin_leverage }}</span>
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
