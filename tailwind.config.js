import defaultTheme from 'tailwindcss/defaultTheme';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50: '#FEECED',
                    100: '#FBD0D2',
                    200: '#F6A1A5',
                    300: '#F17177',
                    400: '#EE474E',
                    500: '#EB1C22',
                    600: '#C5171C',
                    700: '#90191C',
                    800: '#7A1417',
                    900: '#5E0F11',
                },
                gold: {
                    300: '#FFE066',
                    400: '#FFD43B',
                    500: '#FFCB08',
                    600: '#F6B909',
                    700: '#BD8F08',
                },
            },
            boxShadow: {
                card: '0 6px 20px -8px rgba(0, 0, 0, 0.18)',
                soft: '0 2px 12px -4px rgba(0, 0, 0, 0.12)',
            },
            borderRadius: {
                '4xl': '2rem',
            },
        },
    },
    plugins: [
    require('@tailwindcss/forms'),
    require('@tailwindcss/typography'),
    ],
};
