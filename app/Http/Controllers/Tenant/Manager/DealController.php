<?php

namespace App\Http\Controllers\Tenant\Manager;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Client;
use App\Models\Tenant\Deal;
use App\Models\Tenant\DealStage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DealController extends Controller
{
    public function index(Request $request)
    {
        $query = Deal::with(['client', 'assignedTo', 'stage']);

        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('stage_id')) {
            $query->where('deal_stage_id', $request->stage_id);
        }

        $deals = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Tenant/Manager/CRM/Deals/Index', [
            'deals' => $deals,
            'stages' => DealStage::orderBy('order')->get(['id', 'name', 'color']),
            'filters' => $request->only(['search', 'assignee_id', 'stage_id']),
        ]);
    }

    public function pipeline(Request $request)
    {
        $stages = DealStage::with(['deals.client', 'deals.assignedTo'])
            ->orderBy('order')
            ->get();

        return Inertia::render('Tenant/Manager/CRM/Deals/Pipeline', [
            'stages' => $stages,
            'filters' => $request->only(['search', 'assignee_id']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Tenant/Manager/CRM/Deals/Form', [
            'clients' => Client::orderBy('company_name')->get(['id', 'company_name', 'name']),
            'stages' => DealStage::orderBy('order')->get(['id', 'name', 'color']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'client_id' => 'nullable|exists:clients,id',
            'deal_stage_id' => 'required|exists:deal_stages,id',
            'value' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:5',
            'expected_close_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $maxOrder = Deal::where('deal_stage_id', $validated['deal_stage_id'])->max('order') ?? 0;
        $validated['order'] = $maxOrder + 1;

        $deal = Deal::create($validated);

        return redirect()->route('tenant.manager.deals.show', $deal)
            ->with('success', __('messages.deal_created'));
    }

    public function show(Deal $deal)
    {
        $deal->load(['client', 'stage', 'assignedTo', 'notes']);

        return Inertia::render('Tenant/Manager/CRM/Deals/Show', [
            'deal' => $deal,
            'stages' => DealStage::orderBy('order')->get(),
        ]);
    }

    public function edit(Deal $deal)
    {
        return Inertia::render('Tenant/Manager/CRM/Deals/Form', [
            'deal' => $deal,
            'clients' => Client::orderBy('company_name')->get(['id', 'company_name', 'name']),
            'stages' => DealStage::orderBy('order')->get(['id', 'name', 'color']),
        ]);
    }

    public function update(Request $request, Deal $deal)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'client_id' => 'nullable|exists:clients,id',
            'deal_stage_id' => 'required|exists:deal_stages,id',
            'value' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:5',
            'expected_close_date' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
            'assigned_to' => 'nullable|exists:users,id',
        ]);

        $deal->update($validated);

        return back()->with('success', __('messages.deal_updated'));
    }

    public function destroy(Deal $deal)
    {
        $deal->delete();

        return back()->with('success', __('messages.deal_deleted'));
    }

    /**
     * Przenosi deal do innego stage (drag & drop z pipeline).
     */
    public function move(Request $request, Deal $deal)
    {
        $validated = $request->validate([
            'deal_stage_id' => 'required|exists:deal_stages,id',
            'order' => 'nullable|integer|min:0',
        ]);

        $deal->update($validated);

        return response()->json(['success' => true]);
    }

    public function won(Deal $deal)
    {
        $wonStage = DealStage::where('is_won', true)->first();

        $deal->update([
            'deal_stage_id' => $wonStage?->id ?? $deal->deal_stage_id,
            'closed_at' => now(),
        ]);

        return back()->with('success', __('messages.deal_marked_won'));
    }

    public function lost(Deal $deal)
    {
        $lostStage = DealStage::where('is_lost', true)->first();

        $deal->update([
            'deal_stage_id' => $lostStage?->id ?? $deal->deal_stage_id,
            'closed_at' => now(),
        ]);

        return back()->with('success', __('messages.deal_marked_lost'));
    }

    /**
     * Record a call, meeting or note against a deal, so the timeline shows
     * what actually moved it rather than only its stage changes.
     */
    public function addActivity(Request $request, Deal $deal)
    {
        $user = Auth::guard('tenant')->user();
        abort_unless($user, 403);

        $validated = $request->validate([
            'action' => 'required|in:call,meeting,email,note,task',
            'note' => 'nullable|string|max:2000',
            'occurred_at' => 'nullable|date',
        ]);

        $deal->activities()->create([
            'user_id' => $user->id,
            'action' => $validated['action'],
            'data' => [
                'note' => $validated['note'] ?? null,
                'occurred_at' => $validated['occurred_at'] ?? now()->toDateTimeString(),
            ],
        ]);

        return back()->with('success', __('messages.activity_saved'));
    }
}
