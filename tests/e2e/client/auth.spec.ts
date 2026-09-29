import { test, expect } from '@playwright/test'
import { CREDENTIALS } from '../helpers/auth'

test.use({ storageState: { cookies: [], origins: [] } })

// The portal signs in at /portal/logowanie rather than an English path: the
// route is part of the customer-facing URL space, which is Polish by default.
test.describe('Client portal sign-in', () => {
  test('the sign-in page offers an email, a password and a submit button', async ({ page }) => {
    await page.goto('/portal/logowanie')
    await expect(page.locator('input[type="email"]')).toBeVisible()
    await expect(page.locator('input[type="password"]')).toBeVisible()
    await expect(page.locator('button[type="submit"]')).toBeVisible()
  })

  test('valid credentials land on the portal dashboard', async ({ page }) => {
    await page.goto('/portal/logowanie')
    await page.fill('input[type="email"]', CREDENTIALS.client.email)
    await page.fill('input[type="password"]', CREDENTIALS.client.password)
    await page.click('button[type="submit"]')
    await page.waitForURL(/\/portal\/dashboard/, { timeout: 15_000 })
  })

  test('a wrong password keeps the visitor on the sign-in page', async ({ page }) => {
    await page.goto('/portal/logowanie')
    await page.fill('input[type="email"]', CREDENTIALS.client.email)
    await page.fill('input[type="password"]', 'not-the-password')
    await page.click('button[type="submit"]')
    await page.waitForLoadState('networkidle')
    await expect(page).toHaveURL(/\/portal\/logowanie/)
  })

  test('the portal dashboard is unreachable without a session', async ({ page }) => {
    await page.goto('/portal/dashboard')
    await page.waitForLoadState('networkidle')
    await expect(page).toHaveURL(/\/portal\/logowanie/)
  })
})
