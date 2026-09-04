import { defineConfig, devices } from '@playwright/test';

/**
 * Playwright E2E config for the GlowRez (Booksy) company/owner panel.
 *
 * Focus: the salon-owner "add booking" experience plus smoke coverage of the
 * key owner pages. Tests live in tests/e2e so they never collide with the
 * PHPUnit suite in tests/Feature and tests/Unit.
 *
 * Prereqs before running:
 *   - MySQL (XAMPP) up with the `booksy` database.
 *   - The demo owner seeded:  php artisan db:seed --class=DemoOwnerSeeder
 *   - The demo staff must be bookable (is_bookable=1) and linked to services,
 *     otherwise the "assign a specific employee" assertion is skipped.
 */
export default defineConfig({
  testDir: './tests/e2e',
  timeout: 60_000,
  expect: { timeout: 10_000 },
  fullyParallel: false,
  workers: 1,
  reporter: [['list'], ['html', { open: 'never' }]],

  use: {
    baseURL: 'http://127.0.0.1:8000',
    locale: 'ar',
    screenshot: 'only-on-failure',
    trace: 'on-first-retry',
    video: 'off',
  },

  // Let Playwright boot the Laravel dev server if one isn't already running.
  webServer: {
    command: 'php artisan serve --host=127.0.0.1 --port=8000',
    url: 'http://127.0.0.1:8000/company/login',
    reuseExistingServer: true,
    timeout: 120_000,
  },

  projects: [
    { name: 'chromium', use: { ...devices['Desktop Chrome'] } },
  ],
});
