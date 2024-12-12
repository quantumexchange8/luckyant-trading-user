export default {
    root: {
        class: [
            // Flexbox
            'inline-flex items-center',

            // Spacing
            'px-2 py-1 gap-2 mr-1',

            // Shape
            'rounded-lg',

            // Colors
            'text-surface-700 dark:text-white',
            'bg-surface-100 dark:bg-surface-700'
        ]
    },
    label: {
        class: 'text-xs m-0'
    },
    icon: {
        class: 'mr-2'
    },
    image: {
        class: ['w-8 h-8 -ml-2 mr-2', 'rounded-full']
    },
    removeIcon: {
        class: [
            'inline-block',
            // Shape
            'rounded-md leading-6',

            // Size
            'w-4 h-4',

            // Transition
            'transition duration-200 ease-in-out',

            // Misc
            'cursor-pointer'
        ]
    }
};
