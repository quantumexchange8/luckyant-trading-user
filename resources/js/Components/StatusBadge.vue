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
    value: String
})

const baseClasses = [
    'flex px-2 py-1 justify-center text-xs text-white rounded-lg hover:-translate-y-1 transition-all duration-300 ease-in-out',
]

const variantClasses = computed(() => {
    const variantMap = {
        success: 'bg-success-400 dark:bg-success-500',
        approved: 'bg-success-400 dark:bg-success-500',
        active: 'bg-success-400 dark:bg-success-500',
        subscribing: 'bg-success-400 dark:bg-success-500',
        rejected: 'bg-error-400 dark:bg-error-500',
        terminated: 'bg-error-400 dark:bg-error-500',
        unsubscribed: 'bg-error-400 dark:bg-error-500',
        expiring: 'bg-warning-500 dark:bg-warning-400',
        pending: 'bg-blue-400 dark:bg-blue-500',
    };

    const variant = props.value.toLowerCase();
    return variantMap[variant] || 'bg-primary-600';
});

const widthClass = computed(() => {
    return {
        20: 'w-20',
        auto: 'w-auto',
        full: 'w-full',
    }[props.width.toString()]
})

const classes = computed(() => [
    ...baseClasses,
    variantClasses.value
])
</script>

<template>
    <div :class="[widthClass, classes]">
        {{ $t('public.' + value.toLowerCase()) }}
    </div>
</template>
