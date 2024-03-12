<script setup>
import {transactionFormat} from "@/Composables/index.js";
import Button from "@/Components/Button.vue";
import {useForm} from "@inertiajs/vue3";

const props = defineProps({
    account: Object
})
const { formatAmount } = transactionFormat();
const emit = defineEmits(['update:accountActionModal']);

const closeModal = () => {
    emit('update:accountActionModal', false);
}

const form = useForm({
    meta_login: props.account.meta_login,
})

const submit = () => {
    form.post(route('account_info.becomeMaster'), {
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    });
}
</script>

<template>
    <form class="space-y-2">
        <div class="flex p-5 bg-gray-300 dark:bg-gray-600 rounded-lg justify-between items-center">
            <div class="flex flex-col items-start">
                <div class="text-gray-800 dark:text-gray-400 text-sm font-semibold">
                    {{ account.account_type.name }}
                </div>
                <div class="text-gray-600 dark:text-gray-200 text-base">
                    {{ account.meta_login }}
                </div>
            </div>
            <div class="flex flex-col items-start">
                <div class="text-gray-800 dark:text-gray-400 text-sm font-semibold text-right">
                    {{ $t('public.account_equity') }}
                </div>
                <div class="text-gray-600 dark:text-gray-200 text-base w-full text-right">
                    ${{ formatAmount(account.equity) }}
                </div>
            </div>
        </div>
        <div class="text-gray-600 dark:text-gray-400 text-justify text-sm">
            {{ $t('public.become_master_request_message') }}
        </div>

        <div class="pt-5 grid grid-cols-2 gap-4 w-full md:w-1/3 md:float-right">
            <Button variant="transparent" type="button" class="justify-center" @click.prevent="closeModal">
                {{$t('public.cancel')}}
            </Button>
            <Button class="justify-center" @click="submit" :disabled="form.processing">{{$t('public.confirm')}}</Button>
        </div>
    </form>
</template>
