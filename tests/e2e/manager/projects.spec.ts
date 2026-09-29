import { test, expect } from '@playwright/test'

/**
 * The path the product is built around: a project, opened, with a task added.
 *
 * Everything the suite writes is prefixed "E2E " because `e2e:setup` clears
 * rows by that prefix. Dropping it would leave a project behind on every run.
 */

test.describe('Projects', () => {
  test('the seeded project is listed', async ({ page }) => {
    await page.goto('/projects')
    await page.waitForLoadState('networkidle')
    await expect(page.locator('text=Redesign strony głównej Acme')).toBeVisible()
  })

  test('a project can be created and opened', async ({ page }) => {
    await page.goto('/projects/create')
    await page.locator('input[type="text"]').first().fill('E2E Project')
    await page.locator('button[type="submit"]').first().click()
    await page.waitForLoadState('networkidle')

    // The controller redirects to the project it just made; landing back on
    // the list would mean validation sent the form back instead.
    await expect(page).toHaveURL(/\/projects\/\d+/)
  })

  test('a task can be added to a project', async ({ page }) => {
    await page.goto('/projects/create')
    await page.locator('input[type="text"]').first().fill('E2E Project with a task')
    await page.locator('button[type="submit"]').first().click()
    await page.waitForURL(/\/projects\/\d+/)

    const projectId = page.url().match(/\/projects\/(\d+)/)?.[1]
    expect(projectId, 'the project redirect carried no id').toBeTruthy()

    await page.goto(`/projects/${projectId}/tasks/create`)
    await expect(page.locator('input[type="text"]').first()).toBeVisible()

    await page.locator('input[type="text"]').first().fill('E2E Task')
    await page.locator('button[type="submit"]').first().click()
    await page.waitForLoadState('networkidle')

    await expect(page).not.toHaveURL(/\/tasks\/create$/)
  })

  test('the task list and the recurring tasks render', async ({ page }) => {
    for (const path of ['/tasks', '/tasks/recurring']) {
      const response = await page.goto(path)
      expect(response?.status(), `GET ${path}`).toBe(200)
    }
  })
})
