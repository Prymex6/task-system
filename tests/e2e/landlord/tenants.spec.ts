import { test, expect } from '@playwright/test'

test.describe('Workspaces', () => {
  test('the workspace list renders', async ({ page }) => {
    const response = await page.goto('/admin/tenants')
    expect(response?.status()).toBe(200)
    await expect(page).toHaveURL(/\/admin\/tenants/)
  })

  test('the workspace created by e2e:setup is listed', async ({ page }) => {
    await page.goto('/admin/tenants')
    await page.waitForLoadState('networkidle')
    await expect(page.locator('text=E2E Test Tenant')).toBeVisible()
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
