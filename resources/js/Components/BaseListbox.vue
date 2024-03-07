<script setup>
import {computed, ref} from 'vue'
import {
    Listbox,
    ListboxLabel,
    ListboxButton,
    ListboxOptions,
    ListboxOption,
} from '@headlessui/vue'
import {CheckIcon, ChevronDownIcon} from '@heroicons/vue/solid'
import BankImg from "/public/assets/bank.jpg"

const props = defineProps({
    options: Array,
    modelValue: [String, Number, Array, Object],
    placeholder: {
        type: String,
        default: 'Please Select'
    },
    optionMessage: {
        type: String,
        default: 'Please Select'
    },
    multiple: Boolean,
    error: {
        type: Boolean,
        default: false
    },
    withImg: {
        type: Boolean,
        default: false,
    },
    isPhoneCode: {
        type: Boolean,
        default: false,
    },
})

const emit = defineEmits(['update:modelValue'])

const labelWithImg = computed(() => {
    return props.options
        .filter(option => {
            if (Array.isArray(props.modelValue)) {
                return props.modelValue.includes(option.value);
            }
            return props.modelValue === option.value;
        })
        .map(option => ({ label: option.label, value: option.value, imgUrl: option.imgUrl }));
});

const label = computed(() => {
    return props.options.filter(option => {
        if (Array.isArray(props.modelValue)) {
            return props.modelValue.includes(option.value);
        }

        return props.modelValue === option.value;
    }).map(option => option.label).join(', ')
})

const shouldShowLabelWithImg = computed(() => props.withImg && labelWithImg.value.length > 0);
const labelWithImgString = computed(() => labelWithImg.value.map(item => item.label).join(', '));
const labelWithValue = computed(() => labelWithImg.value.map(item => item.value).join(', '));
</script>

<template>
    <Listbox
        :multiple="props.multiple"
        :model-value="props.modelValue"
        @update:modelValue="value => emit('update:modelValue', value)"
    >
        <div
            class="relative rounded-lg border focus:ring-1 focus:outline-none"
            :class="[
                    {
                          'border-error-300 focus-within:ring-error-300 hover:border-error-300 focus-within:border-error-300 focus-within:shadow-error-light dark:border-error-600 dark:focus-within:ring-error-600 dark:hover:border-error-600 dark:focus-within:border-error-600 dark:focus-within:shadow-error-dark': error,
                          'border-gray-300 dark:border-dark-eval-2 focus:ring-primary-400 hover:border-primary-400 focus-within:border-primary-400 focus-within:shadow-primary-light dark:focus-within:ring-primary-500 dark:hover:border-primary-500 dark:focus-within:border-primary-500 dark:focus-within:shadow-primary-dark': !error,
                    }
                ]"
        >
            <ListboxButton
                class="relative w-full cursor-default rounded-lg border shadow-xs bg-white dark:bg-gray-700 py-2.5 pl-4 pr-10 text-left focus:ring-1 focus:outline-none disabled:bg-gray-50 disabled:cursor-not-allowed dark:disabled:bg-gray-900"
                :class="[
                    {
                        'border-transparent focus-within:ring-error-300 focus:border-error-300 focus:shadow-error-light dark:border-transparent dark:focus:ring-error-600 dark:focus:border-error-600 dark:focus:shadow-error-dark': error,
                        'border-transparent dark:border-transparent focus-within:ring-primary-400 focus-within:border-primary-400 focus-within:shadow-primary-light dark:focus-within:ring-primary-500 dark:focus-within:border-primary-500 dark:focus-within:shadow-primary-dark': !error
                    }
                ]"
            >
                <div class="flex gap-2 text-gray-900 dark:text-gray-50 items-center">
                    <template v-if="shouldShowLabelWithImg && withImg && isPhoneCode">
                        <img v-for="item in labelWithImg" :key="item.label" :src="item.imgUrl" width="24" alt="img">
                        <span class="block truncate">{{ labelWithValue }}</span>
                    </template>
                    <template v-else-if="shouldShowLabelWithImg && withImg && !isPhoneCode">
                        <img v-for="item in labelWithImg" class="rounded-full" :key="item.label" :src="item.imgUrl ? item.imgUrl : BankImg" width="24" alt="img">
                        <span class="block truncate">{{ labelWithImgString }}</span>
                    </template>
                    <template v-else-if="!withImg && label">
                        <span class="block truncate">{{ label }}</span>
                    </template>
                    <template v-else>
                        <span class="block truncate text-gray-500 dark:text-gray-400">{{ props.placeholder }}</span>
                    </template>
                </div>
                <span
                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2"
                >
                    <ChevronDownIcon
                        class="h-5 w-5 text-gray-400"
                        aria-hidden="true"
                    />
                </span>
            </ListboxButton>
            <transition
                leave-active-class="transition duration-100 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <ListboxOptions
                    class="z-10 absolute border border-gray-300 dark:border-dark-eval-2 mt-2 max-h-52 w-full overflow-auto rounded-lg bg-white dark:bg-gray-700 py-1 text-base shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm"
                    :style="{ width: isPhoneCode ? '360px' : '' }"
                >
                    <ListboxOption
                        v-slot="{ active, selected }"
                        v-for="option in props.options"
                        :key="option.label"
                        :value="option.value"
                        as="template"
                    >
                        <li
                            :class="[
                                active ? 'bg-gray-100 dark:bg-dark-eval-3 text-gray-900 dark:text-gray-50' : 'text-gray-900 dark:text-gray-50',
                                selected ? 'bg-gray-100 dark:bg-dark-eval-3' : '',
                                'relative cursor-default select-none py-2.5 px-4',
                            ]"
                        >
                            <template v-if="withImg">
                                 <span
                                     :class="[
                                        'block truncate',
                                      ]"
                                 >
                                     <span class="flex items-center gap-2"><img class="rounded-full" :key="option.label" :src="option.imgUrl ? option.imgUrl : BankImg" width="24" alt="img">{{ option.label }} <span v-if="isPhoneCode">({{ option.value }})</span></span></span>
                            </template>
                            <template v-else>
                                 <span
                                     :class="[
                                selected ? 'font-medium' : 'font-normal',
                                'block truncate',
                              ]"
                                 >{{ option.label }}</span>
                            </template>
                            <span
                                v-if="selected"
                                class="absolute inset-y-0 right-0 flex items-center pr-3 dark:text-white"
                            >
                                <CheckIcon class="h-5 w-5 text-gray-600 dark:text-gray-400" aria-hidden="true"/>
                            </span>
                        </li>
                    </ListboxOption>
                    <li v-if="props.options.length === 0" class="text-gray-900 dark:text-gray-50 py-2.5 px-4">
                        {{ optionMessage }}
                    </li>
                </ListboxOptions>
            </transition>
        </div>
    </Listbox>
</template>
