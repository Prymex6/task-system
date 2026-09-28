# task-system

Multi-tenant work management for small agencies: projects and tasks, time tracking, a CRM
pipeline, invoicing, a client helpdesk, and HR records — each workspace on its own database.

Built with Laravel 12, Inertia and Vue 3. Roughly 23k lines of PHP and 22k of Vue across
99 controllers, 113 models, 44 services and 160 pages, covered by 455 tests. The interface
exists in Polish and English.

## What it does

The application runs two front ends off one codebase.

**The manager panel** is what the agency works in:

- **Projects** — kanban board, members with per-project roles, milestones, files,
  discussions, and reusable templates that stamp out a project with its tasks
- **Tasks** — comments, attachments, checklists, labels, dependencies, sprints,
  and time logged directly against a task
- **Time tracking** — a global timer plus manual entries, with per-project reports
- **CRM** — clients and contacts, leads, and a drag-and-drop deal pipeline
- **Finance** — invoices with part payments, recurring invoices that issue
  themselves on a schedule, estimates, expenses and expense claims, credit notes,
  contracts and proposals, all with PDF output
- **Helpdesk** — tickets, canned responses, SLA policies and a knowledge base
- **HR** — team, invitations, leave requests and a leave calendar, timesheet
  approvals, attendance, and performance reviews
- **Settings** — task statuses and labels, tax rates, currencies, custom fields,
  e-mail templates, integrations, and per-role permissions

**The client portal** is the narrow view the agency's own customers log into: their
projects, invoices, contracts, proposals and support tickets.

## Tenancy

Each workspace is a tenant with its own MySQL database, resolved from the request domain by
[stancl/tenancy](https://tenancyforlaravel.com). The landlord database holds only tenants,
plans and platform-level support.

Two migration sets reflect this: `database/migrations/landlord` and
`database/migrations/tenant`. A tenant's schema is created and migrated when the tenant is,
so adding a workspace needs no manual database work.

Models live under `App\Models\Tenant` and `App\Models\Landlord`; the namespace is the
reminder of which connection a query will land on.

## Getting it running

Requires PHP 8.2+, MySQL 8, Node 20+ and Composer.

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Point `DB_*` at a MySQL server. The host the landlord answers on is not an env variable —
it is the `central_domains` list in `config/tenancy.php`, which ships with `localhost`,
`127.0.0.1` and `task-system`. Anything else is treated as a tenant domain.

```bash
php artisan migrate                  # framework + landlord tables
php artisan db:seed                  # plans and a super admin
npm run dev
```

`migrate` covers the landlord schema because `AppServiceProvider` registers
`database/migrations/landlord` alongside the default path. Tenant migrations are separate
and run per workspace:

```bash
php artisan tenants:migrate
php artisan tenants:seed             # demo data — development only
```

A tenant is created through the `Tenant` model (see `TenantSeeder`), and the tenancy package
creates and migrates its database as part of that.

Recurring invoices and overdue flags come from a scheduled command, so the scheduler has to
be running for those to fire:

```bash
php artisan schedule:work            # locally
php artisan billing:cycle            # or run the cycle by hand
```

## Languages

Nothing user-facing is written in the source. The back end hands every message to `__()`,
and components call `$t()`; the wording lives in `lang/{pl,en}` and
`resources/js/locales/{pl,en}.json` — 281 back-end keys and 1370 front-end ones.

`SetLocale` reads the workspace's `language` setting, falls back to Polish, and passes the
result to the page as `current_locale`. `resources/js/i18n.js` loads only that language's
dictionary, so a page never ships the one it is not rendering in.

Four tests in `tests/Unit/TranslationCatalogueTest.php` keep it that way. Two of them are
worth knowing about before adding a screen:

`test_no_message_is_left_hard_coded_in_php` scans the places a string reaches a person —
`->with('success', …)`, `withErrors`, `abort`, `$fail`, `->subject` — and fails on a literal.
It looks at call sites rather than at the text, because a language heuristic cannot tell
"Status" and "Plan" apart from English, and flags Polish in comments that nobody reads.

`test_a_php_catalogue_has_the_same_keys_in_both_languages` is the one that catches the
expensive mistake: a key added to one language only. The interface then falls back to
printing the key, which reads as a bug rather than as a missing translation.

## Tests and static analysis

```bash
php artisan test                     # 455 tests
vendor/bin/pint --test               # code style
vendor/bin/phpstan analyse           # level 1, no baseline
npx eslint .                         # flat config
npx playwright test                  # end-to-end
```

The suite runs against a real MySQL database rather than SQLite: the schema uses enum columns
throughout, and the finance reports group with `DATE_FORMAT`, which SQLite has no equivalent
for. `phpunit.xml` expects a `tasksystem_test` database on the `mysql` connection.

PHPStan runs at level 1 with no baseline. The one suppressed rule is `relationExistence`,
because Larastan cannot follow relations resolved through the tenancy package's model
binding; everything else is fixed rather than ignored.

## Layout

```
app/
  Http/Controllers/Tenant/Manager/   the agency panel
  Http/Controllers/Tenant/Client/    the customer portal
  Models/{Tenant,Landlord}/          split by database connection
  Services/                          logic shared between controllers and jobs
  Tenancy/                           bootstrappers and tenant lifecycle
database/migrations/{landlord,tenant}
resources/js/Pages/Tenant/{Manager,Client}/
resources/js/Components/Manager/     shared tables, forms and widgets
resources/js/locales/{pl,en}.json    the interface wording
lang/{pl,en}/                        back-end messages and validation
routes/tenant.php                    the manager and client routes
```

Two conventions worth knowing before reading the code:

Literal route segments are declared before wildcard ones — `/contracts/types` has to come
before `/contracts/{contract}`, or Laravel matches the wildcard first and the literal path
404s.

Dictionary screens (task statuses, tax rates, currencies, departments and the rest) are all
driven by one component, `Components/Manager/DictionaryTable.vue`, configured with a `fields`
array and a `routeBase`. Adding a dictionary means a controller, a route group and about
twenty lines of Vue.

## Licence

Proprietary. Published as a portfolio piece, not for reuse.
