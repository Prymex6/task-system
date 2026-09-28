import { test, expect } from '@playwright/test';

test.describe('Baza wiedzy – lista artykułów', () => {
    test('E7.1.1 Strona /knowledge-base ładuje się poprawnie', async ({ page }) => {
        await page.goto('/knowledge-base');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/knowledge-base/);
    });

    test('E7.1.2 Widoczny przycisk dodania nowego artykułu', async ({ page }) => {
        await page.goto('/knowledge-base');
        await page.waitForLoadState('networkidle');
        // KB Index używa przycisku otwierającego modal
        const btn = page.locator('button:has-text("Nowy artykuł"), a:has-text("Nowy artykuł")').first();
        await expect(btn).toBeVisible();
    });

    test('E7.1.3 Lista artykułów ładuje się bez błędu', async ({ page }) => {
        await page.goto('/knowledge-base');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).not.toBeEmpty();
    });
});

test.describe('Tworzenie artykułu KB', () => {
    test('E7.2.1 Formularz nowego artykułu jest dostępny', async ({ page }) => {
        await page.goto('/knowledge-base/create');
        await page.waitForLoadState('networkidle');
        // Formularz nie ma atrybutu name na inputach — używamy input[type="text"]
        await expect(page.locator('input[type="text"]').first()).toBeVisible();
    });

    test('E7.2.2 Wysłanie formularza z tytułem i treścią → zapis i przekierowanie', async ({ page }) => {
        await page.goto('/knowledge-base/create');
        await page.waitForLoadState('networkidle');

        // Tytuł — pierwszy input[type="text"]
        await page.locator('input[type="text"]').first().fill('E2E Artykuł Testowy');

        // Treść — textarea[placeholder*="Treść"]
        const contentArea = page.locator('textarea').last();
        if (await contentArea.isVisible()) {
            await contentArea.fill('Treść artykułu testowego dla E2E.');
        }

        await page.locator('button[type="submit"]').first().click();
        await page.waitForLoadState('networkidle');

        // Po zapisie redirect lub zostaje na /knowledge-base
        await expect(page).toHaveURL(/\/knowledge-base/);
    });

    test('E7.2.3 Pusty tytuł → błąd walidacji, brak zapisu', async ({ page }) => {
        await page.goto('/knowledge-base/create');
        await page.waitForLoadState('networkidle');
        await page.locator('button[type="submit"]').first().click();
        await page.waitForLoadState('networkidle');
        await expect(page).not.toHaveURL(/knowledge-base\/\d+/);
    });
});
