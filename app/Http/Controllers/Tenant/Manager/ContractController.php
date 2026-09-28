<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Client;
use App\Models\Tenant\Contract;
use App\Models\Tenant\ContractType;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $query = Contract::with(['client', 'type']);

        if ($request->filled('search')) {
            $query->where('subject', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        $contracts = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Tenant/Manager/Contracts/Index', [
            'contracts' => $contracts,
            'clients' => Client::where('is_active', true)->orderBy('name')->get(['id', 'name', 'company_name']),
            'types' => ContractType::orderBy('name')->get(['id', 'name']),
            'filters' => $request->only(['search', 'status', 'client_id']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Tenant/Manager/Contracts/Form', [
            'clients' => Client::where('is_active', true)->orderBy('name')->get(['id', 'name', 'company_name']),
            'types' => ContractType::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'client_id' => 'required|exists:clients,id',
            'contract_type_id' => 'nullable|exists:contract_types,id',
            'status' => 'nullable|in:draft,sent,signed,active,expired,cancelled,terminated',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'value' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:5',
            'body' => 'nullable|string',
        ]);

        $validated['status'] = $validated['status'] ?? 'draft';
        $validated['created_by'] = Auth::guard('tenant')->id();
        $contract = Contract::create($validated);

        return redirect()->route('tenant.manager.contracts.show', $contract)
            ->with('success', __('messages.contract_created'));
    }

    public function show(Contract $contract)
    {
        $contract->load(['client', 'type', 'renewals', 'creator']);

        return Inertia::render('Tenant/Manager/Contracts/Show', [
            'contract' => $contract,
        ]);
    }

    public function edit(Contract $contract)
    {
        return Inertia::render('Tenant/Manager/Contracts/Form', [
            'contract' => $contract,
            'clients' => Client::where('is_active', true)->orderBy('name')->get(['id', 'name', 'company_name']),
            'types' => ContractType::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Contract $contract)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'client_id' => 'required|exists:clients,id',
            'contract_type_id' => 'nullable|exists:contract_types,id',
            'status' => 'nullable|in:draft,sent,signed,active,expired,cancelled,terminated',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'value' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:5',
            'body' => 'nullable|string',
        ]);

        $contract->update($validated);

        return back()->with('success', __('messages.contract_updated'));
    }

    public function destroy(Contract $contract)
    {
        abort_unless($contract->status === 'draft', 403, __('messages.only_drafts_deletable'));
        $contract->delete();

        return redirect()->route('tenant.manager.contracts.index')
            ->with('success', __('messages.contract_deleted'));
    }

    public function send(Contract $contract)
    {
        $contract->update(['status' => 'sent', 'sent_at' => now()]);

        return back()->with('success', __('messages.contract_sent'));
    }

    public function activate(Contract $contract)
    {
        $contract->update(['status' => 'active']);

        return back()->with('success', __('messages.contract_activated'));
    }

    public function terminate(Contract $contract)
    {
        $contract->update(['status' => 'terminated']);

        return back()->with('success', __('messages.contract_terminated'));
    }

    /**
     * Pobiera PDF umowy.
     */
    public function pdf(Contract $contract)
    {
        $contract->load(['client', 'type']);
        $pdf = Pdf::loadView('pdf.contract', ['contract' => $contract])->setPaper('a4');

        return $pdf->download('umowa-' . Str::slug($contract->subject) . '.pdf');
    }
}
