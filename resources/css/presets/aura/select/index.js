export default {
    root: ({ props, state, parent }) => ({
        class: [
            // Display and Position
            'inline-flex',
            'relative',

            // Shape
            { 'rounded-md': parent.instance.$name !== 'InputGroup' },
            { 'first:rounded-l-md rounded-none last:rounded-r-md': parent.instance.$name == 'InputGroup' },
            { 'border-0 border-y border-l last:border-r': parent.instance.$name == 'InputGroup' },
            { 'first:ml-0 ml-[-1px]': parent.instance.$name == 'InputGroup' && !props.showButtons },

            // Color and Background
            { 'bg-white dark:bg-gray-950': !props.disabled },

            'border',
            { 'dark:border-gray-700': parent.instance.$name != 'InputGroup' },
            { 'dark:border-gray-600': parent.instance.$name == 'InputGroup' },
            { 'border-gray-300 dark:border-gray-600': !props.invalid },
            'shadow-input',

            // Invalid State
            { 'border-error-500 dark:border-[#F97066]': props.invalid },

            // Transitions
            'transition-all',
            'duration-200',

            // States
            { 'hover:border-gray-400 dark:hover:border-gray-600': !props.invalid },
            { 'outline-none outline-offset-0 ring-1 ring-primary-500 dark:ring-primary-300 z-10': state.focused },

            // Misc
            'cursor-pointer',
            'select-none',
            { 'bg-gray-200 dark:bg-gray-700 select-none pointer-events-none cursor-default': props.disabled }
        ]
    }),
    label: ({ props, parent }) => ({
        class: [
            //Font
            'leading-2',
            'text-base',

            // Display
            'block',
            'flex-auto',

            // Color and Background
            'bg-transparent',
            'border-0',
            { 'text-gray-800 dark:text-white/80': props.modelValue != undefined, 'text-gray-400 dark:text-gray-500': props.modelValue == undefined },
            'placeholder:text-gray-400 dark:placeholder:text-gray-500',

            // Sizing and Spacing
            'w-[1%]',
            'py-2.5 px-3',
            { 'pr-7': props.showClear },

            //Shape
            'rounded-none',

            // Transitions
            'transition',
            'duration-200',

            // States
            'focus:outline-none focus:shadow-none',

            // Filled State *for FloatLabel
            { filled: parent.instance?.$name == 'FloatLabel' && props.modelValue !== null },

            // Misc
            'relative',
            'cursor-pointer',
            'overflow-hidden overflow-ellipsis',
            'whitespace-nowrap',
            'appearance-none'
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
            'rounded-r-md'
        ]
    },
    overlay: {
        class: [
            // Colors
            'bg-white dark:bg-gray-900',
            'text-gray-700 dark:text-white/80',

            // Shape
            'border border-gray-300 dark:border-gray-700',
            'rounded-md',
            'shadow-md'
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
            'text-sm',

            // Spacing
            'm-0 px-3 py-2',
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
    optionCheckIcon: 'relative -ms-1.5 me-1.5 text-gray-700 dark:text-white/80 w-4 h-4',
    optionBlankIcon: 'w-4 h-4',
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
    header: {
        class: [
            // Spacing
            'pt-2 px-2 pb-0',
            'm-0',

            //Shape
            'border-b-0',
            'rounded-tl-md',
            'rounded-tr-md',

            // Color
            'text-gray-700 dark:text-white/80',
            'bg-white dark:bg-gray-900',
            'border-gray-300 dark:border-gray-700'
        ]
    },
    clearIcon: {
        class: [
            // Color
            'text-gray-400 dark:text-gray-500',

            // Position
            'absolute',
            'top-1/2',
            'right-12',

            // Spacing
            '-mt-2'
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
