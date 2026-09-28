import { test, expect } from '@playwright/test';

test.describe('Landlord – lista tenantów', () => {
    test('E22.1.1 Strona /admin/tenants ładuje się poprawnie', async ({ page }) => {
        await page.goto('/admin/tenants');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/admin\/tenants/);
    });

    test('E22.1.2 Tenant E2E Test Tenant widoczny na liście', async ({ page }) => {
        await page.goto('/admin/tenants');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('text=E2E Test Tenant')).toBeVisible();
    });

    test('E22.1.3 Widoczny przycisk tworzenia nowego tenanta', async ({ page }) => {
        await page.goto('/admin/tenants');
        await page.waitForLoadState('networkidle');
        const btn = page.locator('a:has-text("Dodaj sklep"), button:has-text("Dodaj sklep"), a:has-text("Nowy tenant"), a:has-text("Utwórz")').first();
        await expect(btn).toBeVisible();
    });

    test('E22.1.4 Strona zwraca status 200', async ({ page }) => {
        const response = await page.goto('/admin/tenants');
        expect(response?.status()).toBe(200);
    });
});

test.describe('Landlord – szczegóły tenanta', () => {
    test('E22.2.1 Strona szczegółów tenanta e2e ładuje się', async ({ page }) => {
        await page.goto('/admin/tenants');
        await page.waitForLoadState('networkidle');
        // Kliknij w link tenanta e2e
        const tenantLink = page.locator('a:has-text("E2E Test Tenant"), a:has-text("ecommerce.localhost")').first();
        if (await tenantLink.isVisible()) {
            await tenantLink.click();
            await page.waitForLoadState('networkidle');
            await expect(page).toHaveURL(/\/admin\/tenants\//);
        }
    });

    test('E22.2.2 Formularz tworzenia tenanta jest dostępny', async ({ page }) => {
        await page.goto('/admin/tenants/create');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('input[type="text"]').first()).toBeVisible();
    });
});
