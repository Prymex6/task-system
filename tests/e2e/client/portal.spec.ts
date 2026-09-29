import { test, expect } from '@playwright/test'

test.describe('Client portal', () => {
  test('the dashboard renders', async ({ page }) => {
    const response = await page.goto('/portal/dashboard')
    expect(response?.status()).toBe(200)
    await expect(page).toHaveURL(/\/portal\/dashboard/)
  })

  test('the invoice list renders', async ({ page }) => {
    const response = await page.goto('/portal/invoices')
    expect(response?.status()).toBe(200)
  })

  test('the knowledge base renders', async ({ page }) => {
    const response = await page.goto('/portal/knowledge-base')
    expect(response?.status()).toBe(200)
  })

  test('the ticket list renders', async ({ page }) => {
    const response = await page.goto('/portal/support')
    expect(response?.status()).toBe(200)
  })

  test('the new-ticket form is reachable', async ({ page }) => {
    await page.goto('/portal/support/create')
    await expect(page.locator('input[type="text"]').first()).toBeVisible()
  })

  /**
   * The one write the portal project performs. A customer raising a ticket is
   * the path that has to work even when nothing else in the portal does, so
   * it is worth submitting for real rather than asserting the form renders.
   */
  test('a customer can raise a ticket', async ({ page }) => {
    await page.goto('/portal/support/create')

    await page.locator('input[type="text"]').first().fill('Raised by the end-to-end suite')

    const body = page.locator('textarea').first()
    if (await body.isVisible()) {
      await body.fill('Submitted while checking that the portal can open a ticket.')
    }

    const priority = page.locator('select').first()
    if (await priority.isVisible()) {
      await priority.selectOption({ index: 1 })
    }

    await page.locator('button[type="submit"]').first().click()
    await page.waitForLoadState('networkidle')

    // Either the list or the new ticket — both mean it saved; staying on
    // /create would mean validation sent it back.
    await expect(page).toHaveURL(/\/portal\/support(?!\/create)/)
  })
})
