<?php

namespace App\Services;

use App\Models\Tenant\Invoice;
use App\Models\Tenant\InvoicePayment;

class PaymentService
{
    public static function recordPayment(Invoice $invoice, array $data): InvoicePayment
    {
        $payment = $invoice->payments()->create([
            'amount' => $data['amount'],
            'payment_date' => $data['payment_date'] ?? today(),
            'payment_method' => $data['payment_method'] ?? 'bank_transfer',
            'reference' => $data['reference'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        static::updateInvoiceStatus($invoice);
        AuditService::log('invoice.payment_recorded', $invoice, [], ['amount' => $data['amount']]);

        return $payment;
    }

    public static function updateInvoiceStatus(Invoice $invoice): void
    {
        $invoice->refresh();
        $paid = (float) $invoice->payments()->sum('amount');
        $total = (float) $invoice->total;

        if ($paid <= 0) {
            return;
        }

        if ($paid >= $total) {
            $invoice->update(['status' => 'paid', 'paid_at' => now()]);
        } else {
            $invoice->update(['status' => 'partial']);
        }
    }

    public static function deletePayment(InvoicePayment $payment): void
    {
        $invoice = $payment->invoice;
        $payment->delete();
        static::updateInvoiceStatus($invoice);
    }
}
