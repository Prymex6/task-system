import { Page } from '@playwright/test'

/**
 * Accounts created by `php artisan e2e:setup`.
 *
 * The landlord signs in on the central domain and the other two on the tenant
 * domain, which is why each helper below navigates before it fills anything:
 * the same path on the wrong host is a different application.
 */
export const CREDENTIALS = {
  manager: { email: 'admin@example.com', password: 'password' },
  client: { email: 'portal@acme.pl', password: 'password' },
  landlord: { email: 'admin@task-system.localhost', password: 'password' },
} as const

export async function loginManager(page: Page): Promise<void> {
  await page.goto('/login')
  await page.fill('input[type="email"]', CREDENTIALS.manager.email)
  await page.fill('input[type="password"]', CREDENTIALS.manager.password)
  await page.click('button[type="submit"]')
  await page.waitForURL(/\/dashboard/, { timeout: 15_000 })
}

export async function loginClient(page: Page): Promise<void> {
  await page.goto('/portal/logowanie')
  await page.fill('input[type="email"]', CREDENTIALS.client.email)
  await page.fill('input[type="password"]', CREDENTIALS.client.password)
  await page.click('button[type="submit"]')
  await page.waitForURL(/\/portal\/dashboard/, { timeout: 15_000 })
}

export async function loginLandlord(page: Page): Promise<void> {
  await page.goto('/admin/login')
  await page.fill('input[type="email"]', CREDENTIALS.landlord.email)
  await page.fill('input[type="password"]', CREDENTIALS.landlord.password)
  await page.click('button[type="submit"]')
  await page.waitForURL(/\/admin\/dashboard/, { timeout: 15_000 })
}

/**
 * Inertia swaps the page component without a document navigation, so the load
 * event has already fired by the time the new screen renders. Waiting for the
 * network to settle is what tells us the visit finished.
 */
export async function waitForInertia(page: Page): Promise<void> {
  await page.waitForLoadState('networkidle')
}
