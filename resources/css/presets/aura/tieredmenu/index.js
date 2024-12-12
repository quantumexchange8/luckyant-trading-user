export default {
    root: {
        class: [
            // Shape
            'rounded-md',

            // Size
            'min-w-[12rem]',
            'py-1',

            // Colors
            'bg-white dark:bg-gray-900',
            'border border-gray-200 dark:border-gray-700'
        ]
    },
    rootList: {
        class: [
            // Spacings and Shape
            'list-none',
            'flex flex-col',
            'm-0 p-0',
            'outline-none'
        ]
    },
    item: {
        class: 'relative my-[2px] [&:first-child]:mt-0'
    },
    itemContent: ({ context }) => ({
        class: [
            // Colors
            'text-gray-700 dark:text-white/80',
            {
                'text-gray-500 dark:text-white/70': !context.focused && !context.active,
                'text-gray-500 dark:text-white/70 bg-gray-200': context.focused && !context.active,
                'bg-highlight': (context.focused && context.active) || context.active || (!context.focused && context.active)
            },

            // Transitions
            'transition-shadow',
            'duration-200',

            // States
            {
                'hover:bg-gray-100 dark:hover:bg-[rgba(255,255,255,0.03)]': !context.active,
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
            'text-gray-700 dark:text-white/80',

            // Font
            'text-sm',

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
            'text-gray-600 dark:text-white/70'
        ]
    },
    itemLabel: {
        class: ['leading-none']
    },
    submenuIcon: {
        class: [
            // Position
            'ml-auto'
        ]
    },
    submenu: {
        class: [
            // Spacing
            'flex flex-col',
            'm-0',
            'outline-none',
            'rounded-md',

            // Size
            'w-full',
            'py-1',

            // Shape
            'shadow-dropdown',
            'border border-gray-200 dark:border-gray-700',

            // Position
            'static sm:absolute',
            'z-10',

            // Color
            'bg-white dark:bg-gray-900'
        ]
    },
    separator: {
        class: 'border-t border-gray-200 dark:border-gray-600 my-[2px]'
    },
    transition: {
        enterFromClass: 'opacity-0',
        enterActiveClass: 'transition-opacity duration-250'
    }
};
