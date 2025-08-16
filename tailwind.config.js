import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './resources/js/**/*.vue',
        // Include Domain-based module resources
        './app/Domains/**/Resources/views/**/*.blade.php',
        './app/Domains/**/Resources/js/**/*.js',
        './app/Domains/**/Resources/js/**/*.vue',
    ],

    safelist: [
        'bg-blue-100', 'border-blue-500', 'text-blue-700',
        'bg-green-100', 'border-green-500', 'text-green-700',
        'bg-red-100', 'border-red-500', 'text-red-700',
        'hover:text-blue-900', 'hover:text-green-900', 'hover:text-red-900',
        // Dynamic classes applied at runtime (editor counter)
        'text-red-600', 'text-gray-500'
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Figtree', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};
