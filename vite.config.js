import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['app/Domains/Shared/Resources/css/app.css', 'app/Domains/Shared/Resources/js/app.js'],
            refresh: true,
        }),
    ],
});
