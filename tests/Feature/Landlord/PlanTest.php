<?php

namespace Tests\Feature\Landlord;

use App\Models\Landlord\Plan;
use App\Models\Landlord\Tenant;
use Illuminate\Support\Str;
use Tests\LandlordTestCase;

class PlanTest extends LandlordTestCase
{
    public function test_plans_index_is_accessible_for_super_admin(): void
    {
        $this->actingAsSuperAdmin();

        $response = $this->get(route('landlord.plans.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Landlord/Plans/Index'));
    }

    public function test_guest_cannot_access_plans(): void
    {
        $response = $this->get(route('landlord.plans.index'));

        $response->assertRedirect();
    }

    public function test_super_admin_can_create_plan(): void
    {
        $this->actingAsSuperAdmin();

        $response = $this->post(route('landlord.plans.store'), [
            'name' => 'Plan Enterprise',
            'price' => 299.00,
            'is_active' => true,
            'features' => ['unlimited_projects', 'api_access'],
        ]);

        $response->assertRedirect(route('landlord.plans.index'));
        $this->assertDatabaseHas('plans', ['name' => 'Plan Enterprise', 'slug' => 'plan-enterprise']);
    }

    public function test_super_admin_can_update_plan(): void
    {
        $this->actingAsSuperAdmin();
        $plan = Plan::create(['name' => 'Stary plan', 'slug' => 'stary-plan', 'is_active' => true]);

        $response = $this->put(route('landlord.plans.update', $plan), [
            'name' => 'Zaktualizowany plan',
            'price' => 149.00,
            'is_active' => true,
        ]);

        $response->assertRedirect(route('landlord.plans.index'));
        $this->assertDatabaseHas('plans', ['id' => $plan->id, 'name' => 'Zaktualizowany plan']);
    }

    public function test_super_admin_can_delete_unused_plan(): void
    {
        $this->actingAsSuperAdmin();
        $plan = Plan::create(['name' => 'Stary plan do usunięcia', 'slug' => 'do-usuniecia', 'is_active' => true]);

        $response = $this->delete(route('landlord.plans.destroy', $plan));

        $response->assertRedirect();
        $this->assertDatabaseMissing('plans', ['id' => $plan->id]);
    }

    public function test_cannot_delete_plan_with_tenants(): void
    {
        $this->actingAsSuperAdmin();
        $plan = Plan::create(['name' => 'Plan z klientami', 'slug' => 'plan-z-klientami', 'is_active' => true]);

        // Simulate a tenant using this plan (skip real per-tenant DB provisioning; this
        // test only needs the row to exist so Plan::tenants()->exists() is true).
        $tenant = new Tenant([
            'id' => Str::uuid(),
            'name' => 'Tenant testowy',
            'plan_id' => $plan->id,
            'status' => 'active',
            'version' => 'stable',
        ]);
        $tenant->setInternal('create_database', false);
        $tenant->save();

        $response = $this->delete(route('landlord.plans.destroy', $plan));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('plans', ['id' => $plan->id]);
    }

    public function test_plan_creation_requires_name(): void
    {
        $this->actingAsSuperAdmin();

        $response = $this->post(route('landlord.plans.store'), [
            'price' => 99.00,
            'is_active' => true,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_plan_store_generates_slug_from_name(): void
    {
        $this->actingAsSuperAdmin();

        $this->post(route('landlord.plans.store'), [
            'name' => 'Mój Nowy Plan PRO',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('plans', ['slug' => 'moj-nowy-plan-pro']);
    }
}
