<?php

namespace Tests\Feature\Manager;

use App\Models\Tenant\Client;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\RecurringInvoice;
use App\Services\RecurringInvoiceService;
use Illuminate\Support\Carbon;
use Tests\TenantTestCase;

/**
 * Standing orders that issue invoices on a schedule.
 *
 * The service behind this was written against columns the table never had, so
 * the whole feature threw on the first query; these cover the shape it should
 * have had.
 */
class RecurringInvoiceTest extends TenantTestCase
{
    /**
     * @param array<string, mixed> $overrides
     */
    private function schedule(array $overrides = []): RecurringInvoice
    {
        $user = $this->actingAsManager();

        return RecurringInvoice::create([
            'client_id' => Client::factory()->create()->id,
            'created_by' => $user->id,
            'title' => 'Hosting',
            'frequency' => 'monthly',
            'interval' => 1,
            'next_date' => today()->toDateString(),
            'is_active' => true,
            'template_data' => [
                'currency' => 'PLN',
                'payment_days' => 14,
                'items' => [
                    ['description' => 'Utrzymanie serwera', 'quantity' => 1, 'unit_price' => 500, 'tax_rate' => 23],
                ],
            ],
            ...$overrides,
        ]);
    }

    public function test_manager_can_create_a_schedule(): void
    {
        $this->actingAsManager();

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.recurring-invoices.store'), [
                'title' => 'Abonament',
                'frequency' => 'monthly',
                'interval' => 1,
                'next_date' => '2026-02-01',
                'template_data' => [
                    'items' => [['description' => 'Opieka', 'quantity' => 1, 'unit_price' => 300, 'tax_rate' => 23]],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('recurring_invoices', ['title' => 'Abonament']);
    }

    public function test_a_schedule_needs_at_least_one_line(): void
    {
        $this->actingAsManager();

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.recurring-invoices.store'), [
                'title' => 'Puste',
                'frequency' => 'monthly',
                'interval' => 1,
                'next_date' => '2026-02-01',
                'template_data' => ['items' => []],
            ])
            ->assertSessionHasErrors('template_data.items');
    }

    public function test_generating_produces_an_invoice_with_the_template_lines(): void
    {
        $schedule = $this->schedule();

        $invoice = RecurringInvoiceService::generateInvoice($schedule);

        $this->assertSame('draft', $invoice->status);
        $this->assertEquals(500, $invoice->subtotal);
        $this->assertEquals(115, $invoice->tax_amount);
        $this->assertEquals(615, $invoice->total);
        $this->assertSame('Utrzymanie serwera', $invoice->items->first()->description);
    }

    public function test_generating_moves_the_schedule_to_its_next_date(): void
    {
        $schedule = $this->schedule(['next_date' => '2026-01-10']);

        RecurringInvoiceService::generateInvoice($schedule);

        $this->assertSame('2026-02-10', $schedule->fresh()->next_date->toDateString());
    }

    public function test_the_next_date_steps_from_the_due_date_not_from_today(): void
    {
        // A schedule that ran late must not drift onto the day it was caught up.
        $schedule = $this->schedule(['next_date' => '2026-01-10', 'frequency' => 'monthly']);

        Carbon::setTestNow('2026-01-27');
        RecurringInvoiceService::generateInvoice($schedule);
        Carbon::setTestNow();

        $this->assertSame('2026-02-10', $schedule->fresh()->next_date->toDateString());
    }

    public function test_the_interval_multiplies_the_frequency(): void
    {
        $schedule = $this->schedule(['next_date' => '2026-01-10', 'frequency' => 'quarterly', 'interval' => 2]);

        RecurringInvoiceService::generateInvoice($schedule);

        $this->assertSame('2026-07-10', $schedule->fresh()->next_date->toDateString());
    }

    public function test_processing_picks_up_only_schedules_that_are_due_and_active(): void
    {
        $this->schedule(['next_date' => today()->toDateString()]);
        $this->schedule(['next_date' => today()->addWeek()->toDateString()]);
        $this->schedule(['next_date' => today()->toDateString(), 'is_active' => false]);
        $this->schedule(['next_date' => today()->toDateString(), 'ends_at' => today()->subDay()->toDateString()]);

        $issued = RecurringInvoiceService::processDue();

        $this->assertSame(1, $issued);
        $this->assertSame(1, Invoice::count());
    }

    public function test_manager_can_generate_one_early_from_the_screen(): void
    {
        $schedule = $this->schedule();

        $this->withoutTenantMiddleware()
            ->post(route('tenant.manager.recurring-invoices.generate', $schedule))
            ->assertRedirect();

        $this->assertSame(1, Invoice::count());
    }

    public function test_a_plain_member_cannot_reach_the_list(): void
    {
        $this->actingAsManager(['workspace_role' => 'member']);

        $this->withoutTenantMiddleware()
            ->get(route('tenant.manager.recurring-invoices.index'))
            ->assertForbidden();
    }
}
