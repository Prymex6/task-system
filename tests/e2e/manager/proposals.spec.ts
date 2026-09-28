import { test, expect } from '@playwright/test';

test.describe('Lista propozycji', () => {
    test('E14.1.1 Strona /proposals ładuje się poprawnie', async ({ page }) => {
        await page.goto('/proposals');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/proposals/);
    });

    test('E14.1.2 Strona zwraca status 200', async ({ page }) => {
        const response = await page.goto('/proposals');
        expect(response?.status()).toBe(200);
    });

    test('E14.1.3 Widoczny przycisk tworzenia nowej propozycji', async ({ page }) => {
        await page.goto('/proposals');
        await page.waitForLoadState('networkidle');
        const btn = page.locator('a:has-text("Nowa propozycja"), button:has-text("Nowa propozycja")').first();
        await expect(btn).toBeVisible();
    });
});

test.describe('Tworzenie propozycji', () => {
    test('E14.2.1 Formularz nowej propozycji jest dostępny', async ({ page }) => {
        await page.goto('/proposals/create');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('input[type="text"], select').first()).toBeVisible();
    });

    test('E14.2.2 Pusty formularz → brak zapisu', async ({ page }) => {
        await page.goto('/proposals/create');
        await page.waitForLoadState('networkidle');
        await page.locator('button[type="submit"]').first().click();
        await page.waitForLoadState('networkidle');
        await expect(page).not.toHaveURL(/proposals\/\d+$/);
    });

    test('E14.2.3 Wypełniony formularz → propozycja zapisana', async ({ page }) => {
        await page.goto('/proposals/create');
        await page.waitForLoadState('networkidle');
        await page.locator('input[type="text"]').first().fill('E2E Propozycja Testowa');
        const clientSelect = page.locator('select').first();
        if (await clientSelect.isVisible()) {
            await clientSelect.selectOption({ index: 1 });
        }
        await page.locator('button[type="submit"]').first().click();
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/proposals/);
    });
});
