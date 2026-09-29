import { defineConfig, devices } from '@playwright/test'

/**
 * End-to-end coverage for the two panels and the client portal.
 *
 * The suite talks to a real tenant over HTTP rather than to the framework, so
 * it needs a running server and a provisioned workspace:
 *
 *   php artisan e2e:setup     creates the tenant, its database and its accounts
 *   php artisan serve         or any host answering on port 8000
 *
 * The tenant is resolved from the domain, which is why the two base URLs differ
 * by host and not by path: ecommerce.localhost is a workspace, localhost is the
 * platform. *.localhost resolves without touching the hosts file.
 */

export const TENANT_URL = process.env.TENANT_URL ?? 'http://ecommerce.localhost:8000'
export const LANDLORD_URL = process.env.LANDLORD_URL ?? 'http://localhost:8000'

export default defineConfig({
  testDir: './tests/e2e',
  outputDir: './tests/e2e/.output',

  // Every project writes to the same tenant database, so a parallel run would
  // have one spec deleting the record another is reading.
  fullyParallel: false,
  workers: 1,

  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  reporter: [['list'], ['html', { outputFolder: 'tests/e2e/.report', open: 'never' }]],

  use: {
    baseURL: TENANT_URL,
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    actionTimeout: 15_000,
    navigationTimeout: 30_000,
  },

  projects: [
    {
      name: 'tenant-setup',
      testMatch: '**/setup/tenant-auth.setup.ts',
      use: { ...devices['Desktop Chrome'], baseURL: TENANT_URL },
    },
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
    {
      // Deliberately signed out: these assert what a guest cannot reach.
      name: 'security',
      testMatch: '**/security/**/*.spec.ts',
      use: {
        ...devices['Desktop Chrome'],
        baseURL: TENANT_URL,
        storageState: { cookies: [], origins: [] },
      },
      // Needs the saved manager session to prove the portal rejects it.
      dependencies: ['tenant-setup'],
    },
    {
      name: 'landlord-setup',
      testMatch: '**/setup/landlord-auth.setup.ts',
      use: { ...devices['Desktop Chrome'], baseURL: LANDLORD_URL },
    },
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
})
