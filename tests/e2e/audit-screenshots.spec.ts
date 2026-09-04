import { test } from '@playwright/test';
import fs from 'fs';

// Standalone capture script for the audit report. Run:
//   npx playwright test audit-screenshots --project=chromium
const OUT = 'C:/Users/hp/Desktop/HTML/glowrez-audit/screenshots';
fs.mkdirSync(OUT, { recursive: true });

const OWNER = { email: 'royalbeauty@glowrez.test', password: 'Password123!' };

async function loginOwner(page: any) {
  await page.goto('/company/login');
  await page.getByPlaceholder('example@email.com').fill(OWNER.email);
  await page.getByPlaceholder('••••••••').fill(OWNER.password);
  await page.getByRole('button', { name: /تسجيل الدخول|Sign in/ }).click();
  await page.waitForLoadState('networkidle');
  await page.goto('/locale/ar');
}

const viewports = [
  { name: 'desktop', width: 1366, height: 900 },
  { name: 'tablet', width: 768, height: 1024 },
  { name: 'mobile', width: 390, height: 844 },
];

test('capture owner + public screenshots at 3 viewports', async ({ browser }) => {
  test.setTimeout(120000);
  // Owner pages (authenticated)
  const ctx = await browser.newContext();
  const page = await ctx.newPage();
  await loginOwner(page);
  for (const vp of viewports) {
    await page.setViewportSize({ width: vp.width, height: vp.height });
    await page.goto('/company/dashboard'); await page.waitForTimeout(1200);
    await page.screenshot({ path: `${OUT}/owner-dashboard-${vp.name}.png`, fullPage: vp.name !== 'desktop' });
    await page.goto('/company/appointments/create'); await page.waitForTimeout(1200);
    await page.screenshot({ path: `${OUT}/owner-create-booking-${vp.name}.png`, fullPage: vp.name !== 'desktop' });
    await page.goto('/company/appointments'); await page.waitForTimeout(1500);
    await page.screenshot({ path: `${OUT}/owner-calendar-${vp.name}.png` });
  }
  await ctx.close();

  // Public pages (anonymous)
  const ctx2 = await browser.newContext();
  const page2 = await ctx2.newPage();
  for (const vp of viewports) {
    await page2.setViewportSize({ width: vp.width, height: vp.height });
    await page2.goto('/business/4'); await page2.waitForTimeout(1200);
    await page2.screenshot({ path: `${OUT}/public-company-${vp.name}.png`, fullPage: vp.name !== 'desktop' });
    await page2.goto('/branch/4'); await page2.waitForTimeout(1200);
    await page2.screenshot({ path: `${OUT}/public-branch-${vp.name}.png`, fullPage: vp.name !== 'desktop' });
  }
  await ctx2.close();
});
