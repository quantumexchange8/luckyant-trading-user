const defaultTheme = require('tailwindcss/defaultTheme')
const colors = require("tailwindcss/colors")

/** @type {import('tailwindcss').Config} */
module.exports = {
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.{vue,js,jsx}',
        './node_modules/vue-tailwind-datepicker/**/*.js',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },

            colors: {
                dark: {
                    'eval-0': '#151823',
                    'eval-1': '#222738',
                    'eval-2': '#2A2F42',
                    'eval-3': '#2C3142',
                },
                primary: {
                    25: 'var(--primary-25)',
                    50: 'var(--primary-50)',
                    100: 'var(--primary-100)',
                    200: 'var(--primary-200)',
                    300: 'var(--primary-300)',
                    400: 'var(--primary-400)',
                    500: 'var(--primary-500)',
                    600: 'var(--primary-600)',
                    700: 'var(--primary-700)',
                    800: 'var(--primary-800)',
                    900: 'var(--primary-900)',
                    950: 'var(--primary-950)',
                },
                gray: {
                    25: '#FCFCFD',
                    50: '#F9FAFB',
                    100: '#F1F5F9',
                    200: '#E2E8F0',
                    300: '#CBD5E1',
                    400: '#94A3B8',
                    500: '#64748B',
                    600: '#475569',
                    700: '#334155',
                    800: '#1E293B',
                    900: '#0F172A',
                    950: '#020617',
                },
                blue: {
                    25: '#FFFBFA',
                    50: '#e6f3ff',
                    100: '#d1e8ff',
                    200: '#acd1ff',
                    300: '#7ab1ff',
                    400: '#467eff',
                    500: '#1d4dff',
                    600: '#0025ff',
                    700: '#0029ff',
                    800: '#0024dc',
                    900: '#051da6',
                },
                pink: {
                    25: '#FFFBFA',
                    50: '#FEF3F2',
                    100: '#FFDCD5',
                    200: '#FFB2AB',
                    300: '#FF8181',
                    400: '#FF6170',
                    500: '#FF2D55',
                    600: '#DB2056',
                    700: '#B71653',
                    800: '#930E4D',
                    900: '#7A0849',
                },
                success: {
                    25: '#F6FEF9',
                    50: '#ECFDF3',
                    100: '#D1FADF',
                    200: '#A6F4C5',
                    300: '#6CE9A6',
                    400: '#32D583',
                    500: '#12B76A',
                    600: '#039855',
                    700: '#027A48',
                    800: '#05603A',
                    900: '#054F31',
                },
                warning: {
                    25: '#FFFCF5',
                    50: '#FFFAEB',
                    100: '#FEF0C7',
                    200: '#FEDF89',
                    300: '#FEC84B',
                    400: '#FDB022',
                    500: '#F79009',
                    600: '#DC6803',
                    700: '#B54708',
                    800: '#93370D',
                    900: '#7A2E0E',
                },
                error: {
                    25: '#FFFBFA',
                    50: '#FEF3F2',
                    100: '#FEE4E2',
                    200: '#FECDCA',
                    300: '#FDA29B',
                    400: '#F97066',
                    500: '#F04438',
                    600: '#D92D20',
                    700: '#B42318',
                    800: '#912018',
                    900: '#7A271A',
                },
                indigo: '#5856D6',
                cyan: '#32ADE6',
                mint: '#00C7BE',
                "vtd-primary": colors.sky,
                "vtd-secondary": colors.zinc,
            },

            typography: ({ theme }) => ({
                DEFAULT: {
                    css: {
                        '--tw-prose-paragraphs': theme('colors.gray[300]'),
                        '--tw-prose-headings': theme('colors.gray[300]'),
                        '--tw-prose-counters': theme('colors.gray[300]'),
                        '--tw-prose-bold': theme('colors.gray[300]'),
                        h1: {
                            fontSize: '28px',
                            lineHeight: '28px'
                        },
                        h2: {
                            fontSize: '24px',
                            lineHeight: '24px'
                        },
                        h3: {
                            fontSize: '20px',
                            lineHeight: '20px'
                        },
                        p: {
                            fontSize: '14px',
                            lineHeight: '24px'
                        }
                    },
                },
            }),
        },
        fontSize: {
            'xs': ['12px'],
            'sm': ['14px'],
            'lg': ['18px', {
                fontWeight: '600',
            }],
            'xl': ['20px', {
                fontWeight: '600',
            }],
            '2xl': ['24px', {
                fontWeight: '600',
            }],
        }
    },

    plugins: [require('@tailwindcss/forms'), require('@tailwindcss/typography')],
}
