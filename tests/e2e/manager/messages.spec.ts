import { test, expect } from '@playwright/test';

test.describe('Wiadomości wewnętrzne', () => {
    test('E18.1.1 Strona /messages ładuje się poprawnie', async ({ page }) => {
        await page.goto('/messages');
        await page.waitForLoadState('networkidle');
        await expect(page).toHaveURL(/\/messages/);
    });

    test('E18.1.2 Strona zwraca status 200', async ({ page }) => {
        const response = await page.goto('/messages');
        expect(response?.status()).toBe(200);
    });

    test('E18.1.3 Panel wiadomości widoczny', async ({ page }) => {
        await page.goto('/messages');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).not.toBeEmpty();
    });

    test('E18.1.4 Widoczny przycisk nowej konwersacji', async ({ page }) => {
        await page.goto('/messages');
        await page.waitForLoadState('networkidle');
        const btn = page.locator('button:has-text("Nowa"), button:has-text("Konwersacja"), a:has-text("Nowa wiadomość")').first();
        await expect(btn).toBeVisible();
    });
});
