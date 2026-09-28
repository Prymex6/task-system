<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Client;
use App\Models\Tenant\Currency;
use App\Models\Tenant\Estimate;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\TaxRate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class EstimateController extends Controller
{
    public function index(Request $request)
    {
        $query = Estimate::with('client');

        if ($request->filled('search')) {
            $query->where('number', 'like', '%' . $request->search . '%')
                ->orWhereHas('client', fn ($c) => $c->where('name', 'like', '%' . $request->search . '%'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $estimates = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Tenant/Manager/Finance/Estimates/Index', [
            'estimates' => $estimates,
            'filters' => $request->only(['search', 'status', 'client_id']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Tenant/Manager/Finance/Estimates/Form', [
            'clients' => Client::where('is_active', true)->orderBy('name')->get(['id', 'name', 'company_name', 'currency']),
            'taxRates' => TaxRate::where('is_active', true)->orderBy('rate')->get(),
            'currencies' => Currency::where('is_active', true)->get(['code', 'name', 'symbol']),
            'nextNumber' => $this->generateNumber(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'number' => 'required|string|max:50|unique:estimates,number',
            'client_id' => 'required|exists:clients,id',
            'status' => 'required|in:draft,sent,accepted,rejected,expired',
            'issue_date' => 'required|date',
            'valid_until' => 'nullable|date|after_or_equal:issue_date',
            'currency' => 'required|string|max:5',
            'discount' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $items = $this->computeItems($validated['items']);
        $totals = $this->computeTotals($items, $validated['discount'] ?? 0);

        $estimate = Estimate::create(array_merge(
            collect($validated)->except('items')->toArray(),
            $totals,
            ['created_by' => Auth::guard('tenant')->id()]
        ));

        foreach ($items as $i => $item) {
            $estimate->items()->create(array_merge($item, ['order' => $i + 1]));
        }

        return redirect()->route('tenant.manager.estimates.show', $estimate)
            ->with('success', __('messages.estimate_created'));
    }

    public function show(Estimate $estimate)
    {
        $estimate->load(['client', 'items', 'creator']);

        return Inertia::render('Tenant/Manager/Finance/Estimates/Show', [
            'estimate' => $estimate,
        ]);
    }

    public function update(Request $request, Estimate $estimate)
    {
        abort_unless(in_array($estimate->status, ['draft', 'sent']), 403);

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'status' => 'required|in:draft,sent,accepted,rejected,expired',
            'issue_date' => 'required|date',
            'valid_until' => 'nullable|date',
            'currency' => 'required|string|max:5',
            'discount' => 'nullable|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:2000',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:500',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $items = $this->computeItems($validated['items']);
        $totals = $this->computeTotals($items, $validated['discount'] ?? 0);

        $estimate->update(array_merge(collect($validated)->except('items')->toArray(), $totals));
        $estimate->items()->delete();
        foreach ($items as $i => $item) {
            $estimate->items()->create(array_merge($item, ['order' => $i + 1]));
        }

        return back()->with('success', __('messages.estimate_updated'));
    }

    public function destroy(Estimate $estimate)
    {
        abort_unless($estimate->status === 'draft', 403);
        $estimate->delete();

        return redirect()->route('tenant.manager.estimates.index')
            ->with('success', __('messages.estimate_deleted'));
    }

    public function send(Estimate $estimate)
    {
        $estimate->update(['status' => 'sent', 'sent_at' => now()]);

        return back()->with('success', __('messages.estimate_sent'));
    }

    /**
     * Turns an accepted estimate into an invoice.
     */
    public function convertToInvoice(Estimate $estimate)
    {
        abort_unless($estimate->status === 'accepted', 422, __('messages.only_accepted_estimates_convertible'));

        $invoice = Invoice::create([
            'client_id' => $estimate->client_id,
            'number' => 'FV/' . now()->year . '/' . now()->format('m') . '/' . str_pad(Invoice::count() + 1, 3, '0', STR_PAD_LEFT),
            'status' => 'draft',
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'currency' => $estimate->currency,
            'subtotal' => $estimate->subtotal,
            'tax_amount' => $estimate->tax_amount,
            'total' => $estimate->total,
            'notes' => $estimate->notes,
            'created_by' => Auth::guard('tenant')->id(),
        ]);

        foreach ($estimate->items as $item) {
            $invoice->items()->create($item->only(['description', 'quantity', 'unit', 'unit_price', 'tax_rate', 'discount_percent', 'total', 'order']));
        }

        $estimate->update(['status' => 'converted']);

        return redirect()->route('tenant.manager.invoices.show', $invoice)
            ->with('success', __('messages.estimate_converted'));
    }

    public function pdf(Estimate $estimate)
    {
        $estimate->load(['client', 'items']);
        $pdf = Pdf::loadView('pdf.estimate', ['estimate' => $estimate])->setPaper('a4');

        return $pdf->download('wycena-' . $estimate->number . '.pdf');
    }

    // ── Private ───────────────────────────────────────────────────────────────

    private function computeItems(array $rawItems): array
    {
        return array_map(function ($item) {
            $subtotal = round($item['quantity'] * $item['unit_price'], 2);
            $tax = round($subtotal * (($item['tax_rate'] ?? 0) / 100), 2);

            return array_merge($item, ['subtotal' => $subtotal, 'tax' => $tax, 'total' => $subtotal + $tax]);
        }, $rawItems);
    }

    private function computeTotals(array $items, float $discount): array
    {
        $subtotal = array_sum(array_column($items, 'subtotal'));
        $taxTotal = array_sum(array_column($items, 'tax'));
        $total = round(($subtotal + $taxTotal) * (1 - $discount / 100), 2);

        return ['subtotal' => $subtotal, 'tax' => $taxTotal, 'total' => $total];
    }

    private function generateNumber(): string
    {
        $year = now()->year;
        $month = now()->format('m');
        $count = Estimate::whereYear('created_at', $year)->whereMonth('created_at', now()->month)->count() + 1;

        return sprintf('WY/%d/%s/%03d', $year, $month, $count);
    }
}
