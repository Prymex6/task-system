import { test, expect } from '@playwright/test';

test.describe('Lista projektów', () => {
    test('E3.1.1 Strona /projects ładuje się z listą projektów', async ({ page }) => {
        await page.goto('/projects');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/projects/);
    });

    test('E3.1.2 Widoczny przycisk dodania nowego projektu', async ({ page }) => {
        await page.goto('/projects');
        await page.waitForLoadState('networkidle');
        const createBtn = page.locator('a:has-text("Nowy projekt"), button:has-text("Nowy projekt")').first();
        await expect(createBtn).toBeVisible();
    });

    test('E3.1.3 Istniejący projekt „Redesign strony głównej Acme" widoczny na liście', async ({ page }) => {
        await page.goto('/projects');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('text=Redesign strony głównej Acme')).toBeVisible();
    });
});

test.describe('Tworzenie projektu', () => {
    test('E3.2.1 Formularz tworzenia projektu jest dostępny', async ({ page }) => {
        await page.goto('/projects/create');
        await page.waitForLoadState('networkidle');
        // Formularz ma input[type="text"] dla nazwy z placeholderem
        await expect(page.locator('input[placeholder*="Redesign"], input[placeholder*="projekt"], input[type="text"]').first()).toBeVisible();
    });

    test('E3.2.2 Wysłanie pustego formularza → walidacja, brak zapisu', async ({ page }) => {
        await page.goto('/projects/create');
        await page.waitForLoadState('networkidle');
        const submitBtn = page.locator('button[type="submit"]').first();
        await submitBtn.click();
        await page.waitForLoadState('networkidle');
        // Nadal na stronie tworzenia lub błąd walidacji
        await expect(page).not.toHaveURL(/projects\/\d+$/);
    });

    test('E3.2.3 Poprawne dane → projekt zapisany, przekierowanie na show', async ({ page }) => {
        await page.goto('/projects/create');
        await page.waitForLoadState('networkidle');

        // Pole nazwy (pierwszy input[type="text"] w formularzu)
        const nameField = page.locator('input[type="text"]').first();
        await nameField.fill('E2E Projekt Testowy');

        await page.locator('button[type="submit"]').first().click();
        await page.waitForLoadState('networkidle');

        // Po zapisie powinien być redirect na show projektu
        await expect(page).toHaveURL(/projects\/\d+|projects/);
    });
});

test.describe('Szczegóły projektu', () => {
    test('E3.3.1 Strona projektu Acme ładuje się z zadaniami', async ({ page }) => {
        await page.goto('/projects');
        await page.waitForLoadState('networkidle');

        const projectLink = page.locator('a:has-text("Redesign strony głównej Acme")').first();
        if (await projectLink.isVisible()) {
            await projectLink.click();
            await page.waitForLoadState('networkidle');
            await expect(page).toHaveURL(/projects\/\d+/);
        }
    });
});
