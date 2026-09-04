import { Page, expect } from '@playwright/test';

export const OWNER = {
  email: 'owner@booksy.demo',
  password: 'password',
};

/**
 * Console errors we treat as environmental noise, not app bugs:
 *  - Reverb / Pusher websocket retries when the realtime server (port 8080)
 *    isn't running in the local dev session.
 */
export function isIgnorableConsoleError(text: string): boolean {
  return (
    /localhost:8080/.test(text) ||
    /WebSocket/.test(text) ||
    /pusher/i.test(text)
  );
}

/** Collect genuine (non-ignorable) console errors while a test runs. */
export function trackConsoleErrors(page: Page): string[] {
  const errors: string[] = [];
  page.on('console', (m) => {
    if (m.type() === 'error' && !isIgnorableConsoleError(m.text())) {
      errors.push(m.text());
    }
  });
  page.on('pageerror', (e) => errors.push(String(e)));
  return errors;
}

/** Dismiss the first-run onboarding tour / setup overlay if it appears. */
export async function dismissOnboarding(page: Page): Promise<void> {
  for (const label of ['تخطي', 'إغلاق']) {
    const btn = page.getByRole('button', { name: label }).first();
    if (await btn.isVisible().catch(() => false)) {
      await btn.click().catch(() => {});
    }
  }
}

/**
 * Log in as the demo salon owner and land on the dashboard.
 *
 * Note: the app's UI language is driven by its own session locale, which
 * defaults to English (APP_LOCALE=en) regardless of the browser locale. Since
 * the real target audience is Arabic (Syria), we force Arabic via /locale/ar so
 * the tests exercise the RTL experience owners actually use.
 */
export async function loginAsOwner(page: Page): Promise<void> {
  await page.goto('/company/login');
  await page.getByPlaceholder('example@email.com').fill(OWNER.email);
  await page.getByPlaceholder('••••••••').fill(OWNER.password);
  await page.getByRole('button', { name: /تسجيل الدخول|Sign in/ }).click();
  await expect(page).toHaveURL(/\/company\/dashboard|\/$|127\.0\.0\.1:8000\/?$/);
  await page.goto('/locale/ar'); // force Arabic UI
  await dismissOnboarding(page);
}
