import { expect, Page } from '@playwright/test'

/**
 * Asserts a screen is actually served.
 *
 * The bar used to be "not a 500", which a 404 and a 403 both clear: a route
 * that quietly stopped existing kept a green test. 200 is the only status that
 * means the controller ran and rendered something.
 */
export async function expectPageRenders(page: Page, path: string): Promise<void> {
  const response = await page.goto(path)

  expect(response?.status(), `GET ${path}`).toBe(200)

  await page.waitForLoadState('networkidle')
  await expect(page.locator('body')).not.toBeEmpty()
}

/**
 * Asserts a form rejected what it was given.
 *
 * Inertia replays the same component with the errors attached, so a rejected
 * submission is recognised by *not* having moved on to the record's own page.
 * The caller passes the pattern as a literal rather than handing over a string
 * to build one from, because the backslash in \d does not survive the trip.
 */
export async function expectNotSaved(page: Page, savedUrl: RegExp): Promise<void> {
  await expect(page).not.toHaveURL(savedUrl)
}
