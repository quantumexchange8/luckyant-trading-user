<script setup>
import {computed} from "vue";

const props = defineProps({
    variant: {
        type: String,
        default: 'success',
        validator(value) {
            return ['primary', 'success', 'danger', 'processing', 'warning'].includes(value)
        },
    },
    width: {
        default: '20',
    },
})

const { variant } = props

const baseClasses = [
    'flex px-2 py-1 justify-center font-semibold rounded-lg hover:-translate-y-1 transition-all duration-300 ease-in-out',
]

const variantClasses = (variant) => ({
    'text-white bg-primary-600': variant === 'primary',
    'bg-success-100 text-success-600 dark:bg-success-300 dark:text-success-900': variant === 'success',
    'text-white bg-error-400 dark:bg-error-500': variant === 'danger',
    'text-white bg-blue-400 dark:bg-blue-500': variant === 'processing',
    'text-white bg-warning-400 dark:bg-warning-500': variant === 'warning',
})

const widthClass = computed(() => {
    return {
        20: 'w-20',
        auto: 'w-auto',
        full: 'w-full',
    }[props.width.toString()]
})

const classes = computed(() => [
    ...baseClasses,
    variantClasses(variant)
])
</script>

<template>
    <div :class="[widthClass, classes]">
        <slot></slot>
    </div>
</template>
