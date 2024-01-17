<script setup>
defineProps({
    title: String,
    caption: String,
})
</script>

<template>
    <h2 class="text-gray-800 dark:text-gray-50 font-semibold text-lg" style="font-family: 'Montserrat SemiBold', sans-serif">{{ title }}</h2>
    <p class="text-sm text-gray-600 dark:text-gray-400">{{ caption }}</p>
</template>
