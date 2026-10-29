import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Livewire/**/*.php',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            colors: {
                rmsc: {
                    red: '#ED1B24',
                    'red-dark': '#C41219',
                    'red-light': '#FEE8E9',
                    slate: '#334155',
                    'slate-dark': '#1E293B',
                    'slate-light': '#475569',
                },
            },
            fontFamily: {
                heading: ['"Poppins"', 'sans-serif'],
                sans: ['"Poppins"', 'sans-serif'],
            },
        },
    },

    plugins: [forms],
};
