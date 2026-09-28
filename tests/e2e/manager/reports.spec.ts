import { test, expect } from '@playwright/test';

// UWAGA: Kontrolery reports/* zwracają back() – "Feature coming soon."
// Testy sprawdzają że trasy istnieją i nie zwracają 500

test.describe('Raporty', () => {
    test('E19.1.1 /reports nie zwraca 500', async ({ page }) => {
        const response = await page.goto('/reports');
        expect(response?.status()).not.toBe(500);
    });

    test('E19.1.2 /reports/time nie zwraca 500', async ({ page }) => {
        const response = await page.goto('/reports/time');
        expect(response?.status()).not.toBe(500);
    });

    test('E19.1.3 /reports/finance nie zwraca 500', async ({ page }) => {
        const response = await page.goto('/reports/finance');
        expect(response?.status()).not.toBe(500);
    });

    test('E19.1.4 /reports/projects nie zwraca 500', async ({ page }) => {
        const response = await page.goto('/reports/projects');
        expect(response?.status()).not.toBe(500);
    });

    test('E19.1.5 /reports/staff nie zwraca 500', async ({ page }) => {
        const response = await page.goto('/reports/staff');
        expect(response?.status()).not.toBe(500);
    });

    test('E19.1.6 /reports/clients nie zwraca 500', async ({ page }) => {
        const response = await page.goto('/reports/clients');
        expect(response?.status()).not.toBe(500);
    });
});
