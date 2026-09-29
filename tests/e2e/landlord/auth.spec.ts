import { test, expect } from '@playwright/test'
import { CREDENTIALS } from '../helpers/auth'

// Signed out on purpose: this file exercises the sign-in itself, so it must not
// inherit the saved landlord session the rest of the project runs with.
test.use({ storageState: { cookies: [], origins: [] } })

test.describe('Landlord sign-in', () => {
  test('the sign-in page offers an email, a password and a submit button', async ({ page }) => {
    await page.goto('/admin/login')
    await expect(page.locator('input[type="email"]')).toBeVisible()
    await expect(page.locator('input[type="password"]')).toBeVisible()
    await expect(page.locator('button[type="submit"]')).toBeVisible()
  })

  test('valid credentials land on the dashboard', async ({ page }) => {
    await page.goto('/admin/login')
    await page.fill('input[type="email"]', CREDENTIALS.landlord.email)
    await page.fill('input[type="password"]', CREDENTIALS.landlord.password)
    await page.click('button[type="submit"]')
    await page.waitForURL(/\/admin\/dashboard/, { timeout: 15_000 })
  })

  test('a wrong password does not get in', async ({ page }) => {
    await page.goto('/admin/login')
    await page.fill('input[type="email"]', CREDENTIALS.landlord.email)
    await page.fill('input[type="password"]', 'not-the-password')
    await page.click('button[type="submit"]')
    await page.waitForLoadState('networkidle')
    await expect(page).not.toHaveURL(/\/admin\/dashboard/)
  })

  test('the dashboard is unreachable without a session', async ({ page }) => {
    await page.goto('/admin/dashboard')
    await page.waitForLoadState('networkidle')
    await expect(page).not.toHaveURL(/\/admin\/dashboard/)
  })
})
