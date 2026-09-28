import { test, expect } from '@playwright/test';

test.describe('Webhooki', () => {
    test('E32.1.1 Strona /webhooks nie zwraca 500', async ({ page }) => {
        const response = await page.goto('/webhooks');
        expect(response?.status()).not.toBe(500);
    });

    test('E32.1.2 Strona dostępna bez błędu serwera', async ({ page }) => {
        await page.goto('/webhooks');
        await page.waitForLoadState('networkidle');
        const is500 = await page.getByText(/500|Internal Server Error/i).isVisible().catch(() => false);
        expect(is500).toBeFalsy();
    });

    test('E32.1.3 Strona /webhooks/create nie zwraca 500', async ({ page }) => {
        const response = await page.goto('/webhooks/create');
        expect(response?.status()).not.toBe(500);
    });

    test('E32.1.4 Brak błędu serwera na stronie tworzenia', async ({ page }) => {
        await page.goto('/webhooks/create');
        await page.waitForLoadState('networkidle');
        const is500 = await page.getByText(/500|Internal Server Error/i).isVisible().catch(() => false);
        expect(is500).toBeFalsy();
    });
});
