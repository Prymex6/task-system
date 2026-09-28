<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Client;
use App\Models\Tenant\Lead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $query = Lead::with('assignedTo');

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('contact_name', 'like', '%' . $request->search . '%')
                    ->orWhere('email', 'like', '%' . $request->search . '%')
                    ->orWhere('company_name', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        if ($request->filled('assignee_id')) {
            $query->where('assigned_to', $request->assignee_id);
        }

        $leads = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Tenant/Manager/CRM/Leads/Index', [
            'leads' => $leads,
            'filters' => $request->only(['search', 'status', 'source', 'assignee_id']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Tenant/Manager/CRM/Leads/Form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'website' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:50',
            'status' => 'nullable|in:new,contacted,qualified,unqualified,proposal,negotiation,won,lost,converted',
            'currency' => 'nullable|string|max:5',
            'notes' => 'nullable|string|max:2000',
            'assigned_to' => 'nullable|exists:users,id',
            'value' => 'nullable|numeric|min:0',
        ]);

        $validated['status'] = $validated['status'] ?? 'new';
        Lead::create($validated);

        return back()->with('success', __('messages.lead_created'));
    }

    public function show(Lead $lead)
    {
        $lead->load(['assignedTo', 'activities.user', 'client']);

        return Inertia::render('Tenant/Manager/CRM/Leads/Show', [
            'lead' => $lead,
        ]);
    }

    public function edit(Lead $lead)
    {
        return Inertia::render('Tenant/Manager/CRM/Leads/Form', [
            'lead' => $lead,
        ]);
    }

    public function update(Request $request, Lead $lead)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'website' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'industry' => 'nullable|string|max:255',
            'source' => 'nullable|string|max:50',
            'status' => 'required|in:new,contacted,qualified,unqualified,proposal,negotiation,won,lost,converted',
            'currency' => 'nullable|string|max:5',
            'notes' => 'nullable|string|max:2000',
            'assigned_to' => 'nullable|exists:users,id',
            'value' => 'nullable|numeric|min:0',
        ]);

        $lead->update($validated);

        return back()->with('success', __('messages.lead_updated'));
    }

    public function destroy(Lead $lead)
    {
        $lead->delete();

        return back()->with('success', __('messages.lead_deleted'));
    }

    /**
     * Konwertuje lead na klienta.
     */
    public function convert(Lead $lead)
    {
        $client = Client::create([
            'name' => $lead->contact_name ?? $lead->company_name,
            'company_name' => $lead->company_name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'notes' => $lead->notes,
            'is_active' => true,
        ]);

        $lead->update(['status' => 'converted', 'converted_client_id' => $client->id, 'converted_at' => now()]);

        return redirect()->route('tenant.manager.clients.show', $client)
            ->with('success', __('messages.lead_converted'));
    }

    public function addActivity(Request $request, Lead $lead)
    {
        $validated = $request->validate([
            'type' => 'required|in:call,email,meeting,note,task',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'occurred_at' => 'required|date',
        ]);

        $lead->activities()->create(array_merge($validated, [
            'user_id' => Auth::guard('tenant')->id(),
        ]));

        return back()->with('success', __('messages.activity_saved'));
    }
}
