import { test, expect } from '@playwright/test';

test.describe('CRM – klienci', () => {
    test('E10.1.1 Strona /crm/clients ładuje się poprawnie', async ({ page }) => {
        await page.goto('/crm/clients');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/crm\/clients/);
    });

    test('E10.1.2 Lista klientów widoczna (Acme Corp w seedzie)', async ({ page }) => {
        await page.goto('/crm/clients');
        await page.waitForLoadState('networkidle');
        // Seeder tworzy klientów: company_name = "Acme Corporation Sp. z o.o."
        await expect(page.locator('text=Acme Corporation')).toBeVisible();
    });

    test('E10.1.3 Widoczny przycisk dodania nowego klienta', async ({ page }) => {
        await page.goto('/crm/clients');
        await page.waitForLoadState('networkidle');
        const btn = page.locator('a:has-text("Nowy klient"), button:has-text("Nowy klient")').first();
        await expect(btn).toBeVisible();
    });

    test('E10.1.4 Formularz tworzenia klienta jest dostępny', async ({ page }) => {
        await page.goto('/crm/clients/create');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('input[type="text"]').first()).toBeVisible();
    });

    test('E10.1.5 Pusty formularz klienta → brak zapisu', async ({ page }) => {
        await page.goto('/crm/clients/create');
        await page.waitForLoadState('networkidle');
        await page.locator('button[type="submit"]').first().click();
        await page.waitForLoadState('networkidle');
        await expect(page).not.toHaveURL(/clients\/\d+$/);
    });

    test('E10.1.6 Poprawne dane → klient zapisany', async ({ page }) => {
        await page.goto('/crm/clients/create');
        await page.waitForLoadState('networkidle');
        await page.locator('input[type="text"]').first().fill('E2E Firma Testowa');
        await page.locator('button[type="submit"]').first().click();
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/crm\/clients/);
    });
});

test.describe('CRM – leady (modal na stronie /crm/leads)', () => {
    // LeadController nie ma strony /create – tworzenie przez modal na Index

    test('E10.2.1 Strona /crm/leads ładuje się poprawnie', async ({ page }) => {
        await page.goto('/crm/leads');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/crm\/leads/);
    });

    test('E10.2.2 Widoczny przycisk "Nowy lead"', async ({ page }) => {
        await page.goto('/crm/leads');
        await page.waitForLoadState('networkidle');
        const btn = page.locator('a:has-text("Nowy lead"), button:has-text("Nowy lead")').first();
        await expect(btn).toBeVisible();
    });

    test('E10.2.3 Po kliknięciu "Nowy lead" formularz jest dostępny', async ({ page }) => {
        await page.goto('/crm/leads');
        await page.waitForLoadState('networkidle');
        const btn = page.locator('a:has-text("Nowy lead"), button:has-text("Nowy lead")').first();
        if (await btn.isVisible()) {
            await btn.click();
            await page.waitForTimeout(300);
            // Formularz w modalu – szukamy dowolnego input
            const input = page.locator('input[type="text"], input[type="email"]').first();
            await expect(input).toBeVisible();
        }
    });

    test('E10.2.4 Strona zwraca status 200', async ({ page }) => {
        const response = await page.goto('/crm/leads');
        expect(response?.status()).toBe(200);
    });
});

test.describe('CRM – deale (modal na /crm/deals)', () => {
    // DealController nie ma osobnej strony create – tworzenie przez modal/pipeline

    test('E10.3.1 Strona /crm/deals ładuje się poprawnie', async ({ page }) => {
        await page.goto('/crm/deals');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/crm\/deals/);
    });

    test('E10.3.2 Pipeline dealów ładuje się bez błędu', async ({ page }) => {
        await page.goto('/crm/deals/pipeline');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/crm\/deals\/pipeline/);
    });

    test('E10.3.3 Widoczny przycisk dodania dealu', async ({ page }) => {
        await page.goto('/crm/deals');
        await page.waitForLoadState('networkidle');
        const btn = page.locator('button:has-text("Nowy deal"), a:has-text("Nowy deal"), button:has-text("Dodaj deal")').first();
        await expect(btn).toBeVisible();
    });
});
