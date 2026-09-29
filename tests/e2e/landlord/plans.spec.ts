import { test, expect } from '@playwright/test'

test.describe('Subscription plans', () => {
  test('the plan list renders', async ({ page }) => {
    const response = await page.goto('/admin/plans')
    expect(response?.status()).toBe(200)
    await expect(page).toHaveURL(/\/admin\/plans/)
  })

  test('the seeded plans are listed', async ({ page }) => {
    await page.goto('/admin/plans')
    await page.waitForLoadState('networkidle')
    await expect(page.locator('tbody tr').first()).toBeVisible()
  })

  // Located by href rather than by label: the panel is bilingual, and a
  // selector written against the Polish wording fails the moment a workspace
  // is switched to English.
  test('the list offers a way to add a plan', async ({ page }) => {
    await page.goto('/admin/plans')
    await expect(page.locator('a[href$="/admin/plans/create"]').first()).toBeVisible()
  })
})
