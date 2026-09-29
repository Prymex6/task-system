import { test, expect } from '@playwright/test'
import { expectPageRenders, expectNotSaved } from '../helpers/pages'

/**
 * Create forms: that they render, and that an empty one is turned away.
 *
 * The second half matters more than it looks. Validation lives in a form
 * request, and a rule dropped from one is invisible — the screen still works,
 * it just accepts what it should not. Each row carries the URL the form would
 * reach on a successful save, and the test asserts it did not get there.
 */
const FORMS: Array<{ path: string; savedUrl: RegExp }> = [
  { path: '/projects/create', savedUrl: /\/projects\/\d+$/ },
  { path: '/crm/clients/create', savedUrl: /\/crm\/clients\/\d+$/ },
  { path: '/finance/invoices/create', savedUrl: /\/finance\/invoices\/\d+$/ },
  { path: '/finance/estimates/create', savedUrl: /\/finance\/estimates\/\d+$/ },
  { path: '/contracts/create', savedUrl: /\/contracts\/\d+$/ },
  { path: '/proposals/create', savedUrl: /\/proposals\/\d+$/ },
  { path: '/knowledge-base/create', savedUrl: /\/knowledge-base\/\d+$/ },
  { path: '/webhooks/create', savedUrl: /\/webhooks\/\d+$/ },
  { path: '/support/create', savedUrl: /\/support\/\d+$/ },
]

test.describe('Create forms', () => {
  for (const { path } of FORMS) {
    test(`${path} renders`, async ({ page }) => {
      await expectPageRenders(page, path)
      await expect(page.locator('input, select, textarea').first()).toBeVisible()
    })
  }

  for (const { path, savedUrl } of FORMS) {
    test(`${path} rejects an empty submission`, async ({ page }) => {
      await page.goto(path)
      await page.locator('button[type="submit"]').first().click()
      await page.waitForLoadState('networkidle')
      await expectNotSaved(page, savedUrl)
    })
  }
})

test.describe('Workspace settings', () => {
  test('the settings form offers its fields and a way to save them', async ({ page }) => {
    await page.goto('/settings')
    await expect(page.locator('input[type="text"], input[type="email"]').first()).toBeVisible()
    await expect(page.locator('button[type="submit"]').first()).toBeVisible()
  })
})
