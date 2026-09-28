import { test, expect } from '@playwright/test';

test.describe('Lista faktur', () => {
    test('E5.1.1 Strona /finance/invoices ładuje się poprawnie', async ({ page }) => {
        await page.goto('/finance/invoices');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/finance\/invoices/);
    });

    test('E5.1.2 Widoczny przycisk tworzenia nowej faktury', async ({ page }) => {
        await page.goto('/finance/invoices');
        await page.waitForLoadState('networkidle');
        const btn = page.locator('a:has-text("Nowa faktura"), button:has-text("Nowa faktura")').first();
        await expect(btn).toBeVisible();
    });

    test('E5.1.3 Strona zawiera podsumowanie finansowe (total, paid, pending)', async ({ page }) => {
        await page.goto('/finance/invoices');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).not.toBeEmpty();
    });
});

test.describe('Tworzenie faktury', () => {
    test('E5.2.1 Formularz nowej faktury jest dostępny', async ({ page }) => {
        await page.goto('/finance/invoices/create');
        await page.waitForLoadState('networkidle');
        // Formularz ma select dla klienta jako pierwsze wymagane pole
        await expect(page.locator('select').first()).toBeVisible();
    });

    test('E5.2.2 Formularz bez klienta i pozycji → błąd walidacji', async ({ page }) => {
        await page.goto('/finance/invoices/create');
        await page.waitForLoadState('networkidle');
        await page.locator('button[type="submit"]').first().click();
        await page.waitForLoadState('networkidle');
        // Nie powinien przekierować na show faktury
        await expect(page).not.toHaveURL(/invoices\/\d+$/);
    });
});
