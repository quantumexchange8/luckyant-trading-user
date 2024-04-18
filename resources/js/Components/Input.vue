<script setup>
import { onMounted, ref } from 'vue'

defineProps({
    modelValue: [String, Number],
    withIcon: {
        type: Boolean,
        default: false,
    },
    invalid: [String, Array]
})

defineEmits(['update:modelValue'])

const input = ref(null)

const focus = () => input.value?.focus()

defineExpose({
    input,
    focus
})

onMounted(() => {
    if (input.value.hasAttribute('autofocus')) {
        input.value.focus()
    }
})
</script>

<template>
    <input
        :class="[
            'py-2.5 rounded-lg text-base font-normal shadow-xs border placeholder:text-gray-400 dark:placeholder:text-gray-500 text-gray-900 dark:text-gray-50',
            'bg-white dark:bg-gray-800',
            'disabled:bg-gray-200 disabled:cursor-not-allowed dark:disabled:bg-gray-800 disabled:text-gray-400 dark:disabled:text-gray-500',
            {
                'px-4': !withIcon,
                'pl-11 pr-4': withIcon,
            },
            {
                'border-gray-300 dark:border-gray-800 focus:ring-primary-400 hover:border-primary-400 focus:border-primary-400 dark:focus:ring-primary-500 dark:hover:border-primary-500 dark:focus:border-primary-500' :!invalid,
                'border-error-300 focus:ring-error-300 hover:border-error-300 focus:border-error-300 dark:border-error-600 dark:focus:ring-error-600 dark:hover:border-error-600 dark:focus:border-error-600' :invalid,
            }
        ]"
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        ref="input"
    />
</template>
