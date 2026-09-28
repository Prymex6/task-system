import { test, expect } from '@playwright/test';

test.describe('Lista kontraktów', () => {
    test('E13.1.1 Strona /contracts ładuje się poprawnie', async ({ page }) => {
        await page.goto('/contracts');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/contracts/);
    });

    test('E13.1.2 Strona zwraca status 200', async ({ page }) => {
        const response = await page.goto('/contracts');
        expect(response?.status()).toBe(200);
    });

    test('E13.1.3 Widoczny przycisk tworzenia nowej umowy', async ({ page }) => {
        await page.goto('/contracts');
        await page.waitForLoadState('networkidle');
        // Przycisk: "Nowa umowa" (Link, nie button)
        const btn = page.locator('a:has-text("Nowa umowa"), button:has-text("Nowa umowa")').first();
        await expect(btn).toBeVisible();
    });
});

test.describe('Tworzenie kontraktu', () => {
    test('E13.2.1 Formularz nowego kontraktu jest dostępny', async ({ page }) => {
        await page.goto('/contracts/create');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('input[type="text"], select').first()).toBeVisible();
    });

    test('E13.2.2 Pusty formularz → brak zapisu kontraktu', async ({ page }) => {
        await page.goto('/contracts/create');
        await page.waitForLoadState('networkidle');
        await page.locator('button[type="submit"]').first().click();
        await page.waitForLoadState('networkidle');
        await expect(page).not.toHaveURL(/contracts\/\d+$/);
    });

    test('E13.2.3 Wypełniony tytuł → kontrakt zapisany', async ({ page }) => {
        await page.goto('/contracts/create');
        await page.waitForLoadState('networkidle');
        await page.locator('input[type="text"]').first().fill('E2E Kontrakt Testowy');
        const clientSelect = page.locator('select').first();
        if (await clientSelect.isVisible()) {
            await clientSelect.selectOption({ index: 1 });
        }
        await page.locator('button[type="submit"]').first().click();
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/contracts/);
    });
});
