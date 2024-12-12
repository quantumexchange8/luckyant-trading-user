export default {
    root: ({ props, state }) => ({
        class: [
            // Font
            'leading-none',

            // Display and Position
            'inline-flex',
            'relative',

            // Shape
            'rounded-lg',

            // Color and Background
            { 'bg-white dark:bg-gray-950': !props.disabled },
            'border',
            { 'border-gray-300 dark:border-gray-700': !props.invalid },

            // Invalid State
            'invalid:focus:ring-red-200',
            'invalid:hover:border-red-500',
            { 'border-red-500 dark:border-red-400': props.invalid },

            // Transitions
            'transition-all',
            'duration-200',

            // States
            { 'hover:border-gray-400 dark:hover:border-gray-600': !props.invalid },
            { 'outline-none outline-offset-0 z-10 ring-1 ring-primary-500 dark:ring-primary-400': state.focused },

            // Misc
            'cursor-pointer',
            'select-none',
            { 'bg-gray-200 dark:bg-gray-700 select-none pointer-events-none cursor-default': props.disabled }
        ]
    }),
    labelContainer: 'overflow-hidden flex flex-auto cursor-pointer',
    label: ({ props }) => ({
        class: [
            'text-base',

            // Spacing
            {
                'py-2.5 px-4': props.display === 'comma' || (props.display === 'chip' && !props?.modelValue?.length),
                'py-1 px-1': props.display === 'chip' && props?.modelValue?.length > 0
            },

            // Color
            { 'text-gray-800 dark:text-white/80': props.modelValue?.length, 'text-gray-400 dark:text-gray-500': !props.modelValue?.length },
            'placeholder:text-gray-400 dark:placeholder:text-gray-500',

            // Transitions
            'transition duration-200',

            // Misc
            'overflow-hidden whitespace-nowrap cursor-pointer overflow-ellipsis'
        ]
    }),
    dropdown: {
        class: [
            // Flexbox
            'flex items-center justify-center',
            'shrink-0',

            // Color and Background
            'bg-transparent',
            'text-gray-500',

            // Size
            'w-12',

            // Shape
            'rounded-r-lg'
        ]
    },
    overlay: {
        class: [
            // Colors
            'bg-white dark:bg-gray-900',
            'text-gray-700 dark:text-white/80',

            // Shape
            'border border-gray-300 dark:border-gray-700',
            'rounded-lg',
            'shadow-md',
            'mt-[2px]'
        ]
    },
    header: {
        class: [
            //Flex
            'flex items-center justify-between',

            // Spacing
            'pt-2 px-4 pb-0 gap-2',
            'm-0',

            //Shape
            'border-b-0',
            'rounded-tl-lg',
            'rounded-tr-lg',

            // Color
            'text-gray-700 dark:text-white/80',
            'bg-white dark:bg-gray-900',
            'border-gray-300 dark:border-gray-700',

            '[&_[data-pc-name=pcfiltercontainer]]:!flex-auto',
            '[&_[data-pc-name=pcfilter]]:w-full'
        ]
    },
    listContainer: {
        class: [
            // Sizing
            'max-h-[200px]',

            // Misc
            'overflow-auto'
        ]
    },
    list: {
        class: 'p-1 list-none m-0'
    },
    option: ({ context }) => ({
        class: [
            'relative',
            'flex items-center',

            // Font
            'leading-none',
            'text-sm',

            // Spacing
            'm-0 px-3 py-2 gap-2',
            'first:mt-0 mt-[2px]',

            // Shape
            'border-0 rounded',

            // Colors
            {
                'text-gray-700 dark:text-white/80': !context.focused && !context.selected,
                'bg-gray-200 dark:bg-gray-600/60': context.focused && !context.selected,
                'text-gray-700 dark:text-white/80': context.focused && !context.selected,
                'bg-primary-100 dark:bg-gray-600/40': context.selected
            },

            //States
            { 'hover:bg-gray-100 dark:hover:bg-[rgba(255,255,255,0.03)]': !context.focused && !context.selected },
            { 'hover:bg-highlight-emphasis': context.selected },
            { 'hover:text-gray-700 hover:bg-gray-100 dark:hover:text-white dark:hover:bg-[rgba(255,255,255,0.03)]': context.focused && !context.selected },

            // Transition
            'transition-shadow duration-200',

            // Misc
            'cursor-pointer overflow-hidden whitespace-nowrap'
        ]
    }),
    optionGroup: {
        class: [
            'font-semibold',

            // Spacing
            'm-0 py-2 px-3',

            // Colors
            'text-gray-400 dark:text-gray-500',

            // Misc
            'cursor-auto'
        ]
    },
    emptyMessage: {
        class: [
            // Font
            'leading-none',
            'text-sm',

            // Spacing
            'py-2 px-3',

            // Color
            'text-gray-800 dark:text-white/80',
            'bg-transparent'
        ]
    },
    loadingIcon: {
        class: 'text-gray-400 dark:text-gray-500 animate-spin'
    },
    transition: {
        enterFromClass: 'opacity-0 scale-y-[0.8]',
        enterActiveClass: 'transition-[transform,opacity] duration-[120ms] ease-[cubic-bezier(0,0,0.2,1)]',
        leaveActiveClass: 'transition-opacity duration-100 ease-linear',
        leaveToClass: 'opacity-0'
    }
};
