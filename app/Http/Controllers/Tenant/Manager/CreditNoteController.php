<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\CreditNote;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\Setting;
use App\Models\Tenant\User;
use App\Services\AuditService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * Credit notes: the document that takes money back off an invoice already
 * issued.
 *
 * A note can never be for more than the invoice still stands at, counting
 * the notes already written against it — otherwise the two documents
 * together would say the client owes a negative amount.
 */
class CreditNoteController extends Controller
{
    public function index()
    {
        abort_unless(Auth::guard('tenant')->user(), 403);

        return Inertia::render('Tenant/Manager/Finance/CreditNotes/Index', [
            'creditNotes' => CreditNote::with('invoice.client')
                ->orderByDesc('issue_date')
                ->orderByDesc('id')
                ->paginate(25),
            'invoices' => Invoice::whereIn('status', ['sent', 'paid', 'partial'])
                ->orderByDesc('issue_date')
                ->get(['id', 'number', 'total']),
        ]);
    }

    public function store(Request $request)
    {
        $user = $this->admin();

        $validated = $request->validate([
            'invoice_id' => 'required|integer|exists:invoices,id',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'nullable|string|max:1000',
        ]);

        $invoice = Invoice::findOrFail($validated['invoice_id']);
        $alreadyCredited = (float) CreditNote::where('invoice_id', $invoice->id)->sum('amount');
        $remaining = round((float) $invoice->total - $alreadyCredited, 2);

        if ($validated['amount'] > $remaining) {
            return back()->withErrors([
                'amount' => __('messages.credit_note_exceeds_invoice', ['amount' => number_format($remaining, 2, ',', ' ')]),
            ]);
        }

        $note = CreditNote::create([
            ...$validated,
            'number' => $this->nextNumber(),
            'issue_date' => now()->toDateString(),
            'created_by' => $user->id,
        ]);

        AuditService::log('credit_note_created', $note, [], $note->only(['number', 'amount']));

        return back()->with('success', __('messages.credit_note_issued'));
    }

    public function pdf(CreditNote $creditNote)
    {
        abort_unless(Auth::guard('tenant')->user(), 403);

        $creditNote->load('invoice.client');

        return Pdf::loadView('pdf.credit-note', ['note' => $creditNote])
            ->setPaper('a4')
            ->download('korekta-' . str_replace('/', '-', $creditNote->number) . '.pdf');
    }

    /**
     * Numbered in their own sequence, per year, alongside the invoices.
     */
    private function nextNumber(): string
    {
        $prefix = Setting::get('credit_note_prefix', 'KOR');
        $year = now()->year;
        $next = CreditNote::whereYear('issue_date', $year)->count() + 1;

        return sprintf('%s/%d/%04d', $prefix, $year, $next);
    }

    private function admin(): User
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user?->isAdmin(), 403);

        return $user;
    }
}
