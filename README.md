# task-system

Work management for small agencies, with a database per client workspace.

Projects and tasks, time tracking, a CRM pipeline, invoicing, a client helpdesk and HR
records — the things an agency runs on, in one application, where each workspace is isolated
down to its own MySQL database.

<p>
  <img alt="PHP 8.2" src="https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white">
  <img alt="Laravel 12" src="https://img.shields.io/badge/Laravel-12-FF2D20?logo=laravel&logoColor=white">
  <img alt="Vue 3" src="https://img.shields.io/badge/Vue-3.5-4FC08D?logo=vuedotjs&logoColor=white">
  <img alt="Inertia 2" src="https://img.shields.io/badge/Inertia-2-9553E9">
  <img alt="Tailwind 4" src="https://img.shields.io/badge/Tailwind-4-06B6D4?logo=tailwindcss&logoColor=white">
  <img alt="516 tests" src="https://img.shields.io/badge/tests-516-success">
  <img alt="PHPStan level 1" src="https://img.shields.io/badge/PHPStan-level%201-blue">
</p>

---

## At a glance

|                   |                                                              |
| ----------------- | ------------------------------------------------------------ |
| **Stack**         | Laravel 12 · Inertia 2 · Vue 3 · Tailwind 4 · MySQL 8        |
| **Tenancy**       | `stancl/tenancy`, database per workspace, resolved by domain |
| **Size**          | ~23k lines of PHP, ~21k of Vue                               |
| **Surface**       | 103 controllers · 113 models · 44 services · 153 pages       |
| **Schema**        | 120 tables across two migration sets                         |
| **Routes**        | 484, every one reaching a method that exists                 |
| **Tests**         | 516 PHPUnit · 138 Playwright                                 |
| **Quality gates** | Pint · PHPStan level 1, no baseline · ESLint · Prettier      |
| **Languages**     | Polish and English, 1914 catalogued strings                  |

---

## What it does

One codebase serves three audiences.

### The manager panel — what the agency works in

**Projects.** A kanban board, members with per-project roles, milestones, a file area,
threaded discussions, and templates that stamp out a project together with its tasks. Budgets
track income against spending, with an hourly rate and a running profit line.

**Tasks.** Comments (internal or client-visible), attachments, checklists, subtasks,
dependencies with loop detection, recurrence, and time logged straight against a task. A
board, a calendar and a Gantt chart read the same data three ways. Sprints group them into
iterations with a burndown.

**Time tracking.** A global timer plus manual entries, weekly timesheets that go through an
approval step, and reports by person and by project. Approving a timesheet is what makes its
hours billable.

**CRM.** Clients with contacts and portal access, leads with an activity history, and a
drag-and-drop deal pipeline with weighted forecasting.

**Finance.** Invoices with part payments and automatic status reconciliation, recurring
invoices that issue themselves on a schedule, estimates that convert into invoices, credit
notes, expenses with an approval flow, contracts and proposals. PDF out, CSV out.

**Helpdesk.** Tickets with SLA policies, canned responses, support departments, and a
knowledge base the client portal reads from.

**HR.** Team and invitations, leave requests against a balance with a shared calendar,
attendance clock-in, performance reviews, departments and positions.

**Automations.** A trigger, some conditions and an action — assign a task, change a status,
send an e-mail, call a webhook — built from a form rather than from code.

### The client portal — the narrow view customers log into

Their projects and the tasks assigned to them, invoices with an outstanding balance, estimates
they can accept or decline, contracts they can sign electronically, support tickets, and the
knowledge base. Accounts are created by the agency; there is no self-registration, which is
deliberate.

### The landlord panel — the platform behind it

Workspaces and their subscription plans, platform statistics, support tickets from tenants,
prospect tracking and contact enquiries.

---

## Tenancy

