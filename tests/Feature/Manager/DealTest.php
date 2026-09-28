<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Client;
use App\Models\Tenant\Deal;
use App\Models\Tenant\DealStage;
use Tests\TenantTestCase;

class DealTest extends TenantTestCase
{
    private function makeStage(array $attrs = []): DealStage
    {
        return DealStage::create(array_merge([
            'name' => 'Nowy lead',
            'order' => 1,
            'color' => '#3b82f6',
        ], $attrs));
    }

    private function makeDeal(DealStage $stage, array $attrs = []): Deal
    {
        return Deal::create(array_merge([
            'deal_stage_id' => $stage->id,
            'title' => 'Nowa umowa testowa',
            'value' => 5000.00,
            'currency' => 'PLN',
        ], $attrs));
    }

    public function test_deals_index_is_accessible(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.deals.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/CRM/Deals/Index'));
    }

    public function test_manager_can_create_deal(): void
    {
        $this->actingAsManager();
        $stage = $this->makeStage();
        $client = Client::factory()->create();

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.deals.store'), [
                'deal_stage_id' => $stage->id,
                'client_id' => $client->id,
                'title' => 'Wielka umowa wdrożeniowa',
                'value' => 50000.00,
                'currency' => 'PLN',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('deals', [
            'title' => 'Wielka umowa wdrożeniowa',
            'client_id' => $client->id,
        ]);
    }

    public function test_manager_can_update_deal(): void
    {
        $this->actingAsManager();
        $stage = $this->makeStage();
        $deal = $this->makeDeal($stage);

        $response = $this->withoutTenantMiddleware()
            ->put(route('tenant.manager.deals.update', $deal), [
                'deal_stage_id' => $stage->id,
                'title' => 'Zaktualizowana umowa',
                'value' => 75000.00,
                'currency' => 'PLN',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('deals', ['id' => $deal->id, 'title' => 'Zaktualizowana umowa']);
    }

    public function test_manager_can_move_deal_to_different_stage(): void
    {
        $this->actingAsManager();
        $stage1 = $this->makeStage(['name' => 'Nowy', 'order' => 1]);
        $stage2 = $this->makeStage(['name' => 'Negocjacje', 'order' => 2]);
        $deal = $this->makeDeal($stage1);

        $response = $this->withoutTenantMiddleware()
            ->put(route('tenant.manager.deals.update', $deal), [
                'deal_stage_id' => $stage2->id,
                'title' => $deal->title,
                'value' => $deal->value,
                'currency' => 'PLN',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('deals', ['id' => $deal->id, 'deal_stage_id' => $stage2->id]);
    }

    public function test_manager_can_delete_deal(): void
    {
        $this->actingAsManager();
        $stage = $this->makeStage();
        $deal = $this->makeDeal($stage);

        $response = $this->withoutTenantMiddleware()
            ->delete(route('tenant.manager.deals.destroy', $deal));

        $response->assertRedirect();
        $this->assertDatabaseMissing('deals', ['id' => $deal->id]);
    }

    public function test_deal_can_be_marked_as_won(): void
    {
        $this->actingAsManager();
        $stage = $this->makeStage();
        $deal = $this->makeDeal($stage);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.deals.won', $deal));

        $response->assertRedirect();
        $this->assertDatabaseHas('deals', ['id' => $deal->id]);
        $this->assertNotNull(Deal::find($deal->id)->closed_at);
    }

    public function test_deal_creation_requires_stage_and_title(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.deals.store'), [
                'value' => 1000,
                'currency' => 'PLN',
            ]);

        $response->assertSessionHasErrors(['deal_stage_id', 'title']);
    }
}
