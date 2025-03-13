<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import ApplicationListView from "@/Pages/Application/ApplicationListView.vue";

defineProps({
    applicationsCount: Number,
})
</script>

<template>
    <AuthenticatedLayout :title="$t('public.application')">
        <template #header>
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold leading-tight">
                    {{ $t('public.application') }}
                </h2>
            </div>
        </template>

        <ApplicationListView
            :applicationsCount="applicationsCount"
        />
    </AuthenticatedLayout>
</template>
