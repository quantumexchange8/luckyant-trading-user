export default {
    root: ({ props, context }) => ({
        class: [
            'relative shrink-0',

            // Shape
            'border-b',
            'rounded-t-md',

            // Spacing
            'py-2 px-[1.125rem]',
            '-mb-px',

            // Font size
            'text-sm',

            // Colors and Conditions
            'outline-transparent',
            {
                'border-gray-200 dark:border-gray-700': !context.active,
                'text-gray-700 dark:text-white/80': !context.active,

                'text-primary-400 font-semibold border-b-4 border-primary-400': context.active,

                'opacity-60 cursor-default user-select-none select-none pointer-events-none': props?.disabled
            },

            // States
            'focus:outline-none focus:outline-offset-0 focus-visible:ring-0 ring-inset focus-visible:ring-primary-400 dark:focus-visible:ring-primary-300',
            'hover:text-primary-700 dark:hover:text-primary-700',

            // Transitions
            'transition-all duration-200',

            // Misc
            'cursor-pointer select-none whitespace-nowrap',
            'user-select-none'
        ]
    })
};
