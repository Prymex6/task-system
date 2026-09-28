<?php

namespace Tests\Unit\Services;

use App\Models\Tenant\Client;
use App\Models\Tenant\Invoice;
use App\Services\InvoiceService;
use Tests\TenantTestCase;

class InvoiceServiceTest extends TenantTestCase
{
    private function makeInvoice(array $attrs = []): Invoice
    {
        return Invoice::factory()->create(array_merge([
            'client_id' => Client::factory()->create()->id,
            'status' => 'sent',
            'total' => 1230.00,
            'paid_amount' => 0,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
        ], $attrs));
    }

    public function test_calculate_totals_computes_net_tax_and_total(): void
    {
        $items = [
            ['quantity' => 2, 'unit_price' => 500.00, 'tax_rate' => 23, 'discount' => 0],
        ];

        $result = InvoiceService::calculateTotals($items);

        $this->assertEquals(1000.00, $result['subtotal']);
        $this->assertEquals(230.00, $result['tax']);
        $this->assertEquals(1230.00, $result['total']);
    }

    public function test_calculate_totals_applies_line_discount(): void
    {
        $items = [
            ['quantity' => 1, 'unit_price' => 1000.00, 'tax_rate' => 0, 'discount' => 10],
        ];

        $result = InvoiceService::calculateTotals($items);

        $this->assertEquals(900.00, $result['subtotal']);
        $this->assertEquals(900.00, $result['total']);
    }

    public function test_calculate_totals_applies_global_discount(): void
    {
        $items = [
            ['quantity' => 1, 'unit_price' => 1000.00, 'tax_rate' => 0, 'discount' => 0],
        ];

        $result = InvoiceService::calculateTotals($items, 10);

        $this->assertEquals(100.00, $result['discount']);
        $this->assertEquals(900.00, $result['total']);
    }

    public function test_calculate_totals_handles_multiple_items(): void
    {
        $items = [
            ['quantity' => 1, 'unit_price' => 500.00, 'tax_rate' => 23, 'discount' => 0],
            ['quantity' => 2, 'unit_price' => 250.00, 'tax_rate' => 23, 'discount' => 0],
        ];

        $result = InvoiceService::calculateTotals($items);

        $this->assertEquals(1000.00, $result['subtotal']);
        $this->assertEquals(1230.00, $result['total']);
    }

    public function test_mark_as_sent_changes_draft_to_sent(): void
    {
        $invoice = Invoice::factory()->create(['status' => 'draft']);

        InvoiceService::markAsSent($invoice);

        $this->assertEquals('sent', $invoice->fresh()->status);
        $this->assertNotNull($invoice->fresh()->sent_at);
    }

    public function test_mark_as_sent_does_not_change_already_sent_invoice(): void
    {
        $sentAt = now()->subDay();
        $invoice = Invoice::factory()->create(['status' => 'sent', 'sent_at' => $sentAt]);

        InvoiceService::markAsSent($invoice);

        $this->assertEquals('sent', $invoice->fresh()->status);
    }

    public function test_mark_as_paid_sets_paid_status(): void
    {
        $invoice = $this->makeInvoice(['status' => 'sent']);

        InvoiceService::markAsPaid($invoice);

        $this->assertEquals('paid', $invoice->fresh()->status);
        $this->assertNotNull($invoice->fresh()->paid_at);
    }

    public function test_check_overdue_marks_past_due_invoices(): void
    {
        $invoice = Invoice::factory()->create([
            'status' => 'sent',
            'due_date' => now()->subDays(5)->toDateString(),
        ]);

        InvoiceService::checkOverdue();

        $this->assertEquals('overdue', $invoice->fresh()->status);
    }

    public function test_check_overdue_does_not_affect_future_due_invoices(): void
    {
        $invoice = Invoice::factory()->create([
            'status' => 'sent',
            'due_date' => now()->addDays(5)->toDateString(),
        ]);

        InvoiceService::checkOverdue();

        $this->assertEquals('sent', $invoice->fresh()->status);
    }

    public function test_generate_number_includes_current_year(): void
    {
        $number = InvoiceService::generateNumber();

        $this->assertStringContainsString((string) now()->year, $number);
    }
}
