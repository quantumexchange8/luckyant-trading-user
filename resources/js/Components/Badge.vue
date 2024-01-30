<script setup>
import { computed } from 'vue';

const props = defineProps({
    status: String,
})

const badgeClasses = computed(() => {
    const baseClasses = 'text-xs font-medium px-3 py-1 rounded-full';
    const status = props.status;

    if (status === 'warning') {
        return `${baseClasses} bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-white`;
    } else if (status === 'success') {
        return `${baseClasses} bg-success-100 text-green-700 dark:bg-success-200`;
    } else if (status === 'primary') {
        return `${baseClasses} bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-white`;
    } else if (status === 'danger') {
        return `${baseClasses} bg-error-100 text-red-800 dark:bg-error-500 dark:text-white`;
    } else {
        return baseClasses;
    }
});
</script>

<template>
    <span :class="badgeClasses"><slot></slot></span>
</template>
