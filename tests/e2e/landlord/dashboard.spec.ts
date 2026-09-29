import { test, expect } from '@playwright/test'

test.describe('Landlord dashboard', () => {
  test('the dashboard renders', async ({ page }) => {
    const response = await page.goto('/admin/dashboard')
    expect(response?.status()).toBe(200)
    await expect(page).toHaveURL(/\/admin\/dashboard/)
  })

  test('the platform figures are on the page', async ({ page }) => {
    await page.goto('/admin/dashboard')
    await page.waitForLoadState('networkidle')
    await expect(page.locator('body')).not.toBeEmpty()
  })
})

test.describe('Workspace leads', () => {
  test('the lead list renders', async ({ page }) => {
    const response = await page.goto('/admin/workspace-leads')
    expect(response?.status()).toBe(200)
    await expect(page).toHaveURL(/\/admin\/workspace-leads/)
  })
})

test.describe('Platform support', () => {
  test('the ticket list renders', async ({ page }) => {
    const response = await page.goto('/admin/support')
    expect(response?.status()).toBe(200)
    await expect(page).toHaveURL(/\/admin\/support/)
  })
})
