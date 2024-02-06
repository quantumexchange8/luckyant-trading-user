<script setup>
import {computed} from "vue";

const props = defineProps({
    variant: {
        type: String,
        default: 'success',
        validator(value) {
            return ['success', 'danger', 'processing', 'warning'].includes(value)
        },
    },
})

const { variant } = props

const baseClasses = [
    'flex w-20 px-2 py-1 justify-center text-white mx-auto rounded-lg hover:-translate-y-1 transition-all duration-300 ease-in-out',
]

const variantClasses = (variant) => ({
    'bg-success-400 dark:bg-success-500': variant === 'success',
    'bg-error-400 dark:bg-error-500': variant === 'danger',
    'bg-blue-400 dark:bg-blue-500': variant === 'processing',
    'bg-warning-400 dark:bg-warning-500': variant === 'warning',
})

const classes = computed(() => [
    ...baseClasses,
    variantClasses(variant)
])
</script>

<template>
    <div :class="classes">
        <slot></slot>
    </div>
</template>
