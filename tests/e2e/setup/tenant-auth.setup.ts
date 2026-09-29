import { test as setup } from '@playwright/test'
import { loginManager, loginClient } from '../helpers/auth'

setup('authenticate as manager', async ({ page }) => {
  await loginManager(page)
  await page.context().storageState({ path: 'tests/e2e/.auth/manager.json' })
})

setup('authenticate as client', async ({ page }) => {
  await loginClient(page)
  await page.context().storageState({ path: 'tests/e2e/.auth/client.json' })
})
