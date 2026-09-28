<?php

namespace App\Services;

use App\Models\Tenant\Invoice;
use App\Models\Tenant\RecurringInvoice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Turns recurring-invoice schedules into real invoices.
 *
 * A schedule carries its line items in `template_data` rather than in a child
 * table, so the invoice it produces is a snapshot: editing the schedule later
 * leaves everything already issued untouched.
 */
class RecurringInvoiceService
{
    /**
     * Issue every schedule that has come due. Run from the scheduler.
     */
    public static function processDue(): int
    {
        $due = RecurringInvoice::where('is_active', true)
            ->whereDate('next_date', '<=', today())
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhereDate('ends_at', '>=', today()))
            ->get();

        foreach ($due as $recurring) {
            static::generateInvoice($recurring);
        }

        return $due->count();
    }

    public static function generateInvoice(RecurringInvoice $recurring): Invoice
    {
        $template = $recurring->template_data ?? [];
        $items = $template['items'] ?? [];

        $totals = InvoiceService::calculateTotals($items, (float) ($template['discount'] ?? 0));

        return DB::transaction(function () use ($recurring, $template, $totals) {
            $invoice = Invoice::create([
                'client_id' => $recurring->client_id,
                'created_by' => $recurring->created_by,
                'number' => InvoiceService::generateNumber(),
                'status' => 'draft',
                'currency' => $template['currency'] ?? 'PLN',
                'issue_date' => today(),
                'due_date' => today()->addDays((int) ($template['payment_days'] ?? 14)),
                'subtotal' => $totals['subtotal'],
                'tax_amount' => $totals['tax'],
                'discount_amount' => $totals['discount'],
                'total' => $totals['total'],
                'notes' => $template['notes'] ?? null,
            ]);

            foreach ($totals['items'] as $order => $item) {
                $invoice->items()->create([
                    'description' => $item['description'] ?? '',
                    'quantity' => $item['quantity'] ?? 1,
                    'unit' => $item['unit'] ?? null,
                    'unit_price' => $item['unit_price'] ?? 0,
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'discount_percent' => $item['discount'] ?? 0,
                    'total' => $item['total'],
                    'order' => $order,
                ]);
            }

            $recurring->update(['next_date' => static::nextDate($recurring)]);

            return $invoice;
        });
    }

    /**
     * Step forward from the date that was due, not from today, so a schedule
     * that ran late does not quietly drift off its billing day.
     */
    public static function nextDate(RecurringInvoice $recurring): string
    {
        $from = Carbon::parse($recurring->next_date);
        $every = max(1, (int) $recurring->interval);

        $next = match ($recurring->frequency) {
            'weekly' => $from->addWeeks($every),
            'quarterly' => $from->addMonths(3 * $every),
            'yearly' => $from->addYears($every),
            default => $from->addMonths($every),
        };

        return $next->toDateString();
    }
}
