import { test, expect } from '@playwright/test'
import { CREDENTIALS } from '../helpers/auth'

test.use({ storageState: { cookies: [], origins: [] } })

test.describe('Manager sign-in', () => {
  test('the sign-in page offers an email, a password and a submit button', async ({ page }) => {
    await page.goto('/login')
    await expect(page.locator('input[type="email"]')).toBeVisible()
    await expect(page.locator('input[type="password"]')).toBeVisible()
    await expect(page.locator('button[type="submit"]')).toBeVisible()
  })

  test('valid credentials land on the dashboard', async ({ page }) => {
    await page.goto('/login')
    await page.fill('input[type="email"]', CREDENTIALS.manager.email)
    await page.fill('input[type="password"]', CREDENTIALS.manager.password)
    await page.click('button[type="submit"]')
    await page.waitForURL(/\/dashboard/, { timeout: 15_000 })
  })

  test('a wrong password keeps the visitor on the sign-in page', async ({ page }) => {
    await page.goto('/login')
    await page.fill('input[type="email"]', CREDENTIALS.manager.email)
    await page.fill('input[type="password"]', 'not-the-password')
    await page.click('button[type="submit"]')
    await page.waitForLoadState('networkidle')
    await expect(page).toHaveURL(/\/login/)
  })

  test('an unknown address keeps the visitor on the sign-in page', async ({ page }) => {
    await page.goto('/login')
    await page.fill('input[type="email"]', 'nobody@example.com')
    await page.fill('input[type="password"]', 'password')
    await page.click('button[type="submit"]')
    await page.waitForLoadState('networkidle')
    await expect(page).toHaveURL(/\/login/)
  })

  /**
   * Signing out sits behind the profile menu in the header, and the control is
   * an Inertia Link rendered as a button — it posts, so it carries no href to
   * aim at and is reached by its test id.
   */
  test('signing out returns to the sign-in page', async ({ page }) => {
    await page.goto('/login')
    await page.fill('input[type="email"]', CREDENTIALS.manager.email)
    await page.fill('input[type="password"]', CREDENTIALS.manager.password)
    await page.click('button[type="submit"]')
    await page.waitForURL(/\/dashboard/, { timeout: 15_000 })

    await page.locator('header button').last().click()
    await page.getByTestId('sign-out').click()

    await page.waitForURL(/\/login/, { timeout: 15_000 })
  })
})
