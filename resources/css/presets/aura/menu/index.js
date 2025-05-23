export default {
    root: {
        class: [
            // Sizing and Shape
            'min-w-[7.5rem]',
            'rounded-md',

            // Spacing
            'p-1',

            // Colors
            'bg-surface-0 dark:bg-surface-900',
            'text-surface-700 dark:text-white/80',
            'border border-surface-200 dark:border-surface-700'
        ]
    },
    list: {
        class: [
            // Spacings and Shape
            'list-none',
            'm-0',
            'p-0',
            'outline-none',
            'text-sm'
        ]
    },
    item: {
        class: 'relative my-[2px] [&:first-child]:mt-0'
    },
    itemContent: ({ context }) => ({
        class: [
            //Shape
            'rounded-[4px]',

            // Colors
            'text-surface-700 dark:text-white/80',
            {
                'text-surface-500 dark:text-white/70': !context.focused && !context.active,
                'text-surface-500 dark:text-white/70 bg-surface-200': context.focused && !context.active,
                'bg-highlight': (context.focused && context.active) || context.active || (!context.focused && context.active)
            },

            // Transitions
            'transition-shadow',
            'duration-200',

            // States
            {
                'hover:bg-surface-100 dark:hover:bg-[rgba(255,255,255,0.03)]': !context.active,
                'hover:bg-highlight-emphasis': context.active
            },

            // Disabled
            { 'opacity-60 pointer-events-none cursor-default': context.disabled }
        ]
    }),
    itemLink: {
        class: [
            'relative',
            // Flexbox

            'flex',
            'items-center',

            // Spacing
            'py-2',
            'px-3',

            // Color
            'text-surface-700 dark:text-white/80',

            // Misc
            'no-underline',
            'overflow-hidden',
            'cursor-pointer',
            'select-none'
        ]
    },
    itemIcon: {
        class: [
            // Spacing
            'mr-2',

            // Color
            'text-surface-600 dark:text-white/70'
        ]
    },
    itemLabel: {
        class: ['leading-[normal]']
    },
    submenuLabel: {
        class: [
            // Font
            'font-bold',

            // Spacing
            'm-0',
            'py-2 px-3',

            // Shape
            'rounded-tl-none',
            'rounded-tr-none',

            // Colors
            'bg-surface-0 dark:bg-surface-900',
            'text-surface-400 dark:text-surface-600'
        ]
    },
    transition: {
        enterFromClass: 'opacity-0 scale-y-[0.8]',
        enterActiveClass: 'transition-[transform,opacity] duration-[120ms] ease-[cubic-bezier(0,0,0.2,1)]',
        leaveActiveClass: 'transition-opacity duration-100 ease-linear',
        leaveToClass: 'opacity-0'
    }
};
