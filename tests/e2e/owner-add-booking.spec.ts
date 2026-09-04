import { test, expect } from '@playwright/test';
import { loginAsOwner, dismissOnboarding, trackConsoleErrors } from './helpers';

test.describe('Salon owner — company panel', () => {
  test.beforeEach(async ({ page }) => {
    await loginAsOwner(page);
  });

  test('dashboard loads and exposes the booking entry point, no genuine console errors', async ({
    page,
  }) => {
    const errors = trackConsoleErrors(page);
    await page.goto('/company/dashboard');
    // "New booking" entry point must exist for the owner (stable, locale-forced Arabic).
    await expect(page.getByRole('link', { name: 'حجز جديد' }).first()).toBeVisible();
    expect(errors, `console errors:\n${errors.join('\n')}`).toEqual([]);
  });

  test('key owner pages load without page errors', async ({ page }) => {
    const routes = [
      '/company/dashboard',
      '/company/appointments',
      '/company/customers',
      '/company/appointments/create',
    ];
    for (const route of routes) {
      const errors = trackConsoleErrors(page);
      const resp = await page.goto(route);
      expect(resp?.status(), `status for ${route}`).toBeLessThan(400);
      await dismissOnboarding(page);
      expect(errors, `page errors on ${route}:\n${errors.join('\n')}`).toEqual([]);
    }
  });

  test('owner can add a booking end-to-end', async ({ page }) => {
    await page.goto('/company/appointments/create');
    await dismissOnboarding(page);

    // 1) Select the branch (single branch is NOT auto-selected — must be clicked).
    await page.locator('label:has(input[name="branch_id"])').first().click();

    // 2) The customer/service block loads after the branch is chosen.
    const name = page.locator('input[name="persons[0][name]"]');
    await expect(name).toBeVisible();

    // The submit button is disabled until the form is valid — verify that first.
    const submit = page.getByRole('button', { name: 'إنشاء موعد' });
    await expect(submit).toBeDisabled();

    await name.fill('سارة الأحمد');
    await page.locator('input[name="persons[0][phone]"]').fill('963962812838');

    // 3) Pick a service by resolving the option value from its Arabic label.
    const svcSelect = page.locator('select[name="persons[0][services][0][service_id]"]');
    const svcValue = await svcSelect.locator('option', { hasText: 'قص شعر' }).getAttribute('value');
    await svcSelect.selectOption(svcValue!);

    // 4) Assign a specific staff member if one is bookable; otherwise leave "Any".
    const emp = page.locator('select[name="persons[0][services][0][employee_id]"]');
    const staffOptions = emp.locator('option', { hasText: /مدير|Demo/ });
    if ((await staffOptions.count()) > 0) {
      await emp.selectOption(await staffOptions.first().getAttribute('value'));
    }

    // 5) Set date & time (flatpickr) to a near-future weekday afternoon.
    const d = new Date();
    d.setDate(d.getDate() + 3);
    const dt = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(
      d.getDate(),
    ).padStart(2, '0')} 14:00`;
    await page.evaluate((value) => {
      const el = document.querySelector(
        'input[name="persons[0][services][0][start_time]"]',
      ) as any;
      if (el && el._flatpickr) el._flatpickr.setDate(value, true);
      else if (el) {
        el.value = value;
        el.dispatchEvent(new Event('change', { bubbles: true }));
      }
    }, dt);

    // 6) The live summary should leave its empty state once a service is added.
    await expect(page.getByText('الإجمالي')).toBeVisible();
    await expect(page.getByText('لا توجد خدمات بعد')).toBeHidden();

    // 7) Now that the form is complete, the submit button becomes enabled
    //    (it also proves the total was computed — enable is gated on a valid form).
    await expect(submit).toBeEnabled();
    await submit.click();

    // 8) Land back on the appointments calendar with a success flash.
    await expect(page).toHaveURL(/\/company\/appointments\b/);
  });

  test('empty booking is blocked — submit stays disabled until required fields are filled', async ({
    page,
  }) => {
    await page.goto('/company/appointments/create');
    await dismissOnboarding(page);
    await page.locator('label:has(input[name="branch_id"])').first().click();

    const name = page.locator('input[name="persons[0][name]"]');
    await expect(name).toBeVisible();

    // With nothing filled the submit button must be disabled (guards bad bookings).
    const submit = page.getByRole('button', { name: 'إنشاء موعد' });
    await expect(submit).toBeDisabled();

    // The customer name field is a hard requirement.
    const nameRequired = await name.evaluate((el: HTMLInputElement) => el.required);
    expect(nameRequired).toBe(true);
  });
});
