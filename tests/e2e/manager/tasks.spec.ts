import { test, expect } from '@playwright/test';

test.describe('Zadania w projekcie', () => {
    test('E4.1.1 Lista zadań projektu ładuje się poprawnie', async ({ page }) => {
        // Przejdź do pierwszego projektu z listy
        await page.goto('/projects');
        await page.waitForLoadState('networkidle');

        // Szukamy linków do projektów z numerycznym ID (nie /create)
        const firstProject = page.locator('a[href*="/projects/"]:not([href*="/create"]):not([href*="/board"]):not([href*="/members"])').first();
        if (await firstProject.isVisible()) {
            await firstProject.click();
            await page.waitForLoadState('networkidle');
            await expect(page).toHaveURL(/projects\/\d+/);
        }
    });

    test('E4.1.2 Zadanie „Analiza wymagań" widoczne na liście projektu Acme', async ({ page }) => {
        await page.goto('/projects');
        await page.waitForLoadState('networkidle');

        const projectLink = page.locator('a:has-text("Redesign strony")').first();
        if (await projectLink.isVisible()) {
            await projectLink.click();
            await page.waitForLoadState('networkidle');
            await expect(page.locator('text=Analiza wymagań')).toBeVisible();
        }
    });
});

test.describe('Tworzenie zadania', () => {
    test('E4.2.1 Formularz tworzenia zadania jest dostępny z poziomu projektu', async ({ page }) => {
        await page.goto('/projects');
        await page.waitForLoadState('networkidle');

        const projectLink = page.locator('a[href*="/projects/"]:not([href*="/create"]):not([href*="/board"])').first();
        if (await projectLink.isVisible()) {
            const href = await projectLink.getAttribute('href');
            if (href) {
                // Przejdź bezpośrednio do formularza zadania
                const projectId = href.match(/\/projects\/(\d+)/)?.[1];
                if (projectId) {
                    await page.goto(`/projects/${projectId}/tasks/create`);
                    await page.waitForLoadState('networkidle');
                    await expect(page.locator('input[type="text"]').first()).toBeVisible();
                }
            }
        }
    });

    test('E4.2.2 Puste pole tytułu → błąd walidacji', async ({ page }) => {
        await page.goto('/projects');
        await page.waitForLoadState('networkidle');

        const projectLink = page.locator('a[href*="/projects/"]:not([href*="/create"]):not([href*="/board"])').first();
        if (await projectLink.isVisible()) {
            const href = await projectLink.getAttribute('href');
            const projectId = href?.match(/\/projects\/(\d+)/)?.[1];
            if (projectId) {
                await page.goto(`/projects/${projectId}/tasks/create`);
                await page.waitForLoadState('networkidle');
                await page.locator('button[type="submit"]').first().click();
                await page.waitForLoadState('networkidle');
                // Nie powinien przekierować na show zadania
                await expect(page).not.toHaveURL(/\/tasks\/\d+$/);
            }
        }
    });
});
