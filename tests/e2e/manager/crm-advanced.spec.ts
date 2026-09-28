import { test, expect } from '@playwright/test';

test.describe('CRM – grupy klientów', () => {
    test('E28.1.1 Strona /crm/client-groups nie zwraca 500', async ({ page }) => {
        const response = await page.goto('/crm/client-groups');
        expect(response?.status()).not.toBe(500);
    });

    test('E28.1.2 Strona dostępna bez błędu serwera', async ({ page }) => {
        await page.goto('/crm/client-groups');
        await page.waitForLoadState('networkidle');
        const is500 = await page.getByText(/500|Internal Server Error/i).isVisible().catch(() => false);
        expect(is500).toBeFalsy();
    });
});

test.describe('CRM – etapy deali', () => {
    test('E28.2.1 Strona /crm/deals/stages nie zwraca 500', async ({ page }) => {
        const response = await page.goto('/crm/deals/stages');
        expect(response?.status()).not.toBe(500);
    });

    test('E28.2.2 Strona dostępna bez błędu serwera', async ({ page }) => {
        await page.goto('/crm/deals/stages');
        await page.waitForLoadState('networkidle');
        const is500 = await page.getByText(/500|Internal Server Error/i).isVisible().catch(() => false);
        expect(is500).toBeFalsy();
    });
});

test.describe('Finanse – waluty', () => {
    test('E28.3.1 Strona /finance/currencies nie zwraca 500', async ({ page }) => {
        const response = await page.goto('/finance/currencies');
        expect(response?.status()).not.toBe(500);
    });

    test('E28.3.2 Strona dostępna bez błędu serwera', async ({ page }) => {
        await page.goto('/finance/currencies');
        await page.waitForLoadState('networkidle');
        const is500 = await page.getByText(/500|Internal Server Error/i).isVisible().catch(() => false);
        expect(is500).toBeFalsy();
    });
});

test.describe('Finanse – stawki podatkowe', () => {
    test('E28.4.1 Strona /finance/tax-rates nie zwraca 500', async ({ page }) => {
        const response = await page.goto('/finance/tax-rates');
        expect(response?.status()).not.toBe(500);
    });

    test('E28.4.2 Strona dostępna bez błędu serwera', async ({ page }) => {
        await page.goto('/finance/tax-rates');
        await page.waitForLoadState('networkidle');
        const is500 = await page.getByText(/500|Internal Server Error/i).isVisible().catch(() => false);
        expect(is500).toBeFalsy();
    });
});

test.describe('Kontrakty – typy umów', () => {
    test('E28.5.1 Strona /contracts/types nie zwraca 500', async ({ page }) => {
        const response = await page.goto('/contracts/types');
        expect(response?.status()).not.toBe(500);
    });

    test('E28.5.2 Strona dostępna bez błędu serwera', async ({ page }) => {
        await page.goto('/contracts/types');
        await page.waitForLoadState('networkidle');
        const is500 = await page.getByText(/500|Internal Server Error/i).isVisible().catch(() => false);
        expect(is500).toBeFalsy();
    });
});
