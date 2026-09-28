import { test, expect } from '@playwright/test';

test('debug page', async ({ page }) => {
    const errors: string[] = [];
    page.on('console', msg => { if (msg.type() === 'error') errors.push(msg.text()); });
    page.on('pageerror', err => errors.push('PAGE ERROR: ' + err.message));
    
    await page.goto('/finance/invoices');
    await page.waitForLoadState('networkidle');
    
    console.log('URL:', page.url());
    console.log('Title:', await page.title());
    console.log('Errors:', errors.join(' | '));
    console.log('h1 count:', await page.locator('h1').count());
    console.log('Body text:', (await page.locator('body').innerText()).substring(0, 300));
});
