<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Client;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\InvoicePayment;
use Tests\TenantTestCase;

/**
 * Payments booked against an invoice.
 *
 * The invoice's own paid/partial state is derived from these rows, so the
 * interesting cases are the ones where a payment is added, reversed, or would
 * take the invoice past its total.
 */
class InvoicePaymentTest extends TenantTestCase
{
    private function invoice(float $total = 1000.0): Invoice
    {
        $this->actingAsManager();

        return Invoice::factory()->create([
            'client_id' => Client::factory()->create()->id,
            'total' => $total,
            'paid_amount' => 0,
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function test_recording_the_full_amount_marks_the_invoice_paid(): void
    {
        $invoice = $this->invoice(1000);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.invoices.payment', $invoice), [
                'amount' => 1000,
                'payment_date' => '2026-01-15',
            ])
            ->assertRedirect();

        $fresh = $invoice->fresh();
        $this->assertSame('paid', $fresh->status);
        $this->assertEquals(1000, $fresh->paid_amount);
        $this->assertNotNull($fresh->paid_at);
    }

    public function test_a_part_payment_leaves_the_invoice_partial(): void
    {
        $invoice = $this->invoice(1000);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.invoices.payment', $invoice), [
                'amount' => 400,
                'payment_date' => '2026-01-15',
            ]);

        $fresh = $invoice->fresh();
        $this->assertSame('partial', $fresh->status);
        $this->assertEquals(400, $fresh->paid_amount);
        $this->assertNull($fresh->paid_at);
    }

    public function test_a_payment_beyond_the_outstanding_amount_is_rejected(): void
    {
        $invoice = $this->invoice(1000);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.invoices.payment', $invoice), [
                'amount' => 1500,
                'payment_date' => '2026-01-15',
            ])
            ->assertSessionHasErrors('amount');

        $this->assertDatabaseCount('invoice_payments', 0);
    }

    public function test_the_outstanding_amount_accounts_for_earlier_payments(): void
    {
        $invoice = $this->invoice(1000);
        $route = route('tenant.manager.invoices.payment', $invoice);

        $this->withoutTenantMiddleware()->post($route, ['amount' => 700, 'payment_date' => '2026-01-15']);

        $this->withoutTenantMiddleware()
            ->post($route, ['amount' => 500, 'payment_date' => '2026-01-20'])
            ->assertSessionHasErrors('amount');

        $this->assertEquals(700, $invoice->fresh()->paid_amount);
    }

    public function test_removing_a_payment_walks_the_invoice_back_out_of_paid(): void
    {
        $invoice = $this->invoice(1000);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.invoices.payment', $invoice), [
                'amount' => 1000,
                'payment_date' => '2026-01-15',
            ]);

        $payment = InvoicePayment::firstWhere('invoice_id', $invoice->id);

        $this->withoutTenantMiddleware()
            ->delete(route('tenant.manager.invoices.payments.destroy', [$invoice, $payment]))
            ->assertRedirect();

        $fresh = $invoice->fresh();
        $this->assertSame('sent', $fresh->status);
        $this->assertEquals(0, $fresh->paid_amount);
        $this->assertNull($fresh->paid_at);
    }

    public function test_a_payment_from_another_invoice_is_not_found(): void
    {
        $invoice = $this->invoice();
        $other = $this->invoice();

        $payment = InvoicePayment::create([
            'invoice_id' => $other->id,
            'amount' => 100,
            'payment_date' => '2026-01-15',
            'currency' => 'PLN',
        ]);

        $this->withoutTenantMiddleware()
            ->delete(route('tenant.manager.invoices.payments.destroy', [$invoice, $payment]))
            ->assertNotFound();
    }

    public function test_payment_requires_an_amount_above_zero(): void
    {
        $invoice = $this->invoice();

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.invoices.payment', $invoice), [
                'amount' => 0,
                'payment_date' => '2026-01-15',
            ])
            ->assertSessionHasErrors('amount');
    }
}
