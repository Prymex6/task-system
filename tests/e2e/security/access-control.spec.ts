import { test, expect } from '@playwright/test'

/**
 * Runs with no session at all.
 *
 * A missing middleware looks identical to a working one until somebody without
 * a session opens the page, which is why these assert the redirect rather than
 * the page: reaching the sign-in screen is the evidence the guard fired.
 */

test.describe('A guest cannot reach the manager panel', () => {
  for (const path of ['/dashboard', '/projects', '/finance/invoices', '/support', '/knowledge-base']) {
    test(`${path} redirects to the sign-in page`, async ({ page }) => {
      await page.goto(path)
      await page.waitForLoadState('networkidle')
      await expect(page).toHaveURL(/\/login/)
    })
  }
})

test.describe('A guest cannot reach the client portal', () => {
  for (const path of ['/portal/dashboard', '/portal/invoices', '/portal/support']) {
    test(`${path} redirects to the portal sign-in page`, async ({ page }) => {
      await page.goto(path)
      await page.waitForLoadState('networkidle')
      await expect(page).toHaveURL(/\/portal\/logowanie/)
    })
  }
})

test.describe('The two guards are separate', () => {
  /**
   * Staff and customers authenticate against different guards, so a manager
   * session must not open the portal. Sharing one guard would let an employee
   * read the portal as though they were the customer.
   */
  test('a manager session does not open the client portal', async ({ browser }) => {
    const context = await browser.newContext({ storageState: 'tests/e2e/.auth/manager.json' })
    const page = await context.newPage()

    await page.goto('/portal/dashboard')
    await page.waitForLoadState('networkidle')
    await expect(page).toHaveURL(/\/portal\/logowanie/)

    await context.close()
  })
})
