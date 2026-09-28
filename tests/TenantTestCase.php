<?php

namespace Tests;

use App\Http\Middleware\Tenant\CheckSetupComplete;
use App\Http\Middleware\Tenant\CheckTenantLicense;
use App\Models\Tenant\Client;
use App\Models\Tenant\ClientContact;
use App\Models\Tenant\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

/**
 * Base test case for tenant feature tests.
 *
 * Uses DatabaseTransactions — wraps each test in a transaction and rolls it
 * back after, which is fast and requires no truncation. The `tasksystem_test`
 * MySQL database must already be migrated (run once with:
 *   DB_DATABASE=tasksystem_test php artisan migrate --path=database/migrations/tenant --force
 * ).
 */
abstract class TenantTestCase extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        // Mark install as complete so CheckSetupComplete middleware passes.
        // (We also bypass this middleware via withoutTenantMiddleware(), but
        //  setting it here prevents issues if a test doesn't call that helper.)
        DB::table('settings')->insertOrIgnore([
            'key' => 'setup_completed',
            'value' => json_encode(true),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Create a manager/staff user and authenticate as them (tenant guard).
     */
    protected function actingAsManager(array $overrides = []): User
    {
        $user = User::factory()->create(array_merge([
            'workspace_role' => 'admin',
            'is_active' => true,
        ], $overrides));

        $this->actingAs($user, 'tenant');

        return $user;
    }

    /**
     * Create a client + contact and authenticate as the contact (customer guard).
     */
    protected function actingAsClient(array $clientOverrides = [], array $contactOverrides = []): ClientContact
    {
        $client = Client::factory()->create($clientOverrides);

        $contact = ClientContact::factory()->create(array_merge([
            'client_id' => $client->id,
        ], $contactOverrides));

        $this->actingAs($contact, 'customer');

        return $contact;
    }

    /**
     * Seed a single setting.
     */
    protected function setSetting(string $key, mixed $value, string $type = 'string'): void
    {
        DB::table('settings')->upsert(
            [['key' => $key, 'value' => (string) $value, 'type' => $type, 'created_at' => now(), 'updated_at' => now()]],
            ['key'],
            ['value', 'type', 'updated_at']
        );
    }

    /**
     * Return middleware classes to bypass in tenant tests.
     */
    protected function tenantMiddlewareToExclude(): array
    {
        return [
            InitializeTenancyByDomain::class,
            PreventAccessFromCentralDomains::class,
            CheckSetupComplete::class,
            CheckTenantLicense::class,
        ];
    }

    /**
     * Make a request bypassing tenant-specific middleware.
     */
    protected function withoutTenantMiddleware(): static
    {
        return $this->withoutMiddleware($this->tenantMiddlewareToExclude());
    }
}
