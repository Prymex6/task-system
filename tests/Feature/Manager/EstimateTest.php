<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Client;
use App\Models\Tenant\Estimate;
use Tests\TenantTestCase;

class EstimateTest extends TenantTestCase
{
    private function makeClient(): Client
    {
        return Client::factory()->create();
    }

    private function makeEstimate(array $attrs = []): Estimate
    {
        return Estimate::create(array_merge([
            'client_id' => $this->makeClient()->id,
            'number' => 'EST/TEST/' . rand(1, 9999),
            'status' => 'draft',
            'currency' => 'PLN',
            'subtotal' => 1000.00,
            'tax_amount' => 230.00,
            'total' => 1230.00,
            'issue_date' => now()->toDateString(),
            'valid_until' => now()->addDays(30)->toDateString(),
        ], $attrs));
    }

    public function test_estimates_index_is_accessible(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.estimates.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/Finance/Estimates/Index'));
    }

    public function test_manager_can_create_estimate(): void
    {
        $user = $this->actingAsManager();
        $client = $this->makeClient();

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.estimates.store'), [
                'client_id' => $client->id,
                'number' => 'EST/2026/001',
                'status' => 'draft',
                'currency' => 'PLN',
                'subtotal' => 500.00,
                'tax_amount' => 115.00,
                'total' => 615.00,
                'issue_date' => now()->toDateString(),
                'valid_until' => now()->addDays(14)->toDateString(),
                'items' => [
                    ['description' => 'Usługa projektowa', 'quantity' => 1, 'unit_price' => 500.00, 'tax_rate' => 23],
                ],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('estimates', ['number' => 'EST/2026/001', 'client_id' => $client->id]);
    }

    public function test_manager_can_update_estimate(): void
    {
        $this->actingAsManager();
        $estimate = $this->makeEstimate(['status' => 'draft']);

        $response = $this->withoutTenantMiddleware()
            ->put(route('tenant.manager.estimates.update', $estimate), [
                'client_id' => $estimate->client_id,
                'number' => $estimate->number,
                'status' => 'draft',
                'currency' => 'PLN',
                'subtotal' => 2000.00,
                'tax_amount' => 460.00,
                'total' => 2460.00,
                'issue_date' => $estimate->issue_date,
                'valid_until' => $estimate->valid_until,
                'items' => [
                    ['description' => 'Usługa 2', 'quantity' => 2, 'unit_price' => 1000.00, 'tax_rate' => 23],
                ],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('estimates', ['id' => $estimate->id, 'total' => 2460.00]);
    }

    public function test_manager_can_send_estimate(): void
    {
        $this->actingAsManager();
        $estimate = $this->makeEstimate(['status' => 'draft']);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.estimates.send', $estimate));

        $response->assertRedirect();
        $this->assertDatabaseHas('estimates', ['id' => $estimate->id, 'status' => 'sent']);
    }

    public function test_manager_can_delete_draft_estimate(): void
    {
        $this->actingAsManager();
        $estimate = $this->makeEstimate(['status' => 'draft']);

        $response = $this->withoutTenantMiddleware()
            ->delete(route('tenant.manager.estimates.destroy', $estimate));

        $response->assertRedirect();
        $this->assertDatabaseMissing('estimates', ['id' => $estimate->id]);
    }

    public function test_estimate_show_page_loads(): void
    {
        $this->actingAsManager();
        $estimate = $this->makeEstimate();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.estimates.show', $estimate));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->where('estimate.id', $estimate->id));
    }

    public function test_estimate_can_be_converted_to_invoice(): void
    {
        $this->actingAsManager();
        $estimate = $this->makeEstimate(['status' => 'accepted']);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.estimates.convert', $estimate));

        $response->assertRedirect();
        $this->assertDatabaseHas('invoices', ['client_id' => $estimate->client_id]);
    }

    public function test_estimate_creation_requires_client(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.estimates.store'), [
                'number' => 'EST/2026/999',
                'status' => 'draft',
                'currency' => 'PLN',
                'subtotal' => 100,
                'total' => 100,
                'issue_date' => now()->toDateString(),
            ]);

        $response->assertSessionHasErrors('client_id');
    }
}
