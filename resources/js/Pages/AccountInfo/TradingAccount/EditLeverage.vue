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
    leverageSel: Array,
})
const emit = defineEmits(['update:accountActionModal']);
const { formatAmount } = transactionFormat();
const form = useForm({
    meta_login: props.account.meta_login,
    leverage: props.account.margin_leverage,
    type: props.account.account_type,
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

</script>

<template>
    <form class="space-y-2">
        <div class="flex flex-col sm:flex-row gap-4">
            <Label class="text-sm dark:text-white w-full md:w-1/4" for="leverage" :value="$t('public.leverage')" />
            <div class="flex flex-col w-full">
                <BaseListbox
                        :options="leverageSel"
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
