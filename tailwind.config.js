import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './node_modules/preline/dist/*.js',
    ],
    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                brand: {
                    50: '#f0f9ff', 100: '#e0f2fe', 200: '#bae6fd',
                    300: '#7dd3fc', 400: '#38bdf8', 500: '#0ea5e9',
                    600: '#0284c7', 700: '#0369a1',
                },
            },
        },
    },
    plugins: [forms],
};
