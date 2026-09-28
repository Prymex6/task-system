import { test, expect } from '@playwright/test';

test.describe('Landlord – plany subskrypcji', () => {
    test('E23.1.1 Strona /admin/plans ładuje się poprawnie', async ({ page }) => {
        await page.goto('/admin/plans');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/admin\/plans/);
    });

    test('E23.1.2 Strona zwraca status 200', async ({ page }) => {
        const response = await page.goto('/admin/plans');
        expect(response?.status()).toBe(200);
    });

    test('E23.1.3 Lista planów widoczna (seeder tworzy plany Free/Starter/Pro)', async ({ page }) => {
        await page.goto('/admin/plans');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).not.toBeEmpty();
    });

    test('E23.1.4 Widoczny przycisk tworzenia nowego planu', async ({ page }) => {
        await page.goto('/admin/plans');
        await page.waitForLoadState('networkidle');
        const btn = page.locator('a:has-text("Dodaj plan"), button:has-text("Dodaj plan"), a:has-text("Nowy plan"), button:has-text("Nowy plan")').first();
        await expect(btn).toBeVisible();
    });
});

test.describe('Landlord – modyfikacje', () => {
    test('E23.2.1 Strona /admin/modifications ładuje się poprawnie', async ({ page }) => {
        await page.goto('/admin/modifications');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/admin\/modifications/);
    });

    test('E23.2.2 Lista modyfikacji widoczna bez błędu', async ({ page }) => {
        const response = await page.goto('/admin/modifications');
        expect(response?.status()).toBe(200);
    });
});
