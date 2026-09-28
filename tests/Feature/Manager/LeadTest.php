<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Lead;
use Tests\TenantTestCase;

class LeadTest extends TenantTestCase
{
    private function makeLead(array $attrs = []): Lead
    {
        return Lead::create(array_merge([
            'company_name' => 'Testowa Firma Sp. z o.o.',
            'contact_name' => 'Adam Wiśniewski',
            'email' => fake()->unique()->safeEmail(),
            'status' => 'new',
            'currency' => 'PLN',
        ], $attrs));
    }

    public function test_leads_index_is_accessible(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.leads.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/CRM/Leads/Index'));
    }

    public function test_manager_can_create_lead(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.leads.store'), [
                'company_name' => 'Nowy Potencjalny Klient Sp. z o.o.',
                'contact_name' => 'Janusz Przykład',
                'email' => 'janusz@przyklad.pl',
                'status' => 'new',
                'currency' => 'PLN',
                'source' => 'website',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('leads', [
            'email' => 'janusz@przyklad.pl',
            'company_name' => 'Nowy Potencjalny Klient Sp. z o.o.',
        ]);
    }

    public function test_manager_can_update_lead(): void
    {
        $this->actingAsManager();
        $lead = $this->makeLead(['status' => 'new']);

        $response = $this->withoutTenantMiddleware()
            ->put(route('tenant.manager.leads.update', $lead), [
                'company_name' => 'Zaktualizowana Firma',
                'status' => 'contacted',
                'currency' => 'PLN',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'company_name' => 'Zaktualizowana Firma',
            'status' => 'contacted',
        ]);
    }

    public function test_manager_can_delete_lead(): void
    {
        $this->actingAsManager();
        $lead = $this->makeLead();

        $response = $this->withoutTenantMiddleware()
            ->delete(route('tenant.manager.leads.destroy', $lead));

        $response->assertRedirect();
        $this->assertDatabaseMissing('leads', ['id' => $lead->id]);
    }

    public function test_lead_show_page_loads(): void
    {
        $this->actingAsManager();
        $lead = $this->makeLead();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.leads.show', $lead));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->where('lead.id', $lead->id));
    }

    public function test_lead_can_be_converted_to_client(): void
    {
        $this->actingAsManager();
        $lead = $this->makeLead(['status' => 'qualified']);

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.leads.convert', $lead), [
                'company_name' => $lead->company_name,
                'currency' => 'PLN',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'status' => 'converted']);
        $this->assertDatabaseHas('clients', ['company_name' => $lead->company_name]);
    }

    public function test_lead_creation_requires_company_name(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.leads.store'), [
                'email' => 'test@test.pl',
                'status' => 'new',
                'currency' => 'PLN',
            ]);

        $response->assertSessionHasErrors('company_name');
    }

    public function test_leads_filtered_by_status(): void
    {
        $this->actingAsManager();
        $this->makeLead(['status' => 'new']);
        $this->makeLead(['status' => 'qualified']);

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.leads.index', ['status' => 'new']));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->has('leads'));
    }
}
