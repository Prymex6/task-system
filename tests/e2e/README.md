# Pizza SaaS – Playwright E2E Tests

## Prerequisites

1. **XAMPP** running with MySQL (port 3306) and PHP 8.2+
2. **Hosts file** – `pizza.localhost` resolves automatically (no hosts file entry needed)
3. **Server** running on port 8000: `php artisan serve --port=8000`
4. **Node** ≥ 18 with packages installed: `npm install`
5. **Playwright browsers**: `npx playwright install chromium`

## Setup

```bash
# Create test tenant + seed all data
php artisan e2e:setup

# (Optional) Fresh reset
php artisan e2e:setup --fresh
```

## Running Tests

```bash
# All tests (headless)
npm run test:e2e

# With browser UI (visible)
npm run test:e2e:headed

# Interactive Playwright UI
npm run test:e2e:ui

# Debug single test
npm run test:e2e:debug -- tests/e2e/manager/menu.spec.ts

# Single project
npx playwright test --project=landlord
npx playwright test --project=manager
npx playwright test --project=staff
npx playwright test --project=client
```

## Test Credentials

| Role        | Email                       | Password  |
|-------------|-----------------------------|-----------|
| Super Admin | admin@roveto.pl       | password  |
| Manager     | manager@pizzaroma.pl        | password  |
| Chef        | kucharz@pizzaroma.pl        | password  |
| Waiter      | kelner@pizzaroma.pl         | password  |
| Driver      | kierowca@pizzaroma.pl       | password  |
| Customer 1  | klient@example.pl           | password  |
| Customer 2  | maria@example.pl            | password  |

## Test Structure

```
tests/e2e/
├── global-setup.ts            # Runs php artisan e2e:setup
├── helpers/
│   └── auth.ts                # Login helpers + credentials
├── setup/
│   ├── landlord-auth.setup.ts # Saves super-admin session
│   └── tenant-auth.setup.ts   # Saves manager/chef/waiter/driver/customer sessions
├── landlord/
│   ├── auth.spec.ts           # Super-admin login/logout
│   ├── dashboard.spec.ts      # Landlord dashboard
│   ├── plans.spec.ts          # Plan CRUD
│   ├── tenants.spec.ts        # Tenant management + impersonation
│   ├── support.spec.ts        # Landlord support tickets
│   └── modifications.spec.ts  # OCMod modifications CRUD + apply
├── manager/
│   ├── auth.spec.ts           # Manager login/logout/password reset
│   ├── dashboard.spec.ts      # Manager dashboard + charts
│   ├── menu.spec.ts           # Category + product CRUD
│   ├── orders.spec.ts         # Order management
│   ├── staff.spec.ts          # Staff CRUD
│   ├── settings.spec.ts       # Settings + delivery zones
│   ├── discounts.spec.ts      # Discount code CRUD
│   ├── tables.spec.ts         # Table management + QR codes
│   ├── customers.spec.ts      # Customer list + detail + CSV export
│   ├── loyalty.spec.ts        # Loyalty points, rewards, campaigns
│   ├── marketing.spec.ts      # Email marketing campaigns
│   ├── reservations.spec.ts   # Reservation management
│   ├── reports.spec.ts        # Sales reports + CSV export
│   ├── role-permissions.spec.ts # Role permission matrix
│   ├── support.spec.ts        # Support tickets (tenant side)
│   ├── license.spec.ts        # License info
│   ├── reviews.spec.ts        # Review management + responses
│   ├── vacation-mode.spec.ts  # Vacation/closed mode toggle
│   └── install-wizard.spec.ts # Install + setup wizard
├── client/
│   ├── landing.spec.ts        # Landing page (central domain)
│   ├── menu.spec.ts           # Menu browsing (guest)
│   ├── auth.spec.ts           # Customer login/register/logout
│   ├── checkout.spec.ts       # Checkout flow + discount validation
│   ├── account.spec.ts        # Customer account management
│   ├── email-change.spec.ts   # Email change re-verification flow
│   ├── gdpr.spec.ts           # Cookie consent + delete account (GDPR)
│   ├── invoice.spec.ts        # PDF invoice (printable HTML)
│   ├── reservation.spec.ts    # Online reservation form
│   ├── contact.spec.ts        # Contact form
│   └── marketing-unsubscribe.spec.ts # HMAC unsubscribe link
├── staff/
│   ├── kitchen.spec.ts        # KDS (chef)
│   ├── waiter.spec.ts         # Waiter panel + table status
│   ├── driver.spec.ts         # Driver panel
│   ├── pos.spec.ts            # POS (waiter/cashier)
│   └── reports.spec.ts        # Staff reports
└── security/
    ├── access-control.spec.ts # Role-based access + guest redirects
    ├── rate-limiting.spec.ts  # Throttle checks
    └── landlord-access.spec.ts # Landlord auth guards
```

## Reports

After running, open the HTML report:
```bash
npx playwright show-report tests/e2e/.report
```
