import { test, expect } from '@playwright/test';
import { CREDENTIALS } from '../helpers/auth';

// Ten plik uruchamiany bez storageState (własny login)
test.use({ storageState: { cookies: [], origins: [] } });

test.describe('Logowanie landlorda', () => {
    test('E21.1.1 Strona /admin/login zawiera formularz logowania', async ({ page }) => {
        await page.goto('/admin/login');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('input[type="email"]')).toBeVisible();
        await expect(page.locator('input[type="password"]')).toBeVisible();
        await expect(page.locator('button[type="submit"]')).toBeVisible();
    });

    test('E21.1.2 Poprawne dane → redirect na /admin/dashboard', async ({ page }) => {
        await page.goto('/admin/login');
        await page.waitForLoadState('networkidle');
        await page.fill('input[type="email"]', CREDENTIALS.landlord.email);
        await page.fill('input[type="password"]', CREDENTIALS.landlord.password);
        await page.click('button[type="submit"]');
        await page.waitForURL(/\/admin\/dashboard/, { timeout: 15_000 });
        await expect(page).toHaveURL(/\/admin\/dashboard/);
    });

    test('E21.1.3 Błędne hasło → brak przekierowania', async ({ page }) => {
        await page.goto('/admin/login');
        await page.waitForLoadState('networkidle');
        await page.fill('input[type="email"]', CREDENTIALS.landlord.email);
        await page.fill('input[type="password"]', 'bledne-haslo');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        await expect(page).not.toHaveURL(/\/admin\/dashboard/);
    });

    test('E21.1.4 Dostęp do /admin/dashboard bez logowania → redirect', async ({ page }) => {
        await page.goto('/admin/dashboard');
        await page.waitForLoadState('networkidle');
        await expect(page).not.toHaveURL(/\/admin\/dashboard/);
    });
});
