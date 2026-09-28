<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Mail\InvoiceMail;
use App\Models\Tenant\Client;
use App\Models\Tenant\Currency;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\Project;
use App\Models\Tenant\TaxRate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with(['client', 'project']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('number', 'like', '%' . $request->search . '%')
                    ->orWhereHas('client', fn ($c) => $c->where('name', 'like', '%' . $request->search . '%'));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('overdue')) {
            $query->overdue();
        }

        $invoices = $query->latest('issue_date')->paginate(20)->withQueryString();

        $summary = [
            'total' => Invoice::sum('total'),
            'paid' => Invoice::where('status', 'paid')->sum('total'),
            'pending' => Invoice::whereIn('status', ['draft', 'sent'])->sum('total'),
            'overdue' => Invoice::overdue()->sum('total'),
        ];

        return Inertia::render('Tenant/Manager/Finance/Invoices/Index', [
            'invoices' => $invoices,
            'summary' => $summary,
            'clients' => Client::where('is_active', true)->orderBy('name')->get(['id', 'name', 'company_name']),
            'filters' => $request->only(['search', 'status', 'client_id', 'overdue']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Tenant/Manager/Finance/Invoices/Form', [
            'clients' => Client::where('is_active', true)->orderBy('name')->get(['id', 'name', 'company_name', 'address', 'city', 'nip', 'currency']),
            'projects' => Project::where('is_archived', false)->orderBy('name')->get(['id', 'name', 'client_id']),
            'taxRates' => TaxRate::where('is_active', true)->orderBy('rate')->get(),
            'currencies' => Currency::where('is_active', true)->get(['code', 'name', 'symbol']),
            'nextNumber' => $this->generateNumber(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'number' => 'required|string|max:50|unique:invoices,number',
            'client_id' => 'required|exists:clients,id',
            'project_id' => 'nullable|exists:projects,id',
            'status' => 'required|in:draft,sent,paid,partial,overdue,cancelled',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'currency' => 'required|string|max:5',
            'discount' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:2000',
            'footer' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $items = collect($validated['items'])->map(function ($item) {
            $subtotal = $item['quantity'] * $item['unit_price'];
            $tax = $subtotal * (($item['tax_rate'] ?? 0) / 100);

            return [
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'tax_rate' => $item['tax_rate'] ?? 0,
                'discount_percent' => $item['discount_percent'] ?? 0,
                'total' => round($subtotal + $tax, 2),
                '_subtotal' => round($subtotal, 2),
                '_tax' => round($tax, 2),
            ];
        });

        $subtotal = $items->sum('_subtotal');
        $taxTotal = $items->sum('_tax');
        $discount = $validated['discount'] ?? 0;
        $total = round(($subtotal + $taxTotal) * (1 - $discount / 100), 2);

        $invoice = Invoice::create(array_merge(
            collect($validated)->except('items')->toArray(),
            [
                'subtotal' => $subtotal,
                'tax_amount' => $taxTotal,
                'total' => $total,
                'paid_amount' => 0,
                'created_by' => Auth::guard('tenant')->id(),
            ]
        ));

        foreach ($items as $i => $item) {
            $invoice->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'tax_rate' => $item['tax_rate'],
                'discount_percent' => $item['discount_percent'],
                'total' => $item['total'],
                'order' => $i + 1,
            ]);
        }

        return redirect()->route('tenant.manager.invoices.show', $invoice)
            ->with('success', __('messages.invoice_created'));
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['client', 'project', 'items', 'payments', 'creator']);

        return Inertia::render('Tenant/Manager/Finance/Invoices/Show', [
            'invoice' => $invoice,
        ]);
    }

    public function edit(Invoice $invoice)
    {
        abort_unless(in_array($invoice->status, ['draft', 'sent']), 403);

        return Inertia::render('Tenant/Manager/Finance/Invoices/Form', [
            'invoice' => $invoice->load('items'),
            'clients' => Client::where('is_active', true)->orderBy('name')->get(['id', 'name', 'company_name', 'address', 'city', 'nip', 'currency']),
            'projects' => Project::where('is_archived', false)->orderBy('name')->get(['id', 'name', 'client_id']),
            'taxRates' => TaxRate::where('is_active', true)->orderBy('rate')->get(),
            'currencies' => Currency::where('is_active', true)->get(['code', 'name', 'symbol']),
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        abort_unless(in_array($invoice->status, ['draft', 'sent']), 403);

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'project_id' => 'nullable|exists:projects,id',
            'status' => 'required|in:draft,sent,paid,partial,overdue,cancelled',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'currency' => 'required|string|max:5',
            'discount' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:2000',
            'footer' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $items = collect($validated['items'])->map(function ($item) {
            $sub = round($item['quantity'] * $item['unit_price'], 2);
            $tax = round($sub * (($item['tax_rate'] ?? 0) / 100), 2);

            return [
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'tax_rate' => $item['tax_rate'] ?? 0,
                'discount_percent' => $item['discount_percent'] ?? 0,
                'total' => round($sub + $tax, 2),
                '_sub' => $sub,
                '_tax' => $tax,
            ];
        });
        $subtotal = $items->sum('_sub');
        $taxTotal = $items->sum('_tax');
        $discount = $validated['discount'] ?? 0;
        $total = round(($subtotal + $taxTotal) * (1 - $discount / 100), 2);
        $paid = $invoice->payments()->sum('amount');

        $invoice->update(array_merge(
            collect($validated)->except('items')->toArray(),
            ['subtotal' => $subtotal, 'tax_amount' => $taxTotal, 'total' => $total, 'paid_amount' => $paid]
        ));

        $invoice->items()->delete();
        foreach ($items as $i => $item) {
            $invoice->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'tax_rate' => $item['tax_rate'],
                'discount_percent' => $item['discount_percent'],
                'total' => $item['total'],
                'order' => $i + 1,
            ]);
        }

        return back()->with('success', __('messages.invoice_updated'));
    }

    public function destroy(Invoice $invoice)
    {
        abort_unless($invoice->status === 'draft', 403, __('messages.only_draft_invoices_deletable'));
        $invoice->delete();

        return redirect()->route('tenant.manager.invoices.index')
            ->with('success', __('messages.invoice_deleted'));
    }

    /**
     * Records a payment against the invoice.
     */
    public function recordPayment(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $invoice->balance_due,
            'payment_date' => 'required|date',
            'payment_method' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:500',
        ]);

        $invoice->payments()->create($validated);

        $totalPaid = $invoice->payments()->sum('amount');
        $balanceDue = max(0, $invoice->total - $totalPaid);

        $invoice->update([
            'balance_due' => $balanceDue,
            'status' => $balanceDue <= 0 ? 'paid' : 'partial',
            'paid_at' => $balanceDue <= 0 ? now() : null,
        ]);

        return back()->with('success', __('messages.payment_recorded'));
    }

    /**
     * Pobiera PDF faktury.
     */
    public function pdf(Invoice $invoice)
    {
        $invoice->load(['client', 'items', 'payments', 'project']);

        $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $invoice])
            ->setPaper('a4');

        return $pdf->download('faktura-' . $invoice->number . '.pdf');
    }

    /**
     * E-mails the invoice to the client.
     */
    public function send(Invoice $invoice)
    {
        abort_unless($invoice->client?->email, 422, __('messages.client_has_no_email'));

        Mail::to($invoice->client->email)->queue(new InvoiceMail($invoice));

        // sent_at is what the overdue check reads, so it has to move with the status.
        $invoice->update([
            'status' => $invoice->status === 'draft' ? 'sent' : $invoice->status,
            'sent_at' => now(),
        ]);

        return back()->with('success', __('messages.invoice_sent'));
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function generateNumber(): string
    {
        $year = now()->year;
        $month = now()->format('m');
        $count = Invoice::whereYear('created_at', $year)->whereMonth('created_at', now()->month)->count() + 1;

        return sprintf('FV/%d/%s/%03d', $year, $month, $count);
    }
}
