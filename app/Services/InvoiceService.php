<?php

namespace App\Services;

use App\Models\Tenant\Invoice;
use App\Models\Tenant\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;

class InvoiceService
{
    public static function generateNumber(): string
    {
        $prefix = Setting::get('invoice_prefix', 'FV');
        $year = now()->year;
        $last = Invoice::whereYear('issue_date', $year)->count() + 1;

        return sprintf('%s/%d/%04d', $prefix, $year, $last);
    }

    public static function calculateTotals(array $items, float $globalDiscount = 0): array
    {
        $subtotal = 0;
        $taxTotal = 0;

        $calculatedItems = array_map(function ($item) {
            $qty = (float) ($item['quantity'] ?? 1);
            $price = (float) ($item['unit_price'] ?? 0);
            $tax = (float) ($item['tax_rate'] ?? 0);
            $disc = (float) ($item['discount'] ?? 0);

            $lineNet = $qty * $price * (1 - $disc / 100);
            $lineTax = $lineNet * $tax / 100;
            $lineTotal = $lineNet + $lineTax;

            return array_merge($item, [
                'subtotal' => round($lineNet, 2),
                'tax' => round($lineTax, 2),
                'total' => round($lineTotal, 2),
            ]);
        }, $items);

        foreach ($calculatedItems as $item) {
            $subtotal += $item['subtotal'];
            $taxTotal += $item['tax'];
        }

        $discount = $subtotal * $globalDiscount / 100;
        $total = $subtotal + $taxTotal - $discount;

        return [
            'items' => $calculatedItems,
            'subtotal' => round($subtotal, 2),
            'tax' => round($taxTotal, 2),
            'discount' => round($discount, 2),
            'total' => round($total, 2),
        ];
    }

    public static function generatePdf(Invoice $invoice): string
    {
        $invoice->load(['client', 'items', 'payments']);

        $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $invoice]);
        $pdf->setPaper('A4', 'portrait');

        $filename = 'invoice_' . Str::slug($invoice->number) . '.pdf';
        $path = storage_path('app/invoices/' . $filename);

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0775, true);
        }

        $pdf->save($path);

        return $path;
    }

    public static function markAsSent(Invoice $invoice): void
    {
        if ($invoice->status === 'draft') {
            $invoice->update(['status' => 'sent', 'sent_at' => now()]);
        }
    }

    public static function markAsPaid(Invoice $invoice): void
    {
        $invoice->update([
            'status' => 'paid',
            'paid_amount' => $invoice->total,
            'paid_at' => now(),
        ]);
    }

    /**
     * Recalculate what an invoice is owed from the payments recorded against
     * it. Called after a payment is added or removed, so a reversed payment
     * walks the invoice back out of `paid` instead of stranding it there.
     */
    public static function syncPaymentState(Invoice $invoice): void
    {
        $paid = (float) $invoice->payments()->sum('amount');
        $total = (float) $invoice->total;

        // Rounding on decimal(12,2) sums: treat a sub-grosz gap as settled.
        $settled = $paid >= $total - 0.005;

        $status = match (true) {
            $settled => 'paid',
            $paid > 0 => 'partial',
            $invoice->sent_at !== null => 'sent',
            default => 'draft',
        };

        if (!$settled && $status !== 'draft' && $invoice->isOverdue()) {
            $status = 'overdue';
        }

        $invoice->update([
            'paid_amount' => $paid,
            'status' => $status,
            'paid_at' => $settled ? ($invoice->paid_at ?? now()) : null,
        ]);
    }

    public static function checkOverdue(): void
    {
        Invoice::whereIn('status', ['sent', 'partial'])
            ->whereNotNull('due_date')
            ->where('due_date', '<', today())
            ->update(['status' => 'overdue']);
    }
}
