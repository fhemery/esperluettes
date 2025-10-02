import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        //'./storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
        './resources/js/**/*.vue',
        // Include Domain-based module resources
        './app/Domains/**/Resources/views/**/*.blade.php',
        './app/Domains/**/Views/**/*.blade.php',
        './app/Domains/**/Resources/js/**/*.js',
        './app/Domains/**/Resources/js/**/*.vue',
    ],
    safelist: [
        'bg-blue-100', 'border-blue-500', 'text-blue-700',
        'bg-green-100', 'border-green-500', 'text-green-700',
        'bg-red-100', 'border-red-500', 'text-red-700',
        'hover:text-blue-900', 'hover:text-green-900', 'hover:text-red-900',
        'border-accent'
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Manrope', ...defaultTheme.fontFamily.sans],
            },
            // Map CSS variables to Tailwind colors for runtime theming
            // Each expects an RGB triplet variable (e.g., 30 64 175)
            colors: {
                bg: 'rgb(var(--color-bg) / <alpha-value>)',
                fg: 'rgb(var(--color-fg) / <alpha-value>)',
                primary: 'rgb(var(--color-primary) / <alpha-value>)',
                secondary: 'rgb(var(--color-secondary) / <alpha-value>)',
                accent: 'rgb(var(--color-accent) / <alpha-value>)',
                tertiary: 'rgb(var(--color-tertiary) / <alpha-value>)',
                error: 'rgb(var(--color-error-fg) / <alpha-value>)',
                info: 'rgb(var(--color-info-fg) / <alpha-value>)',
                success: 'rgb(var(--color-success-fg) / <alpha-value>)',
                warning: 'rgb(var(--color-warning-fg) / <alpha-value>)',
                read: 'rgb(var(--color-read-bg) / <alpha-value>)',
            },
        },
    },

    plugins: [forms],
};

