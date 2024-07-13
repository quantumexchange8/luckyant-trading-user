<script setup>

import NoData from "@/Components/NoData.vue";
</script>

<template>
    <div class="p-5 my-8 bg-white overflow-hidden md:overflow-visible rounded-xl shadow-md dark:bg-gray-900">
        <div class="text-lg font-semibold">
            {{ $t('public.pamm_return') }}
        </div>
        <NoData />
    </div>
</template>
