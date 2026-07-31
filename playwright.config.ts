import { defineConfig, devices } from '@playwright/test';

/**
 * E2E suite.
 *
 * Runs against a second instance of the app on :8080, backed by a throwaway
 * SQLite database (see `.env.e2e`), so specs are free to create, edit and
 * delete anything: the local MySQL dev database is never touched.
 *
 *   npm run e2e                    # headless, everything
 *   npm run e2e -- --headed        # watch it happen
 *   npm run e2e:ui                 # Playwright's interactive runner
 *   E2E_SKIP_RESET=1 npm run e2e   # keep the current database
 *
 * `globalSetup` rebuilds and reseeds that database before the run, which is
 * what lets the fixtures in database/seeders/E2ESeeder.php be constants rather
 * than hopes.
 */

export const BASE_URL = process.env.E2E_BASE_URL ?? `http://localhost:${process.env.E2E_PORT ?? 8080}`;

export default defineConfig({
  testDir: './e2e/tests',
  globalSetup: './e2e/support/global-setup.ts',
  globalTeardown: './e2e/support/global-teardown.ts',

  // Specs share one database, so they must not race each other on it.
  // Files run serially; within a file, tests run in declaration order.
  workers: 1,
  fullyParallel: false,

  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  timeout: 30_000,
  expect: { timeout: 10_000 },

  reporter: process.env.CI ? [['github'], ['html', { open: 'never' }]] : [['list']],

  use: {
    baseURL: BASE_URL,
    locale: 'fr-FR',
    // Evidence, but only when something actually fails.
    screenshot: 'only-on-failure',
    trace: 'retain-on-failure',
    video: 'off',
  },

  projects: [
    // Logs each role in once and saves its cookies; every other project reuses
    // them instead of walking the login form again.
    { name: 'auth', testDir: './e2e/support', testMatch: /auth\.setup\.ts/ },
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'], viewport: { width: 1280, height: 900 } },
      dependencies: ['auth'],
    },
  ],

  webServer: {
    command: `./vendor/bin/sail artisan serve --env=e2e --host=0.0.0.0 --port=${process.env.E2E_PORT ?? 8080}`,
    url: BASE_URL,
    reuseExistingServer: !process.env.CI,
    timeout: 120_000,
    env: { PHP_CLI_SERVER_WORKERS: process.env.PHP_CLI_SERVER_WORKERS ?? '4' },
  },
});