Each workspace is a tenant with its own MySQL database, resolved from the request domain by
[stancl/tenancy](https://tenancyforlaravel.com). The landlord database holds only tenants,
plans and platform support — no workspace data ever lands in it.

Two migration sets reflect the split:

```
database/migrations/landlord/   tenants, plans, platform support   (10 files)
database/migrations/tenant/     everything a workspace owns        (25 files)
```

A tenant's schema is created and migrated when the tenant is, so adding a workspace needs no
manual database work.

Models live under `App\Models\Tenant` and `App\Models\Landlord`. The namespace is the reminder
of which connection a query will land on — the one place where getting it wrong is expensive
and silent.

`TenantMailBootstrapper` swaps the mail from-address per tenant, so an invoice e-mail goes out
under the agency's own name rather than the platform's. Files are scoped the same way, so one
workspace's uploads are never reachable from another.

---

## Authorisation

Two things gate a request.

`workspace.role` sits on the route groups: settings are admin-only, finance, reports and HR
are admin or manager. The owner passes without being named on any route — spelling them out
everywhere invites the one omission that locks a workspace out of its own settings.

Controllers then narrow it further where a role is not enough: a task is editable by whoever
is on its project, a note by whoever wrote it, an estimate only while it is still a draft.

`tests/Feature/Manager/WorkspaceRoleGateTest.php` holds 42 tests over that gate, because the
failure mode is silent — a missing middleware looks exactly like a working one until somebody
with the wrong role opens the page.

---

## Languages

Nothing user-facing is written in the source. The back end hands every message to `__()`,
components call `$t()`, and the wording lives in catalogues:

```
lang/{pl,en}/                      497 back-end keys
resources/js/locales/{pl,en}.json  1417 front-end keys
```

`SetLocale` reads the workspace's `language` setting, falls back to Polish, and passes the
result to the page as `current_locale`. `resources/js/i18n.js` loads only that language's
dictionary, so a page never ships the one it is not rendering in.

Two of the guard tests are worth knowing about before adding a screen.

`test_no_message_is_left_hard_coded_in_php` scans the places a string reaches a person —
`->with('success', …)`, `withErrors`, `abort`, `$fail`, `->subject` — and fails on a literal.
It looks at **call sites rather than at the text**, because a language heuristic cannot tell
"Status" or "Plan" apart from English.

`test_no_template_prints_a_literal` does the front end, and looks at **where** a string is
rather than at what it says. A word list is not enough: "Logowanie…", "Kopiuj" and "Aktywny"
carry no diacritic and match no keyword, so fifty-odd strings sat untranslated behind a check
that read the text. Anything inside `{{ }}` is on screen by definition, so a literal there is
copy — bar a badge overflow count, an avatar initial, a required-field marker and an ISO
currency code.

`test_every_message_survives_the_vue_i18n_compiler` guards the catalogues themselves. The
message syntax reserves `@` for linked messages and `|` for plural forms, and neither fails
loudly: an e-mail address catalogued as a placeholder threw while the page was mounting and
rendered the entire staff panel blank. Write an `@` as `{'@'}`.

Dates and money go through `resources/js/format.js`, which takes its locale from the interface
language. Calling `Intl` with a tag written into the component is what left an English
workspace reading Polish dates.

---

## Getting it running

Requires **PHP 8.2+**, **MySQL 8**, **Node 20+** and Composer.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Point `DB_*` at a MySQL server. The host the landlord answers on is **not** an env variable —
it is the `central_domains` list in `config/tenancy.php`, which ships with `localhost`,
`127.0.0.1` and `task-system`. Anything else is treated as a tenant domain.

```bash
php artisan migrate      # framework + landlord tables
php artisan db:seed      # plans and a super admin
npm run dev
```

`migrate` covers the landlord schema because `AppServiceProvider` registers
`database/migrations/landlord` alongside the default path. Tenant migrations are separate and
run per workspace:

```bash
php artisan tenants:migrate
php artisan tenants:seed     # demo data — development only
```

A tenant is created through the `Tenant` model (see `TenantSeeder`); the tenancy package
creates and migrates its database as part of that.

**Set `SUPER_ADMIN_EMAIL` and `SUPER_ADMIN_PASSWORD` before seeding anywhere real** — without
them the seeder falls back to a known default and says so out loud.

### The scheduler

Recurring invoices and overdue flags come from a scheduled command, so nothing bills itself
unless the scheduler is running:

```bash
php artisan schedule:work    # locally
php artisan billing:cycle    # or run either by hand
php artisan tasks:recurring
```

Both walk every tenant in isolation: a failure in one is logged and the run carries on,
because one broken workspace must not stop everyone else being invoiced, or stop their
repeating tasks appearing.

---

## Tests and static analysis

```bash
php artisan test              # 516 tests, 4703 assertions
vendor/bin/pint --test        # code style
vendor/bin/phpstan analyse    # level 1, no baseline
npx eslint .                  # flat config
npx prettier --check .        # formatting
npx playwright test           # 138 end-to-end tests
```

### The end-to-end suite

Playwright drives a real browser against a real workspace, so unlike the PHPUnit suite it needs
a server and a tenant. Its global setup runs `php artisan e2e:setup`, which creates the
workspace at `ecommerce.localhost`, migrates it and seeds it — there is nothing to prepare by
hand:

```bash
php artisan serve                 # the workspace answers on ecommerce.localhost:8000
npx playwright test
TENANT_URL=http://ecommerce.localhost:8123 LANDLORD_URL=http://localhost:8123 npx playwright test
```

The last line is how to point it somewhere else when port 8000 is taken.

Two things about the environment matter. `SESSION_DOMAIN` has to be empty locally, or the
cookie is pinned to one host, never reaches the workspace domain and every sign-in comes back
a 419 — `.env.example` ships it that way. And the suite blocks service workers, because the
application registers one and it intercepts the sign-in POST before it reaches the server.

Selectors never match on user-facing text. The interface is bilingual, so a selector written
against the Polish wording reports a missing button rather than a translated one the moment a
workspace is switched to English. Links are found by `href`; controls that open a modal or post
a form carry a `data-testid`.

The suite runs against a **real MySQL database rather than SQLite**: the schema uses enum
columns throughout, and the finance reports group with `DATE_FORMAT`, which SQLite has no
equivalent for. The tables are built once up front — `TenantTestCase` rolls back per test
rather than migrating — and it takes **two** databases:

```bash
# What phpunit.xml points every connection at by default.
DB_DATABASE=tasksystem_test php artisan migrate --path=database/migrations/tenant --force
DB_DATABASE=tasksystem_test php artisan migrate --path=database/migrations/landlord --force

# LandlordTestCase repoints 'central' here.
mysql -u root -e "CREATE DATABASE IF NOT EXISTS tasksystem_central_test"
DB_DATABASE=tasksystem_central_test php artisan migrate --path=database/migrations/landlord --database=central --force
```

Both sets go into the first one because tenant screens read landlord tables through the
`central` connection — the billing screen lists the subscription plans — and outside
`LandlordTestCase` that connection resolves to whatever `DB_DATABASE` names. The landlord
tests need a database to themselves because a few table names exist in both schemas; in
production those are separate databases anyway, which is why the landlord table that would
otherwise collide is named `platform_ticket_messages`.

`.github/workflows/ci.yml` performs exactly these steps, so a green run there means the same
commands work from a clean checkout.

The suite does not need the front end to have been built. Every page renders through
`app.blade.php`, which asks Vite for a manifest, so the base `TestCase` stubs Vite out —
otherwise a hundred tests fail on a fresh clone for a reason unrelated to what they assert.

PHPStan runs at **level 1 with no baseline**. The one suppressed rule is `relationExistence`,
because Larastan cannot follow relations resolved through the tenancy package's model binding.
Everything else is fixed rather than ignored.

### The three checks worth knowing about

`tests/Unit/RouteIntegrityTest.php` asserts the wiring, because none of these break loudly:

- every route reaches a controller method that exists
- every route name a component calls is registered
- every page is rendered by something

Each of those failed when it was first written. A route declared against a method nobody wrote
is a 500 nobody sees until a user clicks; a `route()` call Ziggy cannot resolve throws and
blanks the page; a page no controller renders is dead weight that reads as a finished feature.

---

## Layout

```
app/
  Console/Commands/                 billing:cycle, tasks:recurring, backups, e2e:setup
  Http/Controllers/Tenant/Manager/  the agency panel
  Http/Controllers/Tenant/Client/   the customer portal
  Http/Controllers/Landlord/        the platform panel
  Http/Middleware/                  tenancy, locale, role gate
  Models/{Tenant,Landlord}/         split by database connection
  Services/                         logic shared between controllers and jobs
  Tenancy/Bootstrappers/            per-tenant mail configuration
  Traits/Auditable.php              opt-in change log on a model
database/migrations/{landlord,tenant}
lang/{pl,en}/                       back-end messages and validation
resources/js/
  Pages/Tenant/{Manager,Client}/    one component per screen
  Pages/Landlord/
  Components/Manager/               shared tables, forms and widgets
  locales/{pl,en}.json              interface wording
routes/tenant.php                   manager and client routes
routes/landlord.php                 platform routes
```

---

## Conventions worth knowing before reading the code

**Literal route segments come before wildcards.** `/contracts/types` has to be declared ahead
of `/contracts/{contract}`, or Laravel matches the wildcard first and the literal path 404s.
Five route groups were broken this way before it was spotted, so the ordering is deliberate.
The last two — `/crm/deals/stages` and `/tasks/recurring` — survived a long time because the
end-to-end tests covering them asserted only that the response was not a 500, which a 404
clears.

**`Route::resource` excludes what the controller does not implement.** A bare `resource()`
registers `create` and `edit` whether or not anything answers them; where a screen uses a
modal instead of a page, those verbs are excluded rather than left pointing at nothing.

**Dictionary screens share one component.** Task statuses, tax rates, currencies, departments
and the rest are all driven by `Components/Manager/DictionaryTable.vue`, configured with a
`fields` array and a `routeBase`. Adding a dictionary means a controller, a route group and
about twenty lines of Vue.

**Services hold what more than one caller needs.** A controller that is the only user of its
logic keeps it; `InvoiceService::syncPaymentState` lives in a service because a controller, a
job and a console command all have to agree on when an invoice counts as paid.

**Derived state is derived, not stored twice.** An invoice's paid/partial status is
recalculated from the payments recorded against it, so reversing a payment walks the invoice
back out of `paid` instead of stranding it there.

**Comments say why, not what.** The code says what it does; a comment earns its place by
explaining a constraint that is not visible from the line it sits on.

---

## Licence

Proprietary. Published as a portfolio piece, not for reuse.
