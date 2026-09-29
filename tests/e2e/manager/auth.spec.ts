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
   * Signing out is driven from the profile menu in the header. The control is
   * reached by opening that menu rather than by its label, for the same
   * reason the create buttons are: the wording changes with the language.
   */
  test('signing out returns to the sign-in page', async ({ page }) => {
    await page.goto('/login')
    await page.fill('input[type="email"]', CREDENTIALS.manager.email)
    await page.fill('input[type="password"]', CREDENTIALS.manager.password)
    await page.click('button[type="submit"]')
    await page.waitForURL(/\/dashboard/, { timeout: 15_000 })

    await page.locator('header button').last().click()

    const signOut = page.locator('form[action$="/logout"] button, header a[href$="/logout"]').last()
    await signOut.click()

    await page.waitForLoadState('networkidle')
    await expect(page).toHaveURL(/\/login/)
  })
})
