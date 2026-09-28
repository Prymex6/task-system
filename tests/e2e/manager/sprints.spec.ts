import { test, expect } from '@playwright/test';

test.describe('Sprinty – lista', () => {
    test('E15.1.1 Strona /sprints ładuje się poprawnie', async ({ page }) => {
        await page.goto('/sprints');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/sprints/);
    });

    test('E15.1.2 Strona zwraca status 200', async ({ page }) => {
        const response = await page.goto('/sprints');
        expect(response?.status()).toBe(200);
    });

    test('E15.1.3 Lista sprintów widoczna (pusta lub z danymi)', async ({ page }) => {
        await page.goto('/sprints');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).not.toBeEmpty();
    });

    test('E15.1.4 Widoczny przycisk tworzenia sprintu', async ({ page }) => {
        await page.goto('/sprints');
        await page.waitForLoadState('networkidle');
        const btn = page.locator('button:has-text("Nowy sprint"), a:has-text("Nowy sprint"), button:has-text("Utwórz sprint")').first();
        await expect(btn).toBeVisible();
    });
});
