import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

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
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                terracota: {
                    DEFAULT: '#A64B35',
                    light: '#C1705D',
                    dark: '#823A29',
                },
                cantera: {
                    rosa: '#D9A094',
                    clara: '#E5D3C5',
                    gris: '#BDB7AB',
                },
                contraste: {
                    azul: '#1B365D',
                    verde: '#2D4C3E',
                },
            },
        },
    },

    plugins: [forms],
};
