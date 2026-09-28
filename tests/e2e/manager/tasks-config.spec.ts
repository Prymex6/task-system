import { test, expect } from '@playwright/test';

test.describe('Etykiety zadań', () => {
    test('E29.1.1 Strona /task-labels nie zwraca 500', async ({ page }) => {
        const response = await page.goto('/task-labels');
        expect(response?.status()).not.toBe(500);
    });

    test('E29.1.2 Strona dostępna bez błędu serwera', async ({ page }) => {
        await page.goto('/task-labels');
        await page.waitForLoadState('networkidle');
        const is500 = await page.getByText(/500|Internal Server Error/i).isVisible().catch(() => false);
        expect(is500).toBeFalsy();
    });
});

test.describe('Statusy zadań', () => {
    test('E29.2.1 Strona /task-statuses nie zwraca 500', async ({ page }) => {
        const response = await page.goto('/task-statuses');
        expect(response?.status()).not.toBe(500);
    });

    test('E29.2.2 Strona dostępna bez błędu serwera', async ({ page }) => {
        await page.goto('/task-statuses');
        await page.waitForLoadState('networkidle');
        const is500 = await page.getByText(/500|Internal Server Error/i).isVisible().catch(() => false);
        expect(is500).toBeFalsy();
    });
});

test.describe('Szablony projektów', () => {
    test('E29.3.1 Strona /project-templates nie zwraca 500', async ({ page }) => {
        const response = await page.goto('/project-templates');
        expect(response?.status()).not.toBe(500);
    });

    test('E29.3.2 Strona dostępna bez błędu serwera', async ({ page }) => {
        await page.goto('/project-templates');
        await page.waitForLoadState('networkidle');
        const is500 = await page.getByText(/500|Internal Server Error/i).isVisible().catch(() => false);
        expect(is500).toBeFalsy();
    });
});
