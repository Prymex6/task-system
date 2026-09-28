<?php

namespace Tests\Unit\Services;

use App\Models\Tenant\Client;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\InvoicePayment;
use App\Services\PaymentService;
use Tests\TenantTestCase;

class PaymentServiceTest extends TenantTestCase
{
    private function makeSentInvoice(float $total = 1000.00): Invoice
    {
        return Invoice::factory()->create([
            'client_id' => Client::factory()->create()->id,
            'status' => 'sent',
            'total' => $total,
            'paid_amount' => 0,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
        ]);
    }

    public function test_record_payment_creates_payment_record(): void
    {
        $invoice = $this->makeSentInvoice();

        $payment = PaymentService::recordPayment($invoice, [
            'amount' => 500.00,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'bank_transfer',
        ]);

        $this->assertInstanceOf(InvoicePayment::class, $payment);
        $this->assertEquals(500.00, $payment->amount);
        $this->assertDatabaseHas('invoice_payments', [
            'invoice_id' => $invoice->id,
            'amount' => 500.00,
        ]);
    }

    public function test_record_full_payment_marks_invoice_as_paid(): void
    {
        $invoice = $this->makeSentInvoice(1000.00);

        PaymentService::recordPayment($invoice, [
            'amount' => 1000.00,
            'payment_date' => now()->toDateString(),
        ]);

        $this->assertEquals('paid', $invoice->fresh()->status);
        $this->assertNotNull($invoice->fresh()->paid_at);
    }

    public function test_record_partial_payment_marks_invoice_as_partial(): void
    {
        $invoice = $this->makeSentInvoice(1000.00);

        PaymentService::recordPayment($invoice, [
            'amount' => 400.00,
            'payment_date' => now()->toDateString(),
        ]);

        $this->assertEquals('partial', $invoice->fresh()->status);
    }

    public function test_multiple_payments_can_fully_pay_invoice(): void
    {
        $invoice = $this->makeSentInvoice(1000.00);

        PaymentService::recordPayment($invoice, [
            'amount' => 600.00,
            'payment_date' => now()->toDateString(),
        ]);
        PaymentService::recordPayment($invoice, [
            'amount' => 400.00,
            'payment_date' => now()->toDateString(),
        ]);

        $this->assertEquals('paid', $invoice->fresh()->status);
    }

    public function test_delete_payment_reverts_invoice_to_sent_when_no_payments_left(): void
    {
        $invoice = $this->makeSentInvoice(1000.00);
        $payment = PaymentService::recordPayment($invoice, [
            'amount' => 500.00,
            'payment_date' => now()->toDateString(),
        ]);

        PaymentService::deletePayment($payment);

        $this->assertDatabaseMissing('invoice_payments', ['id' => $payment->id]);
    }

    public function test_delete_partial_payment_recalculates_status(): void
    {
        $invoice = $this->makeSentInvoice(1000.00);
        $payment1 = PaymentService::recordPayment($invoice, ['amount' => 600.00, 'payment_date' => now()->toDateString()]);
        $payment2 = PaymentService::recordPayment($invoice, ['amount' => 400.00, 'payment_date' => now()->toDateString()]);

        $this->assertEquals('paid', $invoice->fresh()->status);

        PaymentService::deletePayment($payment2);

        $this->assertEquals('partial', $invoice->fresh()->status);
    }

    public function test_record_payment_stores_reference_and_notes(): void
    {
        $invoice = $this->makeSentInvoice();

        $payment = PaymentService::recordPayment($invoice, [
            'amount' => 200.00,
            'payment_date' => now()->toDateString(),
            'payment_method' => 'cash',
            'reference' => 'REF-2026-001',
            'notes' => 'Gotówka odebrana od klienta',
        ]);

        $this->assertEquals('REF-2026-001', $payment->reference);
        $this->assertEquals('cash', $payment->payment_method);
    }
}
