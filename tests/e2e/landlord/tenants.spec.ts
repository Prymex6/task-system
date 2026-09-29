import { test, expect } from '@playwright/test'

test.describe('Workspaces', () => {
  test('the workspace list renders', async ({ page }) => {
    const response = await page.goto('/admin/tenants')
    expect(response?.status()).toBe(200)
    await expect(page).toHaveURL(/\/admin\/tenants/)
  })

  // Matched on the domain rather than the name: a machine that has run the
  // suite before carries more than one workspace called "E2E Test Tenant", and
  // the domain is the part e2e:setup guarantees is unique.
  test('the workspace created by e2e:setup is listed', async ({ page }) => {
    await page.goto('/admin/tenants')
    await page.waitForLoadState('networkidle')
    await expect(page.locator('text=ecommerce.localhost').first()).toBeVisible()
  })

  test('the list offers a way to add a workspace', async ({ page }) => {
    await page.goto('/admin/tenants')
    await expect(page.locator('a[href$="/admin/tenants/create"]').first()).toBeVisible()
  })

  test('the create form is reachable', async ({ page }) => {
    await page.goto('/admin/tenants/create')
    await expect(page.locator('input[type="text"]').first()).toBeVisible()
  })
})
