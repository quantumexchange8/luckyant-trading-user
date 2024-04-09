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
    'flex px-2 py-1 justify-center text-white rounded-lg hover:-translate-y-1 transition-all duration-300 ease-in-out',
]

const variantClasses = computed(() => {
    if (props.value.toLowerCase() === 'success' || props.value.toLowerCase() === 'active') {
        return 'bg-success-400 dark:bg-success-500'
    } else if (props.value.toLowerCase() === 'rejected' || props.value.toLowerCase() === 'terminated') {
        return 'bg-error-400 dark:bg-error-500'
    } else if (props.value.toLowerCase() === 'pending') {
        return 'bg-blue-400 dark:bg-blue-500'
    }
    return 'bg-primary-600'
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
    variantClasses.value
])
</script>

<template>
    <div :class="[widthClass, classes]">
        {{ $t('public.' + value.toLowerCase()) }}
    </div>
</template>
