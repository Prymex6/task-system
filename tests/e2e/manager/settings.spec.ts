import { test, expect } from '@playwright/test';

test.describe('Ustawienia workspace', () => {
    test('E20.1.1 Strona /settings ładuje się poprawnie', async ({ page }) => {
        await page.goto('/settings');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/settings/);
    });

    test('E20.1.2 Strona zwraca status 200', async ({ page }) => {
        const response = await page.goto('/settings');
        expect(response?.status()).toBe(200);
    });

    test('E20.1.3 Formularz ustawień jest widoczny', async ({ page }) => {
        await page.goto('/settings');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('input[type="text"], input[type="email"]').first()).toBeVisible();
    });

    test('E20.1.4 Przycisk zapisu ustawień jest widoczny', async ({ page }) => {
        await page.goto('/settings');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('button[type="submit"]').first()).toBeVisible();
    });
});

test.describe('Ustawienia – firmy', () => {
    test('E20.2.1 Strona /settings/company ładuje się poprawnie', async ({ page }) => {
        await page.goto('/settings/company');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/settings/);
    });
});

test.describe('Ustawienia – finanse', () => {
    test('E20.3.1 Strona /settings/finance ładuje się poprawnie', async ({ page }) => {
        await page.goto('/settings/finance');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/settings/);
    });
});

test.describe('Automatyzacje', () => {
    test('E20.4.1 Strona /automations ładuje się poprawnie', async ({ page }) => {
        await page.goto('/automations');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/automations/);
    });

    test('E20.4.2 Lista automatyzacji widoczna bez błędu', async ({ page }) => {
        const response = await page.goto('/automations');
        expect(response?.status()).toBe(200);
    });
});
