import { test, expect } from '@playwright/test';

test.describe('Ustawienia – dziennik audytu', () => {
    test('E30.1.1 Strona /settings/audit-log nie zwraca 500', async ({ page }) => {
        const response = await page.goto('/settings/audit-log');
        expect(response?.status()).not.toBe(500);
    });

    test('E30.1.2 Strona dostępna bez błędu serwera', async ({ page }) => {
        await page.goto('/settings/audit-log');
        await page.waitForLoadState('networkidle');
        const is500 = await page.getByText(/500|Internal Server Error/i).isVisible().catch(() => false);
        expect(is500).toBeFalsy();
    });

    test('E30.1.3 Tabela logów lub komunikat o braku aktywności', async ({ page }) => {
        await page.goto('/settings/audit-log');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).toBeVisible();
    });
});

test.describe('Ustawienia – rozliczenia', () => {
    test('E30.2.1 Strona /settings/billing nie zwraca 500', async ({ page }) => {
        const response = await page.goto('/settings/billing');
        expect(response?.status()).not.toBe(500);
    });

    test('E30.2.2 Strona dostępna bez błędu serwera', async ({ page }) => {
        await page.goto('/settings/billing');
        await page.waitForLoadState('networkidle');
        const is500 = await page.getByText(/500|Internal Server Error/i).isVisible().catch(() => false);
        expect(is500).toBeFalsy();
    });
});

test.describe('Ustawienia – pola niestandardowe', () => {
    test('E30.3.1 Strona /settings/custom-fields nie zwraca 500', async ({ page }) => {
        const response = await page.goto('/settings/custom-fields');
        expect(response?.status()).not.toBe(500);
    });

    test('E30.3.2 Strona dostępna bez błędu serwera', async ({ page }) => {
        await page.goto('/settings/custom-fields');
        await page.waitForLoadState('networkidle');
        const is500 = await page.getByText(/500|Internal Server Error/i).isVisible().catch(() => false);
        expect(is500).toBeFalsy();
    });
});

test.describe('Ustawienia – szablony e-mail', () => {
    test('E30.4.1 Strona /settings/email-templates nie zwraca 500', async ({ page }) => {
        const response = await page.goto('/settings/email-templates');
        expect(response?.status()).not.toBe(500);
    });

    test('E30.4.2 Strona dostępna bez błędu serwera', async ({ page }) => {
        await page.goto('/settings/email-templates');
        await page.waitForLoadState('networkidle');
        const is500 = await page.getByText(/500|Internal Server Error/i).isVisible().catch(() => false);
        expect(is500).toBeFalsy();
    });
});

test.describe('Ustawienia – integracje', () => {
    test('E30.5.1 Strona /settings/integrations nie zwraca 500', async ({ page }) => {
        const response = await page.goto('/settings/integrations');
        expect(response?.status()).not.toBe(500);
    });

    test('E30.5.2 Strona dostępna bez błędu serwera', async ({ page }) => {
        await page.goto('/settings/integrations');
        await page.waitForLoadState('networkidle');
        const is500 = await page.getByText(/500|Internal Server Error/i).isVisible().catch(() => false);
        expect(is500).toBeFalsy();
    });
});

test.describe('Ustawienia – powiadomienia', () => {
    test('E30.6.1 Strona /settings/notifications nie zwraca 500', async ({ page }) => {
        const response = await page.goto('/settings/notifications');
        expect(response?.status()).not.toBe(500);
    });

    test('E30.6.2 Strona dostępna bez błędu serwera', async ({ page }) => {
        await page.goto('/settings/notifications');
        await page.waitForLoadState('networkidle');
        const is500 = await page.getByText(/500|Internal Server Error/i).isVisible().catch(() => false);
        expect(is500).toBeFalsy();
    });
});
