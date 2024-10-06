<script setup>
import AuthenticatedLayout from "@/Layouts/Authenticated.vue";
import {MemberDetailIcon} from "@/Components/Icons/outline.jsx";
import Button from "@/Components/Button.vue";
</script>

<template>
    <AuthenticatedLayout :title="$t('public.sidebar.account_info')">
        <template #header>
            <h2 class="text-xl font-semibold leading-tight">
                {{ $t('public.sidebar.account_info') }}
            </h2>
        </template>

    </AuthenticatedLayout>
</template>
