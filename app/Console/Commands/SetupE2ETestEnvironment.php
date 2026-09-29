<?php

namespace App\Console\Commands;

use App\Models\Landlord\Plan;
use App\Models\Landlord\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Stancl\Tenancy\Database\Models\Domain;

/**
 * Provisions the workspace the Playwright suite runs against.
 *
 * Playwright calls this from its global setup, so it has to be safe to run over
 * an existing environment: the workspace is created only when it is missing,
 * and the rows an earlier run left behind are removed rather than accumulated.
 */
class SetupE2ETestEnvironment extends Command
{
    protected $signature = 'e2e:setup';

    protected $description = 'Create and seed the workspace the end-to-end suite runs against';

    /**
     * Tenancy resolves by full hostname, so the domain record has to carry the
     * host Playwright asks for and not just the subdomain in front of it.
     */
    private const DOMAIN = 'ecommerce.localhost';

    /** Prefix every row the suite writes, so the next run can clear it out. */
    private const FIXTURE_PREFIX = 'E2E ';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('This command must not run in production.');

            return self::FAILURE;
        }

        $this->call('db:seed', ['--class' => 'LandlordSeeder', '--force' => true]);

        $tenant = $this->resolveTenant();

        if (!$tenant) {
            return self::FAILURE;
        }

        $tenant->update([
            'status' => 'active',
            'license_ends_at' => now()->addYear(),
        ]);

        $this->call('tenants:migrate', ['--tenants' => [$tenant->id], '--force' => true]);

        tenancy()->initialize($tenant);

        // Sign-in throttling is per address, and three projects sign in on every
        // run; without this the later ones start hitting the limiter.
        $this->call('cache:clear');

        $this->clearFixtures();

        $this->call('db:seed', ['--class' => 'TenantSeeder', '--force' => true]);

        tenancy()->end();

        $this->newLine();
        $this->table(['Key', 'Value'], [
            ['Workspace', 'http://' . self::DOMAIN . ':8000'],
            ['Manager', 'admin@example.com / password'],
            ['Owner', 'owner@example.com / password'],
            ['Client portal', 'portal@acme.pl / password'],
            ['Tenant ID', $tenant->id],
        ]);

        return self::SUCCESS;
    }

    /**
     * Creating the tenant is what makes a fresh clone runnable: the package
     * creates and migrates its database off the back of Tenant::create, so
     * there is nothing to set up by hand first.
     */
    private function resolveTenant(): ?Tenant
    {
        $domain = Domain::where('domain', self::DOMAIN)->first();

        if ($domain) {
            $tenant = Tenant::find($domain->tenant_id);

            if (!$tenant) {
                $this->error('Domain ' . self::DOMAIN . ' points at a tenant that no longer exists.');

                return null;
            }

            $this->info("Using workspace {$tenant->name} ({$tenant->id}).");

            return $tenant;
        }

        $this->info('Creating the workspace at ' . self::DOMAIN . ' …');

        $tenant = Tenant::create([
            'id' => (string) Str::uuid(),
            'name' => 'E2E Test Tenant',
            'subdomain' => 'ecommerce',
            'plan_id' => Plan::query()->orderBy('id')->value('id'),
            'status' => 'active',
            'license_ends_at' => now()->addYear(),
        ]);

        $tenant->domains()->create(['domain' => self::DOMAIN]);

        return $tenant;
    }

    /**
     * The suite writes as it goes — a ticket here, a project there — and those
     * rows would otherwise pile up run after run until a list assertion that
     * expects one match finds twelve.
     */
    private function clearFixtures(): void
    {
        $columns = [
            'clients' => 'company_name',
            'projects' => 'name',
            'kb_articles' => 'title',
            'tickets' => 'subject',
            'contracts' => 'subject',
            'proposals' => 'title',
            'leads' => 'company_name',
            'deals' => 'title',
        ];

        foreach ($columns as $table => $column) {
            DB::table($table)->where($column, 'like', self::FIXTURE_PREFIX . '%')->delete();
        }

        DB::table('users')->where('email', 'like', 'e2e.%@test.com')->delete();
    }
}
