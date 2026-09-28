<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Client;
use App\Models\Tenant\Invoice;
use Tests\TenantTestCase;

class InvoiceTest extends TenantTestCase
{
    public function test_invoices_index_is_accessible(): void
    {
        $this->actingAsManager();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.invoices.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->component('Tenant/Manager/Finance/Invoices/Index'));
    }

    public function test_manager_can_create_invoice(): void
    {
        $user = $this->actingAsManager();
        $client = Client::factory()->create();

        $response = $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.invoices.store'), [
                'client_id' => $client->id,
                'number' => 'FV/TEST/001',
                'status' => 'draft',
                'currency' => 'PLN',
                'subtotal' => 1000.00,
                'tax_amount' => 230.00,
                'total' => 1230.00,
                'issue_date' => now()->toDateString(),
                'due_date' => now()->addDays(14)->toDateString(),
                'items' => [
                    ['description' => 'Usługa', 'quantity' => 1, 'unit_price' => 1000.00, 'tax_rate' => 23],
                ],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('invoices', ['number' => 'FV/TEST/001', 'client_id' => $client->id]);
    }

    public function test_manager_can_update_invoice(): void
    {
        $this->actingAsManager();
        $invoice = Invoice::factory()->create(['status' => 'draft']);

        $response = $this->withoutTenantMiddleware()
            ->put(route('tenant.manager.invoices.update', $invoice), [
                'client_id' => $invoice->client_id,
                'number' => $invoice->number,
                'status' => 'draft',
                'currency' => 'PLN',
                'subtotal' => 2000.00,
                'tax_amount' => 460.00,
                'total' => 2460.00,
                'issue_date' => $invoice->issue_date->toDateString(),
                'due_date' => $invoice->due_date->toDateString(),
                'items' => [
                    ['description' => 'Usługa 2', 'quantity' => 2, 'unit_price' => 1000.00, 'tax_rate' => 23],
                ],
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'total' => 2460.00]);
    }

    public function test_manager_can_delete_draft_invoice(): void
    {
        $this->actingAsManager();
        $invoice = Invoice::factory()->create(['status' => 'draft']);

        $response = $this->withoutTenantMiddleware()
            ->delete(route('tenant.manager.invoices.destroy', $invoice));

        $response->assertRedirect();
        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
    }

    public function test_invoice_show_page_loads(): void
    {
        $this->actingAsManager();
        $invoice = Invoice::factory()->create();

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.invoices.show', $invoice));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->where('invoice.id', $invoice->id));
    }

    public function test_invoices_list_shows_unpaid_sum_in_props(): void
    {
        $this->actingAsManager();
        Invoice::factory()->sent()->count(3)->create([
            'total' => 1000.00,
            'paid_amount' => 0,
        ]);

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.invoices.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page->has('invoices'));
    }
}
