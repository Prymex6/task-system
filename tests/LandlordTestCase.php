<?php

namespace Tests;

use App\Models\Landlord\SuperAdmin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

abstract class LandlordTestCase extends TestCase
{
    /**
     * Deliberately NOT using Illuminate\Foundation\Testing\DatabaseTransactions:
     * its setUp hook runs as part of parent::setUp() (Laravel's trait-based
     * setUpTraits()), which happens BEFORE the config() overrides below can take
     * effect — so it would begin a transaction on the stale 'central' connection
     * (still pointing at 'tasksystem_test') instead of the reconfigured one
     * (pointing at 'tasksystem_central_test'), and every write here would commit
     * for real instead of rolling back. Wrapping the transaction manually here
     * guarantees correct ordering.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // The 'central' (landlord) and default/tenant connections both read
        // env('DB_DATABASE') and would otherwise collide in the same physical
        // 'tasksystem_test' database (some table names, e.g. ticket_messages,
        // exist in both the tenant and landlord schemas). Point 'central' at a
        // dedicated database instead — keep this in sync with
        // 'tasksystem_central_test', which must be migrated once with:
        //   DB_CONNECTION=mysql DB_DATABASE=tasksystem_central_test php artisan migrate:fresh --path=database/migrations/landlord --database=central --force
        config(['database.connections.central.database' => 'tasksystem_central_test']);

        // Landlord models query the 'central' connection explicitly. Connection-less
        // helpers (assertDatabaseHas/Missing, validation's exists: rule) read whatever
        // config('database.default') is, which would otherwise be a separate connection
        // that can't see rows written inside 'central's uncommitted test transaction.
        config(['database.default' => 'central']);

        // Force stancl/tenancy's HasDatabase trait to resolve 'central' too (it otherwise
        // reads env('DB_CONNECTION') directly, bypassing config('database.default') above).
        config(['tenancy.database.central_connection' => 'central']);

        DB::purge('central');
        DB::connection('central')->beginTransaction();
    }

    protected function tearDown(): void
    {
        DB::connection('central')->rollBack();

        parent::tearDown();
    }

    protected function actingAsSuperAdmin(array $overrides = []): SuperAdmin
    {
        $admin = SuperAdmin::create(array_merge([
            'name' => 'Super Admin',
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
        ], $overrides));

        $this->actingAs($admin, 'super_admin');

        return $admin;
    }
}
