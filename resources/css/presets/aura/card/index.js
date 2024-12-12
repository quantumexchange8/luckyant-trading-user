export default {
    root: {
        class: [
            //Flex
            'flex flex-col',

            //Shape
            'rounded-lg',
            'shadow-toast',

            //Color
            'bg-white dark:bg-gray-900',
            'text-gray-950 dark:text-gray-0'
        ]
    },
    body: {
        class: [
            //Flex
            'flex flex-col',
            'gap-4',

            'p-5'
        ]
    },
    caption: {
        class: [
            //Flex
            'flex flex-col',
            'gap-2'
        ]
    },
    title: {
        class: 'text-xl font-semibold mb-0'
    },
    subtitle: {
        class: [
            //Font
            'font-normal',

            //Spacing
            'mb-0',

            //Color
            'text-gray-600 dark:text-gray-0/60'
        ]
    },
    content: {
        class: 'p-0'
    },
    footer: {
        class: 'p-0'
    }
};
