<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import PammMasters from "@/Pages/Pamm/PammListing/PammMasters.vue";

const props = defineProps({
    terms: Object
})
</script>

<template>
    <AuthenticatedLayout :title="$t('public.pamm_listing')">
        <template #header>
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    {{ $t('public.pamm_listing') }}
                </h2>
            </div>
        </template>

        <PammMasters
            :terms="terms"
        />

    </AuthenticatedLayout>
</template>
