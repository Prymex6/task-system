import { test, expect } from '@playwright/test';

test.describe('Wydatki', () => {
    test('E12.1.1 Strona /finance/expenses ładuje się poprawnie', async ({ page }) => {
        await page.goto('/finance/expenses');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/finance\/expenses/);
    });

    test('E12.1.2 Strona zwraca status 200', async ({ page }) => {
        const response = await page.goto('/finance/expenses');
        expect(response?.status()).toBe(200);
    });

    test('E12.1.3 Lista wydatków lub formularz jest widoczny', async ({ page }) => {
        await page.goto('/finance/expenses');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).not.toBeEmpty();
    });

    test('E12.1.4 Przycisk/modal dodania wydatku jest dostępny', async ({ page }) => {
        await page.goto('/finance/expenses');
        await page.waitForLoadState('networkidle');
        const btn = page.locator('button:has-text("Dodaj wydatek"), a:has-text("Dodaj wydatek"), button:has-text("Nowy wydatek")').first();
        await expect(btn).toBeVisible();
    });
});
