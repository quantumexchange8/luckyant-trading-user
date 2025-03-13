<script setup>
import Button from "primevue/button";
import Tag from "primevue/tag";
import {IconHandClick} from "@tabler/icons-vue";

defineProps({
    application: Object
})
</script>

<template>
    <div
        v-if="application.user_applications && application.user_applications.some(app => app.status === 'pending')"
        class="flex items-center justify-between gap-3 w-full"
    >
        <Button
            severity="secondary"
            size="small"
            :label="$t('public.view_details')"
        />

        <Tag
            severity="warn"
            :value="$t('public.pending')"
        />
    </div>

    <div
        v-else-if="application.user_applications && application.user_applications.some(app => app.status === 'approved')"
        class="flex items-center justify-between gap-3 w-full"
    >
        <Button
            severity="secondary"
            size="small"
            :label="$t('public.view_details')"
        />
        <Tag
            severity="success"
            :value="$t('public.approved')"
        />
    </div>

    <div
        v-else-if="application.user_applications && application.user_applications.some(app => app.status === 'rejected')"
        class="flex items-center justify-between gap-3 w-full"
    >
        <Button
            severity="secondary"
            size="small"
            :label="$t('public.view_details')"
        />
        <Tag
            severity="danger"
            :value="$t('public.rejected')"
        />
    </div>

    <Button
        v-else
        as="a"
        :href="route('application.application_form', { id: application.id })"
        type="button"
        size="small"
        class="flex gap-2 w-full"
    >
        <IconHandClick size="20" stroke-width="1.5" />
        {{ $t('public.apply_form') }}
    </Button>
</template>
