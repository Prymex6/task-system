import { Page } from '@playwright/test';

export const CREDENTIALS = {
    /** Admin (pełne uprawnienia) – główne konto dla testów managera */
    manager:  { email: 'admin@example.com',          password: 'password' },
    /** Kontakt klienta – portal klienta Acme Corp */
    client:   { email: 'portal@acme.pl',              password: 'password' },
    /** Super admin – panel landlorda */
    landlord: { email: 'admin@task-system.localhost', password: 'password' },
} as const;

/** Loguje do panelu managera, czeka na przekierowanie do /dashboard */
export async function loginManager(page: Page): Promise<void> {
    await page.goto('/login');
    await page.waitForLoadState('networkidle');
    await page.fill('input[type="email"]', CREDENTIALS.manager.email);
    await page.fill('input[type="password"]', CREDENTIALS.manager.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/\/dashboard/, { timeout: 15_000 });
}

/** Loguje do portalu klienta, czeka na przekierowanie do /portal/dashboard */
export async function loginClient(page: Page): Promise<void> {
    await page.goto('/portal/logowanie');
    await page.waitForLoadState('networkidle');
    await page.fill('input[type="email"]', CREDENTIALS.client.email);
    await page.fill('input[type="password"]', CREDENTIALS.client.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/\/portal\/dashboard/, { timeout: 15_000 });
}

/** Loguje do panelu landlorda, czeka na przekierowanie do /admin/dashboard */
export async function loginLandlord(page: Page): Promise<void> {
    await page.goto('/admin/login');
    await page.waitForLoadState('networkidle');
    await page.fill('input[type="email"]', CREDENTIALS.landlord.email);
    await page.fill('input[type="password"]', CREDENTIALS.landlord.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/\/admin\/dashboard/, { timeout: 15_000 });
}

/** Czeka aż Inertia zakończy nawigację */
export async function waitForInertia(page: Page): Promise<void> {
    await page.waitForLoadState('networkidle');
}
