export default {
    root: ({ props }) => ({
        class: [
            // Display and Position
            {
                flex: props.fluid,
                'inline-flex': !props.fluid
            },
            'max-w-full',
            'relative'
        ]
    }),
    pcInputText: ({ props, parent }) => ({
        root: {
            class: [
                // Display
                'flex-auto w-[1%]',

                // Font
                'leading-2 font-normal text-base',

                // Colors
                'text-gray-600 dark:text-gray-200',
                'placeholder:text-gray-400 dark:placeholder:text-gray-500',
                { 'bg-white dark:bg-gray-950': !props.disabled },
                'border',
                { 'border-gray-300 dark:border-gray-600': !props.invalid },

                // Invalid State
                'invalid:focus:ring-red-200',
                'invalid:hover:border-red-500',
                { 'border-red-500 dark:border-red-400': props.invalid },

                // Spacing
                'm-0 py-2.5 px-3',

                // Shape
                'appearance-none',
                { 'rounded-md': !props.showIcon || props.iconDisplay == 'input' },
                { 'rounded-l-md  flex-1 pr-9': props.showIcon && props.iconDisplay !== 'input' },
                { 'rounded-md flex-1 pr-9': props.showIcon && props.iconDisplay === 'input' },

                // Transitions
                'transition-colors',
                'duration-200',

                // States
                {
                    'hover:border-gray-400 dark:hover:border-gray-600': !props.disabled && !props.invalid,
                    'focus:outline-none focus:outline-offset-0 focus:ring-1 focus:ring-primary-500 dark:focus:ring-primary-400 focus:z-10': !props.disabled,
                    'bg-gray-200 dark:bg-gray-700 select-none pointer-events-none cursor-default': props.disabled
                },

                // Filled State *for FloatLabel
                { filled: parent.instance?.$name == 'FloatLabel' && props.modelValue !== null }
            ]
        }
    }),
    dropdownIcon: {
        class: ['absolute top-1/2 -mt-2', 'text-gray-600 dark:text-gray-200', 'right-3']
    },
    dropdown: {
        class: [
            'relative',

            // Alignments
            'items-center inline-flex text-center align-bottom justify-center',

            // Shape
            'rounded-r-lg',

            // Size
            'py-2 px-0',
            'w-10',
            'leading-[normal]',

            // Colors
            'border border-l-0 border-gray-300 dark:border-gray-600',

            // States
            'focus:outline-none focus:outline-offset-0 focus:ring-1',
            'hover:bg-primary-hover hover:border-primary-hover',
            'focus:ring-primary-500 dark:focus:ring-primary-400'
        ]
    },
    inputIconContainer: 'absolute cursor-pointer top-1/2 right-3 -mt-3',
    inputIcon: 'inline-block text-base',
    panel: ({ props }) => ({
        class: [
            // Display & Position
            {
                absolute: !props.inline,
                'inline-block': props.inline
            },

            // Size
            { 'w-auto p-3 ': !props.inline },
            { 'min-w-[80vw] w-auto p-3 ': props.touchUI },
            { 'p-3 min-w-full': props.inline },

            // Shape
            'border rounded-lg',
            {
                'shadow-md': !props.inline
            },

            // Colors
            'bg-white dark:bg-gray-900',
            'border-gray-200 dark:border-gray-700',

            //misc
            { 'overflow-x-auto': props.inline }
        ]
    }),
    header: {
        class: [
            //Font
            'font-medium',

            // Flexbox and Alignment
            'flex items-center justify-between',

            // Spacing
            'p-0 pb-2',
            'm-0',

            // Shape
            'border-b',
            'rounded-t-md',

            // Colors
            'text-gray-700 dark:text-white/80',
            'bg-white dark:bg-gray-900',
            'border-gray-200 dark:border-gray-700'
        ]
    },
    title: {
        class: [
            // Text
            'leading-7',
            'mx-auto my-0'
        ]
    },
    selectMonth: {
        class: [
            // Font
            'text-sm leading-[normal]',
            'font-medium',

            //shape
            'rounded-md',

            // Colors
            'text-gray-700 dark:text-white/80',

            // Transitions
            'transition duration-200',

            // Spacing
            'p-1',
            'm-0 mr-2',

            // States
            'hover:text-primary-500 dark:hover:text-primary-400',
            'focus:outline-none focus:outline-offset-0 focus:ring-1 focus:ring-primary-500 dark:focus:ring-primary-400 focus:z-10',

            // Misc
            'cursor-pointer'
        ]
    },
    selectYear: {
        class: [
            // Font
            'text-sm leading-[normal]',
            'font-medium',

            //shape
            'rounded-md',

            // Colors
            'text-gray-700 dark:text-white/80',

            // Transitions
            'transition duration-200',

            // Spacing
            'p-1',
            'm-0 mr-2',

            // States
            'hover:text-primary-500 dark:hover:text-primary-400',
            'focus:outline-none focus:outline-offset-0 focus:ring-1 focus:ring-primary-500 dark:focus:ring-primary-400 focus:z-10',

            // Misc
            'cursor-pointer'
        ]
    },
    table: {
        class: [
            // Font
            'text-sm leading-[normal]',
            // Size & Shape
            'border-collapse',
            'w-full',

            // Spacing
            'm-0 mt-2'
        ]
    },
    tableHeaderCell: {
        class: [
            // Spacing
            'p-1',
            'font-medium',
            'text-sm'
        ]
    },
    weekHeader: {
        class: ['leading-5', 'text-gray-600 dark:text-white/70', 'opacity-60 cursor-default']
    },
    weekNumber: {
        class: ['text-gray-600 dark:text-white/70', 'opacity-60 cursor-default']
    },
    weekday: {
        class: [
            // Colors
            'text-gray-500 dark:text-white/60',
            'p-1'
        ]
    },
    dayCell: {
        class: [
            // Spacing
            'p-1',
            'text-sm'
        ]
    },
    weekLabelContainer: {
        class: [
            // Flexbox and Alignment
            'flex items-center justify-center',
            'mx-auto',

            // Shape & Size
            'w-8 h-8',
            'rounded-full',
            'border-transparent border',
            'leading-[normal]',

            // Colors
            'opacity-60 cursor-default'
        ]
    },
    dayView: 'w-full',
    day: ({ context }) => ({
        class: [
            // Flexbox and Alignment
            'flex items-center justify-center',
            'mx-auto',

            // Shape & Size
            'w-8 h-8',
            'rounded-full',
            'border-transparent border',
            'leading-[normal]',

            // Colors
            {
                'bg-gray-100 dark:bg-gray-800 text-primary-600 dark:text-white/70': context.date.today && !context.selected && !context.disabled,
                'bg-transparent text-gray-600 dark:text-white/70': !context.selected && !context.disabled && !context.date.today,
                'bg-primary-100 dark:bg-gray-800 text-primary-600 dark:text-white/70': context.selected && !context.disabled
            },

            // States
            'focus:outline-none focus:outline-offset-0 focus:ring-1 focus:ring-primary-500 dark:focus:ring-primary-400 focus:z-10',
            {
                'hover:bg-gray-50 dark:hover:bg-gray-500/10': !context.selected && !context.disabled
            },
            {
                'text-gray-400 dark:text-gray-600 cursor-default': context.disabled,
                'cursor-pointer': !context.disabled
            }
        ]
    }),
    monthView: {
        class: [
            // Spacing
            'mt-2'
        ]
    },
    month: ({ context }) => ({
        class: [
            // Flexbox and Alignment
            'inline-flex items-center justify-center',

            // Size
            'w-1/3',
            'p-1',

            // Shape
            'rounded-md',

            // Colors
            {
                'text-gray-600 dark:text-white/70 bg-transparent': !context.selected && !context.disabled,
                'bg-highlight': context.selected && !context.disabled
            },

            // States
            'focus:outline-none focus:outline-offset-0 focus:ring-1 focus:ring-primary-500 dark:focus:ring-primary-400 focus:z-10',
            {
                'hover:bg-gray-100 dark:hover:bg-[rgba(255,255,255,0.03)]': !context.selected && !context.disabled
            },

            // Misc
            'cursor-pointer'
        ]
    }),
    yearView: {
        class: [
            // Spacing
            'mt-2'
        ]
    },
    year: ({ context }) => ({
        class: [
            // Flexbox and Alignment
            'inline-flex items-center justify-center',

            // Size
            'w-1/2',
            'p-1',

            // Shape
            'rounded-md',

            // Colors
            {
                'text-gray-600 dark:text-white/70 bg-transparent': !context.selected && !context.disabled,
                'bg-highlight': context.selected && !context.disabled
            },

            // States
            'focus:outline-none focus:outline-offset-0 focus:ring-1 focus:ring-primary-500 dark:focus:ring-primary-400 focus:z-10',
            {
                'hover:bg-gray-100 dark:hover:bg-[rgba(255,255,255,0.03)]': !context.selected && !context.disabled
            },

            // Misc
            'cursor-pointer'
        ]
    }),
    timePicker: {
        class: [
            // Flexbox
            'flex',
            'justify-center items-center',

            // Borders
            'border-t-1',
            'border-solid border-gray-200',

            // Spacing
            'pt-2 mt-2'
        ]
    },
    separatorContainer: {
        class: [
            // Flexbox and Alignment
            'flex',
            'items-center',
            'flex-col',

            // Spacing
            'px-2'
        ]
    },
    separator: {
        class: [
            // Text
            'text-xl'
        ]
    },
    hourPicker: {
        class: [
            // Flexbox and Alignment
            'flex',
            'items-center',
            'flex-col',

            // Spacing
            'px-2'
        ]
    },
    minutePicker: {
        class: [
            // Flexbox and Alignment
            'flex',
            'items-center',
            'flex-col',

            // Spacing
            'px-2'
        ]
    },
    secondPicker: {
        class: [
            // Flexbox and Alignment
            'flex',
            'items-center',
            'flex-col',

            // Spacing
            'px-2'
        ]
    },
    ampmPicker: {
        class: [
            // Flexbox and Alignment
            'flex',
            'items-center',
            'flex-col',

            // Spacing
            'px-2'
        ]
    },
    calendarContainer: 'flex',
    calendar: 'flex-auto border-l first:border-l-0 border-gray-200',
    buttonbar: {
        class: [
            // Flexbox
            'flex justify-between items-center',

            // Spacing
            'pt-2',

            // Shape
            'border-t border-gray-200 dark:border-gray-700'
        ]
    },
    transition: {
        enterFromClass: 'opacity-0 scale-y-[0.8]',
        enterActiveClass: 'transition-[transform,opacity] duration-[120ms] ease-[cubic-bezier(0,0,0.2,1)]',
        leaveActiveClass: 'transition-opacity duration-100 ease-linear',
        leaveToClass: 'opacity-0'
    }
};
