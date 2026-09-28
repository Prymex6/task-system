import { test, expect } from '@playwright/test';

test.describe('Support – szablony odpowiedzi', () => {
    test('E33.1.1 Strona /support/canned-responses nie zwraca 500', async ({ page }) => {
        const response = await page.goto('/support/canned-responses');
        expect(response?.status()).not.toBe(500);
    });

    test('E33.1.2 Strona dostępna bez błędu serwera', async ({ page }) => {
        await page.goto('/support/canned-responses');
        await page.waitForLoadState('networkidle');
        const is500 = await page.getByText(/500|Internal Server Error/i).isVisible().catch(() => false);
        expect(is500).toBeFalsy();
    });
});

test.describe('Support – działy', () => {
    test('E33.2.1 Strona /support/departments nie zwraca 500', async ({ page }) => {
        const response = await page.goto('/support/departments');
        expect(response?.status()).not.toBe(500);
    });

    test('E33.2.2 Strona dostępna bez błędu serwera', async ({ page }) => {
        await page.goto('/support/departments');
        await page.waitForLoadState('networkidle');
        const is500 = await page.getByText(/500|Internal Server Error/i).isVisible().catch(() => false);
        expect(is500).toBeFalsy();
    });
});

test.describe('Support – polityki SLA', () => {
    test('E33.3.1 Strona /support/sla-policies nie zwraca 500', async ({ page }) => {
        const response = await page.goto('/support/sla-policies');
        expect(response?.status()).not.toBe(500);
    });

    test('E33.3.2 Strona dostępna bez błędu serwera', async ({ page }) => {
        await page.goto('/support/sla-policies');
        await page.waitForLoadState('networkidle');
        const is500 = await page.getByText(/500|Internal Server Error/i).isVisible().catch(() => false);
        expect(is500).toBeFalsy();
    });
});
