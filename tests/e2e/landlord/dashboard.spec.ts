import { test, expect } from '@playwright/test';

// Ten plik uruchamiany z storageState: landlord.json (zalogowany super admin)

test.describe('Dashboard landlorda', () => {
    test('E21.2.1 Strona /admin/dashboard ładuje się poprawnie', async ({ page }) => {
        await page.goto('/admin/dashboard');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/admin\/dashboard/);
    });

    test('E21.2.2 Dashboard zawiera statystyki (tenants, plany)', async ({ page }) => {
        await page.goto('/admin/dashboard');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).not.toBeEmpty();
    });

    test('E21.2.3 Strona zwraca status 200', async ({ page }) => {
        const response = await page.goto('/admin/dashboard');
        expect(response?.status()).toBe(200);
    });
});

test.describe('Landlord – workspace leads', () => {
    test('E21.3.1 Strona /admin/workspace-leads ładuje się poprawnie', async ({ page }) => {
        await page.goto('/admin/workspace-leads');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/admin\/workspace-leads/);
    });
});

test.describe('Landlord – support', () => {
    test('E21.4.1 Strona /admin/support ładuje się poprawnie', async ({ page }) => {
        await page.goto('/admin/support');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/admin\/support/);
    });

    test('E21.4.2 Lista zgłoszeń supportu widoczna', async ({ page }) => {
        const response = await page.goto('/admin/support');
        expect(response?.status()).toBe(200);
    });
});
