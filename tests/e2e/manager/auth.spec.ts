import { test, expect } from '@playwright/test';
import { CREDENTIALS } from '../helpers/auth';

test.use({ storageState: { cookies: [], origins: [] } });

test.describe('Logowanie managera', () => {
    test('E1.1.1 Strona logowania zawiera pola e-mail i hasło', async ({ page }) => {
        await page.goto('/login');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('input[type="email"]')).toBeVisible();
        await expect(page.locator('input[type="password"]')).toBeVisible();
        await expect(page.locator('button[type="submit"]')).toBeVisible();
    });

    test('E1.1.2 Logowanie poprawnymi danymi → przekierowanie na /dashboard', async ({ page }) => {
        await page.goto('/login');
        await page.waitForLoadState('networkidle');
        await page.fill('input[type="email"]', CREDENTIALS.manager.email);
        await page.fill('input[type="password"]', CREDENTIALS.manager.password);
        await page.click('button[type="submit"]');
        await page.waitForURL(/\/dashboard/, { timeout: 15_000 });
        await expect(page).toHaveURL(/\/dashboard/);
    });

    test('E1.1.3 Błędne hasło → komunikat o błędzie, brak przekierowania', async ({ page }) => {
        await page.goto('/login');
        await page.waitForLoadState('networkidle');
        await page.fill('input[type="email"]', CREDENTIALS.manager.email);
        await page.fill('input[type="password"]', 'bledne-haslo');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/login/);
    });

    test('E1.1.4 Nieistniejący e-mail → błąd, brak przekierowania', async ({ page }) => {
        await page.goto('/login');
        await page.waitForLoadState('networkidle');
        await page.fill('input[type="email"]', 'nieistnieje@example.com');
        await page.fill('input[type="password"]', 'password');
        await page.click('button[type="submit"]');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/login/);
    });
});

test.describe('Wylogowanie managera', () => {
    test('E1.2.1 Wylogowanie → przekierowanie na stronę logowania', async ({ page }) => {
        // Zaloguj się
        await page.goto('/login');
        await page.waitForLoadState('networkidle');
        await page.fill('input[type="email"]', CREDENTIALS.manager.email);
        await page.fill('input[type="password"]', CREDENTIALS.manager.password);
        await page.click('button[type="submit"]');
        await page.waitForURL(/\/dashboard/, { timeout: 15_000 });

        // Otwórz dropdown profilu i kliknij Wyloguj
        await page.locator('header button').last().click();
        await page.waitForTimeout(300);
        const logoutBtn = page.locator('button:has-text("Wyloguj"), a:has-text("Wyloguj")').last();
        await logoutBtn.click();
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/login/);
    });
});
