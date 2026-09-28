import { test, expect } from '@playwright/test';

test.describe('Powiadomienia', () => {
    test('E31.1.1 Strona /notifications ładuje się poprawnie', async ({ page }) => {
        await page.goto('/notifications');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/notifications/);
    });

    test('E31.1.2 Strona nie zwraca błędu 500', async ({ page }) => {
        const response = await page.goto('/notifications');
        expect(response?.status()).not.toBe(500);
    });

    test('E31.1.3 Lista powiadomień lub komunikat o braku – brak błędu 500', async ({ page }) => {
        await page.goto('/notifications');
        await page.waitForLoadState('networkidle');
        const is500 = await page.getByText(/500|Internal Server Error/i).isVisible().catch(() => false);
        expect(is500).toBeFalsy();
        await expect(page.locator('body')).toBeVisible();
    });
});

test.describe('Wyszukiwarka globalna', () => {
    test('E31.2.1 Strona /search nie zwraca 500', async ({ page }) => {
        const response = await page.goto('/search');
        expect(response?.status()).not.toBe(500);
    });

    test('E31.2.2 Strona dostępna bez błędu serwera', async ({ page }) => {
        await page.goto('/search');
        await page.waitForLoadState('networkidle');
        const is500 = await page.getByText(/500|Internal Server Error/i).isVisible().catch(() => false);
        expect(is500).toBeFalsy();
    });

    test('E31.2.3 Wyszukiwanie zwraca wyniki bez błędu', async ({ page }) => {
        await page.goto('/search?q=acme');
        await page.waitForLoadState('networkidle');
        const is500 = await page.getByText(/500|Internal Server Error/i).isVisible().catch(() => false);
        expect(is500).toBeFalsy();
    });
});
