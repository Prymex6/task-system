<?php

namespace Tests\Feature\Client;

use App\Models\Tenant\Invoice;
use Tests\TenantTestCase;

class PortalInvoiceTest extends TenantTestCase
{
    public function test_client_can_view_own_invoices(): void
    {
        $contact = $this->actingAsClient();
        Invoice::factory()->count(3)->create(['client_id' => $contact->client_id]);

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.portal.invoices'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Tenant/Client/Invoices/Index')
            ->has('invoices.data', 3)
        );
    }

    public function test_client_can_view_own_invoice_detail(): void
    {
        $contact = $this->actingAsClient();
        $invoice = Invoice::factory()->create(['client_id' => $contact->client_id]);

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.portal.invoices.show', $invoice));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->where('invoice.id', $invoice->id));
    }

    public function test_client_cannot_view_other_clients_invoice(): void
    {
        $this->actingAsClient();
        $otherInvoice = Invoice::factory()->create(); // belongs to different client

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.portal.invoices.show', $otherInvoice));

        $response->assertStatus(403);
    }

    public function test_client_cannot_see_invoices_of_other_clients_in_list(): void
    {
        $contact = $this->actingAsClient();
        Invoice::factory()->count(2)->create(['client_id' => $contact->client_id]);
        Invoice::factory()->count(3)->create(); // other clients

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.portal.invoices'));

        $response->assertInertia(fn ($page) => $page->has('invoices.data', 2));
    }
}
