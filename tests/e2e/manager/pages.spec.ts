import { test } from '@playwright/test'
import { expectPageRenders } from '../helpers/pages'

/**
 * The screens the suite only needs to prove render.
 *
 * Kept as one table rather than as a file per area: each of these was three
 * near-identical tests asserting a page was not a 500, and the duplication hid
 * how thin the assertion underneath actually was.
 */
const SCREENS: Record<string, string[]> = {
  Dashboard: ['/dashboard', '/notifications', '/search', '/search?q=acme', '/profile'],
  Projects: ['/projects', '/project-templates', '/task-labels', '/task-statuses', '/sprints'],
  CRM: ['/crm/clients', '/crm/client-groups', '/crm/leads', '/crm/deals', '/crm/deals/pipeline', '/crm/deals/stages'],
  Finance: [
    '/finance/invoices',
    '/finance/estimates',
    '/finance/expenses',
    '/finance/currencies',
    '/finance/tax-rates',
  ],
  Contracts: ['/contracts', '/contracts/types', '/proposals'],
  'Time tracking': ['/time', '/time/team', '/time/reports', '/time/approvals'],
  Reports: ['/reports', '/reports/time', '/reports/finance', '/reports/projects', '/reports/staff', '/reports/clients'],
  HR: [
    '/hr/staff',
    '/hr/attendance',
    '/hr/announcements',
    '/hr/invitations',
    '/hr/leaves',
    '/hr/leaves/calendar',
    '/hr/leave-types',
    '/hr/performance',
    '/hr/positions',
    '/hr/departments',
    '/hr/permissions',
  ],
  Helpdesk: [
    '/support',
    '/support/canned-responses',
    '/support/departments',
    '/support/sla-policies',
    '/knowledge-base',
  ],
  Settings: [
    '/settings',
    '/settings/company',
    '/settings/finance',
    '/settings/billing',
    '/settings/custom-fields',
    '/settings/email-templates',
    '/settings/integrations',
    '/settings/notifications',
    '/settings/audit-log',
    '/automations',
    '/webhooks',
    '/messages',
  ],
}

for (const [area, paths] of Object.entries(SCREENS)) {
  test.describe(area, () => {
    for (const path of paths) {
      test(`${path} renders`, async ({ page }) => {
        await expectPageRenders(page, path)
      })
    }
  })
}
