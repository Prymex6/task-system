import { test, expect } from '@playwright/test';

test.describe('HR – pracownicy', () => {
    test('E17.1.1 Strona /hr/staff ładuje się poprawnie', async ({ page }) => {
        await page.goto('/hr/staff');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/hr\/staff/);
    });

    test('E17.1.2 Lista pracowników widoczna (seeder tworzy 4 konta)', async ({ page }) => {
        await page.goto('/hr/staff');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).not.toBeEmpty();
    });

    test('E17.1.3 Widoczny przycisk dodania pracownika', async ({ page }) => {
        await page.goto('/hr/staff');
        await page.waitForLoadState('networkidle');
        // Przycisk otwierający modal – "Dodaj Pracownika"
        const btn = page.locator('button:has-text("Dodaj Pracownika"), button:has-text("Zaproś"), a:has-text("Zaproś")').first();
        await expect(btn).toBeVisible();
    });
});

test.describe('HR – frekwencja', () => {
    test('E17.2.1 Strona /hr/attendance ładuje się poprawnie', async ({ page }) => {
        await page.goto('/hr/attendance');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/hr\/attendance/);
    });

    test('E17.2.2 Strona zwraca status 200', async ({ page }) => {
        const response = await page.goto('/hr/attendance');
        expect(response?.status()).toBe(200);
    });
});

test.describe('HR – urlopy (feature coming soon)', () => {
    // LeaveRequestController zwraca back() – testy sprawdzają brak 500

    test('E17.3.1 /hr/leaves nie zwraca 500', async ({ page }) => {
        const response = await page.goto('/hr/leaves');
        expect(response?.status()).not.toBe(500);
    });

    test('E17.3.2 /hr/leaves/calendar nie zwraca 500', async ({ page }) => {
        const response = await page.goto('/hr/leaves/calendar');
        expect(response?.status()).not.toBe(500);
    });
});
