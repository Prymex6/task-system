import { test, expect } from '@playwright/test';

test.describe('Lista wycen', () => {
    test('E11.1.1 Strona /finance/estimates ładuje się poprawnie', async ({ page }) => {
        await page.goto('/finance/estimates');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/finance\/estimates/);
    });

    test('E11.1.2 Widoczny przycisk tworzenia nowej wyceny', async ({ page }) => {
        await page.goto('/finance/estimates');
        await page.waitForLoadState('networkidle');
        const btn = page.locator('a:has-text("Nowa wycena"), button:has-text("Nowa wycena")').first();
        await expect(btn).toBeVisible();
    });

    test('E11.1.3 Lista wycen ładuje się bez błędu 500', async ({ page }) => {
        const response = await page.goto('/finance/estimates');
        expect(response?.status()).toBe(200);
    });
});

test.describe('Tworzenie wyceny', () => {
    test('E11.2.1 Formularz nowej wyceny jest dostępny', async ({ page }) => {
        await page.goto('/finance/estimates/create');
        await page.waitForLoadState('networkidle');
        // Formularz zaczyna się od selecta klienta
        await expect(page.locator('select').first()).toBeVisible();
    });

    test('E11.2.2 Pusty formularz → brak przekierowania na show', async ({ page }) => {
        await page.goto('/finance/estimates/create');
        await page.waitForLoadState('networkidle');
        await page.locator('button[type="submit"]').first().click();
        await page.waitForLoadState('networkidle');
        await expect(page).not.toHaveURL(/estimates\/\d+$/);
    });
});
