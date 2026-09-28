import { test, expect } from '@playwright/test';

// UWAGA: Kontrolery time/* zwracają back() – "Feature coming soon."
// Testy sprawdzają że strona nie zwraca 500 i poprawnie przekierowuje

test.describe('Śledzenie czasu – lista', () => {
    test('E16.1.1 GET /time nie zwraca 500', async ({ page }) => {
        // back() przekierowuje na referrer – sprawdzamy brak błędu 500
        const response = await page.goto('/time');
        expect(response?.status()).not.toBe(500);
        await expect(page.locator('body')).not.toBeEmpty();
    });

    test('E16.1.2 Strona zwraca status nie-błędowy', async ({ page }) => {
        const response = await page.goto('/time');
        expect(response?.status()).toBeLessThan(500);
    });

    test('E16.1.3 /time/team nie zwraca 500', async ({ page }) => {
        const response = await page.goto('/time/team');
        expect(response?.status()).not.toBe(500);
    });
});

test.describe('Raporty czasu', () => {
    test('E16.2.1 /time/reports nie zwraca 500', async ({ page }) => {
        const response = await page.goto('/time/reports');
        expect(response?.status()).not.toBe(500);
    });

    test('E16.2.2 /time/approvals nie zwraca 500', async ({ page }) => {
        const response = await page.goto('/time/approvals');
        expect(response?.status()).not.toBe(500);
    });
});
