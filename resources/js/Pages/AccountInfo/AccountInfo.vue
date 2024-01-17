<script setup>
import AuthenticatedLayout from '@/Layouts/Authenticated.vue'
import Button from "@/Components/Button.vue";
import {PlusCircleIcon} from "@heroicons/vue/solid";
import {useForm} from "@inertiajs/vue3";
import TradingAccountTable from "@/Pages/AccountInfo/TradingAccount/TradingAccount.vue";

const form = useForm({
    id: ''
})

const submit = () => {
    form.post(route('account_info.add_trading_account'))
}
</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar.Account Info')">
        <template #header>
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    {{ $t('public.sidebar.Account Info') }}
                </h2>

                <Button
                    type="button"
                    variant="primary"
                    class="items-center gap-2 max-w-xs"
                    v-slot="{ iconSizeClasses }"
                    @click="submit"
                >
                    <PlusCircleIcon aria-hidden="true" :class="iconSizeClasses" />
                    <span>{{ $t('public.Add Trading Account') }}</span>
                </Button>
            </div>
        </template>

        <TradingAccountTable />
    </AuthenticatedLayout>
</template>
