import { test as setup } from '@playwright/test'
import { loginLandlord } from '../helpers/auth'

setup('authenticate as landlord', async ({ page }) => {
  await loginLandlord(page)
  await page.context().storageState({ path: 'tests/e2e/.auth/landlord.json' })
})
