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
        'border-accent', 'bg-accent', 'text-accent', 'hover:text-accent', 'hover:bg-accent', 'hover:border-accent',
        'border-primary', 'bg-primary', 'text-primary', 'hover:text-primary', 'hover:bg-primary', 'hover:border-primary',
        'border-secondary', 'bg-secondary', 'text-secondary', 'hover:text-secondary', 'hover:bg-secondary', 'hover:border-secondary',
        'border-tertiary', 'bg-tertiary', 'text-tertiary', 'hover:text-tertiary', 'hover:bg-tertiary', 'hover:border-tertiary',
        'border-error', 'bg-error', 'text-error', 'hover:text-error', 'hover:bg-error', 'hover:border-error',
        'border-info', 'bg-info', 'text-info', 'hover:text-info', 'hover:bg-info', 'hover:border-info',
        'border-success', 'bg-success', 'text-success', 'hover:text-success', 'hover:bg-success', 'hover:border-success',
        'border-warning', 'bg-warning', 'text-warning', 'hover:text-warning', 'hover:bg-warning', 'hover:border-warning',
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

