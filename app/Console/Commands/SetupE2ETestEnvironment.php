<?php

namespace App\Console\Commands;

use App\Models\Landlord\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Database\Models\Domain;

class SetupE2ETestEnvironment extends Command
{
    protected $signature = 'e2e:setup {--fresh : Re-seed tenant data}';

    protected $description = 'Set up test environment for Playwright E2E tests';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('⛔ This command must NOT run in production.');

            return self::FAILURE;
        }

        $this->info('🧪 Setting up E2E test environment...');

        // 1. Landlord: plany + super admin
        $this->call('db:seed', ['--class' => 'LandlordSeeder', '--force' => true]);

        // 2. Find the tenant the e2e suite runs against
        $domain = 'ecommerce.localhost';
        $domainRecord = Domain::where('domain', $domain)->first();

        if (!$domainRecord) {
            $this->error("No tenant found at {$domain}. Create a tenant with that domain first.");

            return self::FAILURE;
        }

        $tenant = Tenant::find($domainRecord->tenant_id);

        if (!$tenant) {
            $this->error("Tenant record not found for domain {$domain}.");

            return self::FAILURE;
        }

        $this->info("  Tenant: {$tenant->id} ({$tenant->name}) → {$domain}");

        // 2b. Aktywna licencja
        $tenant->update([
            'status' => 'active',
            'license_ends_at' => now()->addYear(),
        ]);

        // 3. Migracje tenanta
        $this->info('  Running tenant migrations...');
        tenancy()->initialize($tenant);

        $this->call('tenants:migrate', [
            '--tenants' => [$tenant->id],
            '--force' => true,
        ]);

        tenancy()->initialize($tenant);

        // 4. Clear the cache so rate limits do not carry between runs
        $this->call('cache:clear');

        // 5. Drop whatever an earlier run left behind
        DB::table('users')
            ->where('email', 'like', 'e2e.%@test.com')
            ->delete();

        DB::table('clients')
            ->where('email', 'like', 'e2e.%@example.com')
            ->delete();

        DB::table('projects')
            ->where('name', 'like', 'E2E %')
            ->delete();

        DB::table('kb_articles')
            ->where('title', 'like', 'E2E %')
            ->delete();

        DB::table('tickets')
            ->where('subject', 'like', 'E2E %')
            ->delete();

        DB::table('contracts')
            ->where('subject', 'like', 'E2E %')
            ->delete();

        DB::table('proposals')
            ->where('title', 'like', 'E2E %')
            ->delete();

        DB::table('leads')
            ->where('company_name', 'like', 'E2E %')
            ->delete();

        DB::table('deals')
            ->where('title', 'like', 'E2E %')
            ->delete();

        // 6. Seed danych demo
        $this->info('  Seeding tenant data...');
        $this->call('db:seed', [
            '--class' => 'TenantSeeder',
            '--force' => true,
        ]);

        tenancy()->end();

        $this->newLine();
        $this->info('✅ E2E test environment ready!');
        $this->table(
            ['Key', 'Value'],
            [
                ['Tenant URL',    "http://{$domain}:8000"],
                ['Manager',       'admin@example.com / password'],
                ['Owner',         'owner@example.com / password'],
                ['Client portal', 'portal@acme.pl / password'],
                ['Tenant ID',     $tenant->id],
            ]
        );

        return self::SUCCESS;
    }
}
