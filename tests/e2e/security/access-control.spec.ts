import { test, expect } from '@playwright/test';

// Ten plik uruchamiany BEZ storageState (gość – niezalogowany)

test.describe('Kontrola dostępu – panel managera', () => {
    test('E10.1.1 /dashboard bez sesji → redirect na /login', async ({ page }) => {
        await page.goto('/dashboard');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/login/);
    });

    test('E10.1.2 /projects bez sesji → redirect na /login', async ({ page }) => {
        await page.goto('/projects');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/login/);
    });

    test('E10.1.3 /finance/invoices bez sesji → redirect na /login', async ({ page }) => {
        await page.goto('/finance/invoices');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/login/);
    });

    test('E10.1.4 /support bez sesji → redirect na /login', async ({ page }) => {
        await page.goto('/support');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/login/);
    });

    test('E10.1.5 /knowledge-base bez sesji → redirect na /login', async ({ page }) => {
        await page.goto('/knowledge-base');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/login/);
    });
});

test.describe('Kontrola dostępu – portal klienta', () => {
    test('E10.2.1 /portal/dashboard bez sesji → redirect na /portal/logowanie', async ({ page }) => {
        await page.goto('/portal/dashboard');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/portal\/logowanie/);
    });

    test('E10.2.2 /portal/invoices bez sesji → redirect na /portal/logowanie', async ({ page }) => {
        await page.goto('/portal/invoices');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/portal\/logowanie/);
    });

    test('E10.2.3 /portal/support bez sesji → redirect na /portal/logowanie', async ({ page }) => {
        await page.goto('/portal/support');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/portal\/logowanie/);
    });
});

test.describe('Izolacja sesji', () => {
    test('E10.3.1 Sesja managera nie daje dostępu do portalu klienta (różne guardy)', async ({ page, context }) => {
        // Gość próbuje wejść do portalu → redirect
        await page.goto('/portal/dashboard');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/portal\/logowanie/);
    });
});
