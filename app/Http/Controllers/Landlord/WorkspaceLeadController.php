<?php

namespace App\Http\Controllers\Landlord;

use App\Http\Controllers\Controller;
use App\Models\Landlord\WorkspaceLead;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Companies worth approaching about the platform.
 *
 * Distinct from a tenant's own CRM leads: these are prospects for the
 * platform itself, and they live in the landlord database.
 */
class WorkspaceLeadController extends Controller
{
    public function index(Request $request)
    {
        $leads = WorkspaceLead::query()
            ->when($request->query('search'), fn ($q, $term) => $q->where(
                fn ($w) => $w->where('company_name', 'like', "%{$term}%")
                    ->orWhere('email', 'like', "%{$term}%")
            ))
            ->when($request->query('contacted'), fn ($q, $state) => $state === 'yes'
                ? $q->whereNotNull('contacted_at')
                : $q->whereNull('contacted_at'))
            ->orderByDesc('created_at')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Landlord/WorkspaceLeads/Index', [
            'leads' => $leads,
            'filters' => $request->only(['search', 'contacted']),
        ]);
    }

    public function toggleContacted(WorkspaceLead $workspaceLead)
    {
        // The column is a timestamp, not a flag: when they were contacted is
        // worth more than whether they were.
        $workspaceLead->update([
            'contacted_at' => $workspaceLead->contacted_at ? null : now(),
        ]);

        return back()->with('success', __('messages.lead_updated'));
    }
}
