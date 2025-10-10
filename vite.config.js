import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const appUrl = env.APP_URL ?? 'http://localhost';

    return {
        server: {
            watch: {
                ignored: ['dist/**'],
            },
            // Only enable proxy during development
            ...(mode === 'development'
                ? {
                      // Ensure requests to /images, /storage, etc. hit the Laravel app (APP_URL)
                      // instead of the Vite dev server origin.
                      proxy: {
                          '/images': {
                              target: appUrl,
                              changeOrigin: true,
                          },
                          '/storage': {
                              target: appUrl,
                              changeOrigin: true,
                          },
                          '/fonts': {
                              target: appUrl,
                              changeOrigin: true,
                          },
                      },
                  }
                : {}),
        },
        plugins: [
            laravel({
                input: ['app/Domains/Shared/Resources/css/app.scss', 'app/Domains/Shared/Resources/js/app.js'],
                refresh: true,
            }),
        ],
    };
});
