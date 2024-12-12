export default {
    root: ({ props }) => ({
        class: ['relative w-full', { '[&>input]:pr-10': props.toggleMask }, { 'flex [&>input]:w-full': props.fluid, 'inline-flex': !props.fluid }]
    }),
    overlay: {
        class: [
            // Font
            'text-xs',

            // Spacing
            'p-3',

            // Shape
            'border',
            'shadow rounded-md',

            // Colors
            'bg-surface-0 dark:bg-surface-900',
            'text-surface-700 dark:text-white/80',
            'border-surface-200 dark:border-surface-700'
        ]
    },
    meter: {
        class: [
            // Position and Overflow
            'overflow-hidden',
            'relative',

            // Shape and Size
            'border-0',
            'h-2',
            'rounded-md',

            // Spacing
            'mb-3',

            // Colors
            'bg-surface-100 dark:bg-surface-700'
        ]
    },
    meterLabel: ({ instance }) => ({
        class: [
            // Size
            'h-full',

            // Colors
            {
                'bg-error-500 dark:bg-error-400/80': instance?.meter?.strength == 'weak',
                'bg-warning-500 dark:bg-warning-400/50': instance?.meter?.strength == 'medium',
                'bg-success-500 dark:bg-success-400/50': instance?.meter?.strength == 'strong'
            },

            // Transitions
            'transition-all duration-1000 ease-in-out'
        ]
    }),
    maskIcon: {
        class: ['absolute top-1/2 right-3 -mt-2 z-10', 'text-surface-600 dark:text-white/70']
    },
    unmaskIcon: {
        class: ['absolute top-1/2 right-3 -mt-2 z-10', 'text-surface-600 dark:text-white/70']
    },
    pcinput: {
        root: ({ props, context, parent }) => ({
            class: [
                // Font
                'text-sm',

                // Flex
                { 'flex-1 w-[1%]': parent.instance.$name == 'InputGroup' },

                // Spacing
                'm-0',
                { 'w-full': props.fluid },

                // Size
                {
                    'py-3 px-3.5': props.size == 'large',
                    'py-1.5 px-2': props.size == 'small',
                    'py-2 px-3': props.size == null
                },

                // Shape
                { 'rounded-md': parent.instance.$name !== 'InputGroup' },
                { 'first:rounded-l-md rounded-none last:rounded-r-md': parent.instance.$name == 'InputGroup' },
                { 'border-0 border-y border-l last:border-r': parent.instance.$name == 'InputGroup' },
                { 'first:ml-0 -ml-px': parent.instance.$name == 'InputGroup' && !props.showButtons },

                // Colors
                'text-surface-800 dark:text-white/80',
                'placeholder:text-surface-400 dark:placeholder:text-surface-500',
                { 'bg-surface-0 dark:bg-surface-950': !context.disabled },
                'border',
                { 'border-surface-300 dark:border-surface-700': !props.invalid },

                // Invalid State
                { 'border-error-500 dark:border-error-400': props.invalid },

                // States
                {
                    'hover:border-surface-400 dark:hover:border-surface-600': !context.disabled && !props.invalid,
                    'focus:outline-none focus:ring-0 focus:border-primary-500 dark:focus:border-primary-300': !context.disabled,
                    'bg-surface-200 dark:bg-surface-800 disabled:text-gray-500 dark:disabled:text-gray-500 select-none pointer-events-none cursor-default': context.disabled
                },

                // Filled State *for FloatLabel
                { filled: parent.instance?.$name == 'FloatLabel' && context.filled },

                // Misc
                'appearance-none shadow-input',
                'transition-colors duration-200',
                'w-full',
            ]
        })
    },
    transition: {
        enterFromClass: 'opacity-0 scale-y-[0.8]',
        enterActiveClass: 'transition-[transform,opacity] duration-[120ms] ease-[cubic-bezier(0,0,0.2,1)]',
        leaveActiveClass: 'transition-opacity duration-100 ease-linear',
        leaveToClass: 'opacity-0'
    }
};
