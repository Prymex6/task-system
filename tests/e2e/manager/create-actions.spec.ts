import { test, expect } from '@playwright/test'

/**
 * Every list screen has to offer the way into its create flow.
 *
 * These are located by href or by test id, never by the label on the control.
 * The interface is bilingual, so a selector written against the Polish wording
 * passes only until a workspace is switched to English — and then reports a
 * missing button rather than a translated one.
 */

const BY_LINK: Array<[list: string, createPath: string]> = [
  ['/projects', '/projects/create'],
  ['/crm/clients', '/crm/clients/create'],
  ['/crm/leads', '/crm/leads/create'],
  ['/crm/deals', '/crm/deals/create'],
  ['/finance/invoices', '/finance/invoices/create'],
  ['/finance/estimates', '/finance/estimates/create'],
  ['/contracts', '/contracts/create'],
  ['/proposals', '/proposals/create'],
]

const BY_TEST_ID: Array<[list: string, testId: string]> = [
  ['/knowledge-base', 'create-article'],
  ['/sprints', 'create-sprint'],
  ['/finance/expenses', 'create-expense'],
  ['/messages', 'create-conversation'],
  ['/hr/staff', 'create-staff'],
]

test.describe('Create actions', () => {
  for (const [list, createPath] of BY_LINK) {
    test(`${list} links to ${createPath}`, async ({ page }) => {
      await page.goto(list)
      await expect(page.locator(`a[href$="${createPath}"]`).first()).toBeVisible()
    })
  }

  // These open a modal rather than navigating, so there is no href to match.
  for (const [list, testId] of BY_TEST_ID) {
    test(`${list} offers its create control`, async ({ page }) => {
      await page.goto(list)
      await expect(page.getByTestId(testId)).toBeVisible()
    })
  }
})
