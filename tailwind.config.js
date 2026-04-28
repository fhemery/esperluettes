import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
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

