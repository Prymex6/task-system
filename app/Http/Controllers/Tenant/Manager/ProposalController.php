<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Mail\ProposalMail;
use App\Models\Tenant\Client;
use App\Models\Tenant\Invoice;
use App\Models\Tenant\Proposal;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ProposalController extends Controller
{
    public function index(Request $request)
    {
        $proposals = Proposal::with('client')
            ->when($request->search, fn ($q, $s) => $q->where('title', 'like', "%$s%"))
            ->when($request->status, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('Tenant/Manager/Proposals/Index', [
            'proposals' => $proposals,
            'filters' => $request->only('search', 'status'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Tenant/Manager/Proposals/Form', [
            'clients' => Client::orderBy('company_name')->get(['id', 'company_name', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'client_id' => 'required|exists:clients,id',
            'body' => 'nullable|string',
            'valid_until' => 'nullable|date',
            'currency' => 'nullable|string|size:3',
            'total_net' => 'nullable|numeric|min:0',
            'total_tax' => 'nullable|numeric|min:0',
            'total_gross' => 'nullable|numeric|min:0',
            'issue_date' => 'nullable|date',
            'number' => 'nullable|string|max:50',
        ]);

        if (empty($validated['number'])) {
            $year = now()->year;
            $month = now()->format('m');
            $last = Proposal::whereYear('created_at', $year)->count() + 1;
            $validated['number'] = "OF/{$year}/{$month}/" . str_pad($last, 3, '0', STR_PAD_LEFT);
        }

        $validated['status'] = 'draft';
        $validated['issue_date'] = $validated['issue_date'] ?? now()->toDateString();
        $validated['created_by'] = Auth::guard('tenant')->id();

        Proposal::create($validated);

        return redirect()->route('tenant.manager.proposals.index')
            ->with('success', __('messages.proposal_created'));
    }

    public function show(Proposal $proposal)
    {
        $proposal->load('client', 'items');

        return Inertia::render('Tenant/Manager/Proposals/Show', [
            'proposal' => $proposal,
        ]);
    }

    public function edit(Proposal $proposal)
    {
        return Inertia::render('Tenant/Manager/Proposals/Form', [
            'proposal' => $proposal,
            'clients' => Client::orderBy('company_name')->get(['id', 'company_name', 'name']),
        ]);
    }

    public function update(Request $request, Proposal $proposal)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'client_id' => 'required|exists:clients,id',
            'body' => 'nullable|string',
            'valid_until' => 'nullable|date',
            'currency' => 'nullable|string|size:3',
            'total_net' => 'nullable|numeric|min:0',
            'total_tax' => 'nullable|numeric|min:0',
            'total_gross' => 'nullable|numeric|min:0',
            'issue_date' => 'nullable|date',
            'number' => 'nullable|string|max:50',
            'status' => 'nullable|in:draft,sent,viewed,accepted,rejected,expired',
        ]);

        $proposal->update($validated);

        return redirect()->route('tenant.manager.proposals.show', $proposal->id)
            ->with('success', __('messages.proposal_updated'));
    }

    public function destroy(Proposal $proposal)
    {
        $proposal->delete();

        return redirect()->route('tenant.manager.proposals.index')
            ->with('success', __('messages.proposal_deleted'));
    }

    public function send(Proposal $proposal)
    {
        $email = $proposal->client?->email ?? $proposal->lead?->email;
        abort_unless($email, 422, __('messages.recipient_has_no_email'));

        Mail::to($email)->queue(new ProposalMail($proposal));

        $proposal->update(['status' => 'sent', 'sent_at' => now()]);

        return back()->with('success', __('messages.proposal_sent'));
    }

    public function changeStatus(Request $request, Proposal $proposal)
    {
        $request->validate([
            'status' => 'required|in:draft,sent,viewed,accepted,rejected,expired',
        ]);

        $proposal->update(['status' => $request->status]);

        return back()->with('success', __('messages.proposal_status_updated'));
    }

    public function convertToInvoice(Proposal $proposal)
    {
        $invoice = Invoice::create([
            'client_id' => $proposal->client_id,
            'created_by' => Auth::guard('tenant')->id(),
            'number' => 'FV/' . now()->format('Y/m') . '/' . str_pad(Invoice::whereYear('created_at', now()->year)->count() + 1, 3, '0', STR_PAD_LEFT),
            'status' => 'draft',
            'currency' => $proposal->currency,
            'subtotal' => $proposal->total_net,
            'tax_amount' => $proposal->total_tax,
            'total' => $proposal->total_gross,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
        ]);

        return redirect()->route('tenant.manager.invoices.show', $invoice)
            ->with('success', __('messages.invoice_created_from_proposal'));
    }

    public function pdf(Proposal $proposal)
    {
        $proposal->load('client');

        $pdf = Pdf::loadView('pdf.proposal', compact('proposal'));

        return $pdf->stream('propozycja-' . Str::slug($proposal->title) . '.pdf');
    }
}
