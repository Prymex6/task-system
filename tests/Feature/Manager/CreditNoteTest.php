<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\CreditNote;
use App\Models\Tenant\Invoice;
use Tests\TenantTestCase;

/**
 * Credit notes against an issued invoice.
 *
 * The screen for these was built and the controller was one stub with no
 * route at all, so the page it posts to did not exist. What it needed on top
 * of the usual round trip is the rule that a credit note cannot take more
 * off an invoice than is left on it.
 */
class CreditNoteTest extends TenantTestCase
{
    private function invoice(float $total = 1000): Invoice
    {
        return Invoice::factory()->create(['total' => $total, 'status' => 'sent']);
    }

    public function test_the_page_lists_notes_and_the_invoices_they_can_be_written_against(): void
    {
        $this->actingAsManager();
        $this->invoice();

        $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.credit-notes.index'))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('Tenant/Manager/Finance/CreditNotes/Index')
                ->has('creditNotes.data')
                ->has('invoices', 1)
            );
    }

    public function test_a_note_is_numbered_and_dated_when_it_is_written(): void
    {
        $this->actingAsManager();
        $invoice = $this->invoice();

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.credit-notes.store'), [
                'invoice_id' => $invoice->id,
                'amount' => 250,
                'reason' => 'Rabat po fakcie',
            ])
            ->assertRedirect();

        $note = CreditNote::sole();
        $this->assertSame(sprintf('KOR/%d/0001', now()->year), $note->number);
        $this->assertSame('250.00', (string) $note->amount);
        $this->assertSame(now()->toDateString(), $note->issue_date->toDateString());
    }

    public function test_a_note_cannot_take_more_off_than_the_invoice_is_worth(): void
    {
        $this->actingAsManager();
        $invoice = $this->invoice(1000);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.credit-notes.store'), ['invoice_id' => $invoice->id, 'amount' => 1500])
            ->assertSessionHasErrors('amount');

        $this->assertSame(0, CreditNote::count());
    }

    public function test_notes_already_written_count_against_what_is_left(): void
    {
        $this->actingAsManager();
        $invoice = $this->invoice(1000);

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.credit-notes.store'), ['invoice_id' => $invoice->id, 'amount' => 800])
            ->assertRedirect();

        // 800 is gone, so 300 is more than the 200 still standing.
        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.credit-notes.store'), ['invoice_id' => $invoice->id, 'amount' => 300])
            ->assertSessionHasErrors('amount');

        $this->assertSame(1, CreditNote::count());
    }

    public function test_the_pdf_downloads(): void
    {
        $user = $this->actingAsManager();
        $invoice = $this->invoice();
        $note = CreditNote::create([
            'invoice_id' => $invoice->id,
            'created_by' => $user->id,
            'number' => 'KOR/2026/0001',
            'amount' => 100,
            'issue_date' => now()->toDateString(),
        ]);

        $response = $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.credit-notes.pdf', $note->id));

        $response->assertStatus(200);
        $this->assertSame('application/pdf', $response->headers->get('content-type'));
    }
}
