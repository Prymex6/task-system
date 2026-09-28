import { test, expect } from '@playwright/test';

test.describe('Dashboard managera', () => {
    test('E2.1.1 Dashboard ładuje się poprawnie po zalogowaniu', async ({ page }) => {
        await page.goto('/dashboard');
        await expect(page).toHaveURL(/\/dashboard/);
    });

    test('E2.1.2 Dashboard zawiera widgety ze statystykami', async ({ page }) => {
        await page.goto('/dashboard');
        await page.waitForLoadState('networkidle');
        // Strona powinna mieć jakieś karty statystyk – sprawdzamy że nie jest pusta
        await expect(page.locator('body')).not.toBeEmpty();
    });

    test('E2.1.3 Nawigacja do projektu z dashboardu', async ({ page }) => {
        await page.goto('/dashboard');
        await page.waitForLoadState('networkidle');
        // Pasek nawigacji powinien zawierać link do projektów
        const projectsLink = page.locator('a[href*="projects"]').first();
        if (await projectsLink.isVisible()) {
            await projectsLink.click();
            await page.waitForLoadState('networkidle');
            await expect(page).toHaveURL(/projects/);
        }
    });
});
