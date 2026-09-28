import { defineConfig, devices } from '@playwright/test';

/**
 * Task System SaaS – Playwright E2E Configuration
 *
 * Tenant URL: http://ecommerce.localhost:8000
 *
 * Prerequisities:
 *   1. Add to Windows hosts:  127.0.0.1  ecommerce.localhost
 *   2. Run:  php artisan e2e:setup
 *   3. Run:  php artisan serve  (or have XAMPP running on port 8000)
 *
 * Konta testowe (tworzone przez e2e:setup):
 *   Manager:  admin@example.com / password
 *   Client:   portal@acme.pl    / password
 */

export const TENANT_URL   = process.env.TENANT_URL   ?? 'http://ecommerce.localhost:8000';
export const LANDLORD_URL = process.env.LANDLORD_URL ?? 'http://localhost:8000';

export default defineConfig({
    testDir: './tests/e2e',
    outputDir: './tests/e2e/.output',
    fullyParallel: false,
    forbidOnly: !!process.env.CI,
    retries: process.env.CI ? 1 : 0,
    workers: 1,
    reporter: [
        ['list'],
        ['html', { outputFolder: 'tests/e2e/.report', open: 'never' }],
        ['./tests/e2e/reporters/plan-reporter.ts'],
    ],
    use: {
        baseURL: TENANT_URL,
        trace: 'on-first-retry',
        screenshot: 'only-on-failure',
        video: 'retain-on-failure',
        actionTimeout: 15_000,
        navigationTimeout: 30_000,
        waitForLoadState: 'networkidle',
    },
    projects: [
        // ── Setup ───────────────────────────────────────────────────────────────
        {
            name: 'tenant-setup',
            testMatch: '**/setup/tenant-auth.setup.ts',
            use: { ...devices['Desktop Chrome'], baseURL: TENANT_URL },
        },

        // ── Manager (panel pracownika) ──────────────────────────────────────────
        {
            name: 'manager',
            testMatch: '**/manager/**/*.spec.ts',
            use: {
                ...devices['Desktop Chrome'],
                baseURL: TENANT_URL,
                storageState: 'tests/e2e/.auth/manager.json',
            },
            dependencies: ['tenant-setup'],
        },

        // ── Client (portal klienta) ─────────────────────────────────────────────
        {
            name: 'client',
            testMatch: '**/client/**/*.spec.ts',
            use: {
                ...devices['Desktop Chrome'],
                baseURL: TENANT_URL,
                storageState: 'tests/e2e/.auth/client.json',
            },
            dependencies: ['tenant-setup'],
        },

        // ── Security ────────────────────────────────────────────────────────────
        {
            name: 'security',
            testMatch: '**/security/**/*.spec.ts',
            use: {
                ...devices['Desktop Chrome'],
                baseURL: TENANT_URL,
                storageState: { cookies: [], origins: [] },
            },
        },

        // ── Landlord setup ──────────────────────────────────────────────────────
        {
            name: 'landlord-setup',
            testMatch: '**/setup/landlord-auth.setup.ts',
            use: { ...devices['Desktop Chrome'], baseURL: LANDLORD_URL },
        },

        // ── Landlord (super admin panel) ────────────────────────────────────────
        {
            name: 'landlord',
            testMatch: '**/landlord/**/*.spec.ts',
            use: {
                ...devices['Desktop Chrome'],
                baseURL: LANDLORD_URL,
                storageState: 'tests/e2e/.auth/landlord.json',
            },
            dependencies: ['landlord-setup'],
        },
    ],
    globalSetup: './tests/e2e/global-setup.ts',
});
