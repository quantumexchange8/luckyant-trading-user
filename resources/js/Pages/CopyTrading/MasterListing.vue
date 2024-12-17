<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import MasterListView from "@/Pages/CopyTrading/MasterListView.vue";

defineProps({
    mastersCount: Number,
    strategyType: String
})
</script>

<template>
    <AuthenticatedLayout :title="$t(`public.${strategyType}_copy_trading`)">
        <template #header>
            <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <h2 class="text-xl font-semibold">
                    {{ $t(`public.${strategyType}_copy_trading`) }}
                </h2>
            </div>
        </template>

        <div class="flex flex-col items-center gap-5">
            <!-- Master List -->
            <MasterListView
                :mastersCount="mastersCount"
                :strategy="strategyType"
            />
        </div>
    </AuthenticatedLayout>
</template>
