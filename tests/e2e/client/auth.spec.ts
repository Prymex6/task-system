import { test, expect } from '@playwright/test';
import { CREDENTIALS } from '../helpers/auth';

test.use({ storageState: { cookies: [], origins: [] } });

test.describe('Logowanie do portalu klienta', () => {
    test('E8.1.1 Strona /portal/logowanie zawiera formularz logowania', async ({ page }) => {
        await page.goto('/portal/logowanie');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('input[type="email"]')).toBeVisible();
        await expect(page.locator('input[type="password"]')).toBeVisible();
        await expect(page.locator('button[type="submit"]')).toBeVisible();
    });

    test('E8.1.2 Logowanie poprawnymi danymi → przekierowanie na /portal/dashboard', async ({ page }) => {
        await page.goto('/portal/logowanie');
        await page.waitForLoadState('networkidle');
        await page.fill('input[type="email"]', CREDENTIALS.client.email);
        await page.fill('input[type="password"]', CREDENTIALS.client.password);
        await page.click('button[type="submit"]');
        await page.waitForURL(/\/portal\/dashboard/, { timeout: 15_000 });
        await expect(page).toHaveURL(/\/portal\/dashboard/);
    });

    test('E8.1.3 Błędne hasło → brak przekierowania, pozostaje na stronie logowania', async ({ page }) => {
        await page.goto('/portal/logowanie');
        await page.waitForLoadState('networkidle');
        await page.fill('input[type="email"]', CREDENTIALS.client.email);
        await page.fill('input[type="password"]', 'wrong-password');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/portal\/logowanie/);
    });

    test('E8.1.4 Dostęp do /portal/dashboard bez logowania → redirect na /portal/logowanie', async ({ page }) => {
        await page.goto('/portal/dashboard');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/portal\/logowanie/);
    });
});
