<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\InvoicePayment;
use App\Services\AuditService;
use App\Services\InvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Payments booked against an invoice.
 *
 * Adding or reversing one re-derives the invoice's own status, so the
 * paid/partial state always follows from the rows rather than being set by
 * hand and drifting.
 */
class InvoicePaymentController extends Controller
{
    public function store(Request $request, Invoice $invoice)
    {
        $this->authorizeManager();

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'payment_method_id' => 'nullable|integer|exists:payment_methods,id',
            'reference' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        $outstanding = round((float) $invoice->total - (float) $invoice->payments()->sum('amount'), 2);

        if ($validated['amount'] > $outstanding + 0.005) {
            return back()->withErrors([
                'amount' => __('messages.amount_exceeds_outstanding', ['amount' => number_format($outstanding, 2, ',', ' ')]),
            ]);
        }

        DB::transaction(function () use ($invoice, $validated) {
            $invoice->payments()->create([
                ...$validated,
                'currency' => $invoice->currency,
                'gateway' => 'manual',
            ]);

            InvoiceService::syncPaymentState($invoice->fresh());
        });

        AuditService::log('invoice_payment_recorded', $invoice, [], [
            'amount' => $validated['amount'],
            'invoice' => $invoice->number,
        ]);

        return back()->with('success', __('messages.payment_recorded'));
    }

    public function destroy(Invoice $invoice, InvoicePayment $payment)
    {
        $this->authorizeManager();
        abort_unless($payment->invoice_id === $invoice->id, 404);

        DB::transaction(function () use ($invoice, $payment) {
            $payment->delete();

            InvoiceService::syncPaymentState($invoice->fresh());
        });

        AuditService::log('invoice_payment_deleted', $invoice, ['amount' => $payment->amount], []);

        return back()->with('success', __('messages.payment_deleted'));
    }

    private function authorizeManager(): void
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user && ($user->isAdmin() || $user->isManager()), 403);
    }
}
