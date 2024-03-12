<script setup>
import Button from "@/Components/Button.vue";
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import {PlusCircleIcon} from "@heroicons/vue/solid";

const viewMasterRequest = () => {

}
</script>

<template>
    <AuthenticatedLayout :title="$t('public.master_configuration')">
        <template #header>
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    {{ $t('public.master_account_configuration') }}
                </h2>

                <Button
                    type="button"
                    variant="primary"
                    class="items-center gap-2 max-w-xs"
                    v-slot="{ iconSizeClasses }"
                    @click="viewMasterRequest"
                >
                    <PlusCircleIcon aria-hidden="true" :class="iconSizeClasses" />
                    <span>{{ $t('public.view_master_request') }}</span>
                </Button>
            </div>
        </template>

    </AuthenticatedLayout>
</template>
