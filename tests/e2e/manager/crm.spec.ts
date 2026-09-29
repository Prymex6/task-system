import { test, expect } from '@playwright/test'

test.describe('CRM', () => {
  test('the seeded client is listed', async ({ page }) => {
    await page.goto('/crm/clients')
    await page.waitForLoadState('networkidle')
    await expect(page.locator('text=Acme Corporation')).toBeVisible()
  })

  test('a client can be created', async ({ page }) => {
    await page.goto('/crm/clients/create')
    await page.locator('input[type="text"]').first().fill('E2E Client')
    await page.locator('button[type="submit"]').first().click()
    await page.waitForLoadState('networkidle')

    await expect(page).toHaveURL(/\/crm\/clients/)
  })
})
