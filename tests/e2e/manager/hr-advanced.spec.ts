import { test, expect } from '@playwright/test';

test.describe('HR – ogłoszenia', () => {
    test('E27.1.1 Strona /hr/announcements ładuje się poprawnie', async ({ page }) => {
        await page.goto('/hr/announcements');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/hr\/announcements/);
    });

    test('E27.1.2 Strona zwraca status 200', async ({ page }) => {
        const response = await page.goto('/hr/announcements');
        expect(response?.status()).toBe(200);
    });

    test('E27.1.3 Strona /hr/announcements nie zwraca 500', async ({ page }) => {
        const response = await page.goto('/hr/announcements');
        expect(response?.status()).not.toBe(500);
    });
});

test.describe('HR – zaproszenia', () => {
    test('E27.2.1 Strona /hr/invitations ładuje się poprawnie', async ({ page }) => {
        await page.goto('/hr/invitations');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/hr\/invitations/);
    });

    test('E27.2.2 Strona zwraca status 200', async ({ page }) => {
        const response = await page.goto('/hr/invitations');
        expect(response?.status()).toBe(200);
    });
});

test.describe('HR – typy urlopów', () => {
    test('E27.3.1 Strona /hr/leave-types nie zwraca 500', async ({ page }) => {
        const response = await page.goto('/hr/leave-types');
        expect(response?.status()).not.toBe(500);
    });

    test('E27.3.2 Strona dostępna bez błędu serwera', async ({ page }) => {
        await page.goto('/hr/leave-types');
        await page.waitForLoadState('networkidle');
        const is500 = await page.getByText(/500|Internal Server Error/i).isVisible().catch(() => false);
        expect(is500).toBeFalsy();
    });
});

test.describe('HR – oceny pracownicze', () => {
    test('E27.4.1 Strona /hr/performance nie zwraca 500', async ({ page }) => {
        const response = await page.goto('/hr/performance');
        expect(response?.status()).not.toBe(500);
    });

    test('E27.4.2 Strona dostępna bez błędu serwera', async ({ page }) => {
        await page.goto('/hr/performance');
        await page.waitForLoadState('networkidle');
        const is500 = await page.getByText(/500|Internal Server Error/i).isVisible().catch(() => false);
        expect(is500).toBeFalsy();
    });
});

test.describe('HR – stanowiska', () => {
    test('E27.5.1 Strona /hr/positions nie zwraca 500', async ({ page }) => {
        const response = await page.goto('/hr/positions');
        expect(response?.status()).not.toBe(500);
    });

    test('E27.5.2 Strona dostępna bez błędu serwera', async ({ page }) => {
        await page.goto('/hr/positions');
        await page.waitForLoadState('networkidle');
        const is500 = await page.getByText(/500|Internal Server Error/i).isVisible().catch(() => false);
        expect(is500).toBeFalsy();
    });
});

test.describe('HR – działy', () => {
    test('E27.6.1 Strona /hr/departments nie zwraca 500', async ({ page }) => {
        const response = await page.goto('/hr/departments');
        expect(response?.status()).not.toBe(500);
    });

    test('E27.6.2 Strona dostępna bez błędu serwera', async ({ page }) => {
        await page.goto('/hr/departments');
        await page.waitForLoadState('networkidle');
        const is500 = await page.getByText(/500|Internal Server Error/i).isVisible().catch(() => false);
        expect(is500).toBeFalsy();
    });
});

test.describe('HR – uprawnienia ról', () => {
    test('E27.7.1 Strona /hr/permissions nie zwraca 500', async ({ page }) => {
        const response = await page.goto('/hr/permissions');
        expect(response?.status()).not.toBe(500);
    });

    test('E27.7.2 Strona dostępna bez błędu serwera', async ({ page }) => {
        await page.goto('/hr/permissions');
        await page.waitForLoadState('networkidle');
        const is500 = await page.getByText(/500|Internal Server Error/i).isVisible().catch(() => false);
        expect(is500).toBeFalsy();
    });
});

test.describe('Profil użytkownika', () => {
    test('E27.8.1 Strona /profile ładuje się poprawnie', async ({ page }) => {
        await page.goto('/profile');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/profile/);
    });

    test('E27.8.2 Formularz profilu zawiera pola', async ({ page }) => {
        await page.goto('/profile');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('input[type="text"], input[type="email"]').first()).toBeVisible();
    });
});
