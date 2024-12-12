export default {
    root: ({ props }) => ({
        class: [
            //Font
            'text-xs font-bold',

            //Alignments
            'inline-flex items-center justify-center',

            //Spacing
            'px-[0.4rem] py-1',

            //Shape
            {
                'rounded-md': !props.rounded,
                'rounded-full': props.rounded
            },

            //Colors
            {
                'bg-primary-500 text-primary-50': props.severity === null || props.severity === 'primary',
                'text-success-500 dark:text-success-300 bg-success-100 dark:bg-success-500/20': props.severity === 'success',
                'text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-500/20': props.severity === 'secondary',
                'text-blue-700 dark:text-blue-300 bg-blue-100 dark:bg-blue-500/20': props.severity === 'info',
                'text-orange-700 dark:text-orange-300 bg-orange-100 dark:bg-orange-500/20': props.severity === 'warn',
                'text-red-500 dark:text-red-300 bg-red-100 dark:bg-red-500/20': props.severity === 'danger',
                'text-white dark:text-gray-900 bg-gray-900 dark:bg-white': props.severity === 'contrast'
            }
        ]
    }),
    value: {
        class: 'leading-normal'
    },
    icon: {
        class: 'mr-1 text-sm'
    }
};
