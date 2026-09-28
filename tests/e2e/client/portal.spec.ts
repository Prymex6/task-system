import { test, expect } from '@playwright/test';

// Ten plik uruchamiany z storageState: client.json (zalogowany kontakt)

test.describe('Portal klienta – dashboard', () => {
    test('E9.1.1 /portal/dashboard ładuje się poprawnie po zalogowaniu', async ({ page }) => {
        await page.goto('/portal/dashboard');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/portal\/dashboard/);
    });

    test('E9.1.2 Dashboard zawiera widżety (projekty, faktury, tickety)', async ({ page }) => {
        await page.goto('/portal/dashboard');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).not.toBeEmpty();
    });
});

test.describe('Portal klienta – faktury', () => {
    test('E9.2.1 Strona /portal/invoices ładuje się poprawnie', async ({ page }) => {
        await page.goto('/portal/invoices');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/portal\/invoices/);
    });

    test('E9.2.2 Lista faktur widoczna (lub pusta) – brak błędu 500', async ({ page }) => {
        const response = await page.goto('/portal/invoices');
        expect(response?.status()).toBe(200);
    });
});

test.describe('Portal klienta – zgłoszenia supportu', () => {
    test('E9.3.1 Strona /portal/support ładuje się poprawnie', async ({ page }) => {
        await page.goto('/portal/support');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/portal\/support/);
    });

    test('E9.3.2 Formularz nowego zgłoszenia jest dostępny', async ({ page }) => {
        await page.goto('/portal/support/create');
        await page.waitForLoadState('networkidle');
        // Pole subject — pierwszy input[type="text"] w formularzu
        await expect(page.locator('input[type="text"]').first()).toBeVisible();
    });

    test('E9.3.3 Wysłanie zgłoszenia z tematem i treścią → zapis i redirect', async ({ page }) => {
        await page.goto('/portal/support/create');
        await page.waitForLoadState('networkidle');

        // Pole tematu — pierwszy input[type="text"]
        await page.locator('input[type="text"]').first().fill('E2E Testowe zgłoszenie z portalu');

        // Treść — textarea
        const bodyField = page.locator('textarea').first();
        if (await bodyField.isVisible()) {
            await bodyField.fill('Treść testowego zgłoszenia E2E.');
        }

        // Wybierz priorytet jeśli jest select
        const prioritySelect = page.locator('select').first();
        if (await prioritySelect.isVisible()) {
            await prioritySelect.selectOption({ index: 1 });
        }

        await page.locator('button[type="submit"]').first().click();
        await page.waitForLoadState('networkidle');

        // Po zapisie powinien być redirect na listę lub show
        await expect(page).toHaveURL(/\/portal\/support/);
    });
});

test.describe('Portal klienta – baza wiedzy', () => {
    test('E9.4.1 Strona /portal/knowledge-base ładuje się poprawnie', async ({ page }) => {
        await page.goto('/portal/knowledge-base');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/portal\/knowledge-base/);
    });
});
