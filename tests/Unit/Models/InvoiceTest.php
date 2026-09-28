<?php

namespace Tests\Unit\Models;

use App\Models\Tenant\Client;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\InvoiceItem;
use App\Models\Tenant\InvoicePayment;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TenantTestCase;

class InvoiceTest extends TenantTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function test_it_belongs_to_client(): void
    {
        $client = Client::factory()->create();
        $invoice = Invoice::factory()->create(['client_id' => $client->id]);

        $this->assertInstanceOf(Client::class, $invoice->client);
        $this->assertEquals($client->id, $invoice->client->id);
    }

    /** @test */
    public function test_it_has_many_items(): void
    {
        $invoice = Invoice::factory()->create();

        InvoiceItem::factory()->count(3)->create(['invoice_id' => $invoice->id]);

        $this->assertCount(3, $invoice->items);
        $this->assertInstanceOf(InvoiceItem::class, $invoice->items->first());
    }

    /** @test */
    public function test_it_has_many_payments(): void
    {
        $invoice = Invoice::factory()->create(['status' => 'sent']);

        InvoicePayment::factory()->count(2)->create([
            'invoice_id' => $invoice->id,
            'amount' => 100.00,
            'payment_date' => now()->toDateString(),
        ]);

        $this->assertCount(2, $invoice->payments);
    }

    /** @test */
    public function test_it_calculates_total_correctly(): void
    {
        $invoice = Invoice::factory()->create([
            'subtotal' => 1000.00,
            'tax_amount' => 230.00,
            'discount_amount' => 0.00,
            'total' => 1230.00,
        ]);

        $this->assertEquals('1230.00', $invoice->total);
    }

    /** @test */
    public function test_it_calculates_balance_due(): void
    {
        $invoice = Invoice::factory()->create([
            'total' => 1230.00,
            'paid_amount' => 400.00,
        ]);

        $this->assertEquals(830.00, $invoice->balance_due);
    }

    /** @test */
    public function test_balance_due_is_zero_when_fully_paid(): void
    {
        $invoice = Invoice::factory()->create([
            'total' => 500.00,
            'paid_amount' => 500.00,
        ]);

        $this->assertEquals(0.0, $invoice->balance_due);
    }

    /** @test */
    public function test_it_can_be_marked_as_paid(): void
    {
        $invoice = Invoice::factory()->sent()->create([
            'total' => 1000.00,
            'paid_amount' => 0.00,
        ]);

        $invoice->update([
            'status' => 'paid',
            'paid_amount' => 1000.00,
            'paid_at' => now(),
        ]);

        $this->assertEquals('paid', $invoice->fresh()->status);
        $this->assertNotNull($invoice->fresh()->paid_at);
    }

    /** @test */
    public function test_scope_overdue_returns_past_due_unpaid_invoices(): void
    {
        Invoice::factory()->create([
            'status' => 'sent',
            'due_date' => now()->subDays(5)->toDateString(),
        ]);
        Invoice::factory()->create([
            'status' => 'paid',
            'due_date' => now()->subDays(5)->toDateString(),
        ]);
        Invoice::factory()->create([
            'status' => 'draft',
            'due_date' => now()->addDays(10)->toDateString(),
        ]);

        $overdue = Invoice::overdue()->get();

        $this->assertCount(1, $overdue);
        $this->assertEquals('sent', $overdue->first()->status);
    }

    /** @test */
    public function test_is_overdue_returns_false_for_paid_invoice(): void
    {
        $invoice = Invoice::factory()->paid()->create([
            'due_date' => now()->subDays(5)->toDateString(),
        ]);

        $this->assertFalse($invoice->isOverdue());
    }
}
