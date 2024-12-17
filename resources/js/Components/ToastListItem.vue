<script setup>
import {onMounted} from "vue";
import {
    IconCircleCheckFilled,
    IconAlertTriangleFilled,
    IconCircleXFilled,
    IconInfoOctagonFilled,
    IconX
} from '@tabler/icons-vue';

const props = defineProps({
    title: String,
    message: String,
    type: String, // Accepts 'success', 'info', 'warning', 'error'
    duration: {
        type: Number,
        default: 5000
    }
});

onMounted(() => {
    setTimeout(() => emit('remove'), props.duration);
});

const emit = defineEmits(['remove']);

// Determine icon based on the type
const iconComponent = {
    success: IconCircleCheckFilled,
    warning: IconAlertTriangleFilled,
    error: IconCircleXFilled,
    info: IconInfoOctagonFilled
}[props.type];

const bgColor = {
    success: 'bg-success-50/90 dark:bg-success-500/20',
    warning: 'bg-warning-50/90 dark:bg-warning-500/20',
    error: 'bg-error-50/90 dark:bg-error-500/20',
    info: 'bg-info-50/90 dark:bg-info-500/20',
}[props.type];

const borderColor = {
    success: 'border-success-200 dark:border-success-500/20',
    warning: 'border-warning-200 dark:border-warning-500/20',
    error: 'border-error-200 dark:border-error-500/20',
    info: 'border-info-500/90 dark:border-info-500/20',
}[props.type];

const textColor = {
    success: 'text-success-500',
    warning: 'text-warning-500',
    error: 'text-error-500',
    info: 'text-info-500',
}[props.type];

</script>
<template>
    <div
        class="mx-3 sm:mx-0 py-3 px-4 flex justify-center self-stretch gap-4 rounded border shadow-toast backdrop-blur-[10px]"
        :class="[
            message ? 'items-start' : 'items-center',
            borderColor,
            bgColor
        ]"
        role="alert"
    >
        <div :class="textColor">
            <component :is="iconComponent" size="20" />
        </div>
        <div
            class="flex flex-col items-start w-full text-sm"
            :class="{
                'gap-1': message
            }"
        >
            <div class="font-semibold" :class="textColor">
                {{ title }}
            </div>
            <div class="text-gray-700 text-xs dark:text-gray-300">
                {{ message }}
            </div>
        </div>
        <div
            class="hover:cursor-pointer select-none"
            :class="textColor"
            @click="emit('remove')"
        >
            <IconX size="16" stroke-width="1.5" />
        </div>
    </div>
</template>
